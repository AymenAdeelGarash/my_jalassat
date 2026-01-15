<?php
require_once '../config.php';

if (!isAdmin()) {
    redirect('login.php');
}

$action = $_GET['action'] ?? 'dashboard';

// Safe Data Loading
$settings = getSettings();
$products = getData('products');
$orders   = getData('orders');
$stats    = getData('stats');
$users    = getData('users');

$msg = '';

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Update Settings
    if (isset($_POST['update_settings'])) {
        $settings['store_name'] = $_POST['store_name'];
        $settings['store_phone'] = $_POST['store_phone'];
        $settings['store_status'] = $_POST['store_status'];
        $settings['instagram'] = $_POST['instagram'];
        $settings['snapchat'] = $_POST['snapchat'];
        $settings['primary_color'] = $_POST['primary_color'];
        $settings['snow_enabled'] = isset($_POST['snow_enabled']);
        saveData('settings', $settings);
        $msg = "تم تحديث الإعدادات بنجاح!";
    }

    // 2. Add Product (with Image Upload)
    if (isset($_POST['add_product'])) {
        $image_path = $_POST['image_url']; // Default to URL
        
        // Handle File Upload
        if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === 0) {
            $ext = pathinfo($_FILES['image_file']['name'], PATHINFO_EXTENSION);
            $new_name = 'prod_' . time() . '.' . $ext;
            $destination = '../assets/images/products/' . $new_name;
            if (move_uploaded_file($_FILES['image_file']['tmp_name'], $destination)) {
                $image_path = 'assets/images/products/' . $new_name;
            }
        }

        $products[] = [
            "id" => time(),
            "name" => $_POST['name'],
            "price_regular" => (int)$_POST['price_regular'],
            "price_weekend" => (int)$_POST['price_weekend'],
            "image" => $image_path,
            "status" => "available",
            "badge" => $_POST['badge'],
            "description" => $_POST['description'],
            "ar_url" => $_POST['ar_url']
        ];
        saveData('products', $products);
        $msg = "تمت إضافة المنتج بنجاح!";
    }

    // 3. Update Product
    if (isset($_POST['update_product'])) {
        $id = $_POST['id'];
        foreach ($products as &$p) {
            if ($p['id'] == $id) {
                $p['name'] = $_POST['name'];
                $p['price_regular'] = (int)$_POST['price_regular'];
                $p['price_weekend'] = (int)$_POST['price_weekend'];
                $p['status'] = $_POST['status'];
                $p['badge'] = $_POST['badge'];
                $p['description'] = $_POST['description'] ?? '';
                $p['image'] = $_POST['image_url'] ?? $p['image'];
                $p['ar_url'] = $_POST['ar_url'] ?? '';
            }
        }
        saveData('products', $products);
        $msg = "تم تحديث المنتج!";
    }

    // 4. Update/Cancel Order
    if (isset($_POST['update_order_status'])) {
        $oid = $_POST['order_id'];
        $new_status = $_POST['new_status'];
        $bookings = getData('bookings');

        if ($new_status === 'delete') {
            $orders = array_filter($orders, fn($o) => $o['id'] != $oid);
            $bookings = array_filter($bookings, fn($b) => ($b['order_id'] ?? '') != $oid);
        } else {
            foreach ($orders as &$o) {
                if ($o['id'] == $oid) {
                    $old_status = $o['status'] ?? '';
                    $o['status'] = $new_status;
                    
                    // Award Loyalty Points if status changed to 'completed'
                    if ($new_status === 'completed' && $old_status !== 'completed' && isset($o['customer_id'])) {
                        $customers = getData('customers');
                        $points_to_add = floor(($o['total'] ?? 0) / 10); // 1 point per 10 SAR
                        foreach($customers as &$cust) {
                            if ($cust['id'] == $o['customer_id']) {
                                $cust['points'] = ($cust['points'] ?? 0) + $points_to_add;
                                break;
                            }
                        }
                        saveData('customers', $customers);
                    }
                }
            }
            foreach ($bookings as &$b) {
                if (($b['order_id'] ?? '') == $oid) $b['status'] = $new_status;
            }
        }
        saveData('orders', $orders);
        saveData('bookings', $bookings);
        $msg = "تم تحديث الطلب والحجوزات المرتبطة به!";
    }

    // 5. User Management (Add User)
    if (isset($_POST['add_user'])) {
        $new_user = [
            "username" => $_POST['username'],
            "password" => password_hash($_POST['password'], PASSWORD_DEFAULT),
            "role" => $_POST['role']
        ];
        $users[] = $new_user;
        saveData('users', $users);
        $msg = "تمت إضافة المستخدم بنجاح!";
    }

    // 6. Coupon Management
    if (isset($_POST['add_coupon'])) {
        $coupons = getData('coupons');
        $coupons[] = [
            "id" => time(),
            "code" => $_POST['code'],
            "discount_type" => $_POST['discount_type'],
            "discount_value" => (int)$_POST['discount_value'],
            "min_order" => (int)$_POST['min_order'],
            "max_uses" => (int)$_POST['max_uses'],
            "used_count" => 0,
            "valid_from" => $_POST['valid_from'],
            "valid_until" => $_POST['valid_until'],
            "status" => "active"
        ];
        saveData('coupons', $coupons);
        $msg = "تمت إضافة الكوبون بنجاح!";
    }

    if (isset($_POST['delete_coupon'])) {
        $coupons = getData('coupons');
        $id = $_POST['coupon_id'];
        $coupons = array_filter($coupons, fn($c) => $c['id'] != $id);
        saveData('coupons', $coupons);
        $msg = "تم حذف الكوبون!";
    }

    // 7. Review Management
    if (isset($_POST['update_review'])) {
        $reviews = getData('reviews');
        $rid = $_POST['review_id'];
        $new_status = $_POST['new_status'];
        if ($new_status === 'delete') {
            $reviews = array_filter($reviews, fn($r) => $r['id'] != $rid);
        } else {
            foreach ($reviews as &$r) {
                if ($r['id'] == $rid) $r['status'] = $new_status;
            }
        }
        saveData('reviews', $reviews);
        $msg = "تم تحديث التقييم!";
    }

    // 8. Add-ons Management
    if (isset($_POST['add_addon'])) {
        $addons = getData('addons');
        $addons[] = [
            "id" => uniqid('a'),
            "name" => $_POST['name'],
            "price" => (int)$_POST['price'],
            "image" => $_POST['image_url']
        ];
        saveData('addons', $addons);
        $msg = "تمت إضافة الملحق!";
    }
    if (isset($_POST['delete_addon'])) {
        $addons = getData('addons');
        $aid = $_POST['addon_id'];
        $addons = array_filter($addons, fn($a) => $a['id'] != $aid);
        saveData('addons', $addons);
        $msg = "تم حذف الملحق!";
    }
    if (isset($_POST['update_addon'])) {
        $addons = getData('addons');
        $aid = $_POST['addon_id'];
        foreach ($addons as &$a) {
            if ($a['id'] == $aid) {
                $a['name'] = $_POST['name'];
                $a['price'] = (int)$_POST['price'];
                $a['image'] = $_POST['image_url'];
            }
        }
        saveData('addons', $addons);
        $msg = "تم تحديث الملحق!";
    }
}

// Data Loading
$settings = getSettings();
$products = getData('products');
$orders   = getData('orders');
$stats    = getData('stats');
$users    = getData('users');
$coupons  = getData('coupons');
$reviews  = getData('reviews');
$addons   = getData('addons');
$bookings = getData('bookings');
$customers = getData('customers');

// Data Export Logic (Engineer Feature)
if (isAdmin() && isset($_GET['export'])) {
    $data_to_export = [];
    foreach ($paths as $key => $p) {
        $data_to_export[$key] = getData($key);
    }
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="galassat_backup_'.date('Y-m-d').'.json"');
    echo json_encode($data_to_export, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// Stats Calculation
$total_clicks = 0;
if($stats) {
    foreach($stats as $k => $s) {
        if (is_array($s) && isset($s['clicks'])) {
            $total_clicks += $s['clicks'];
        }
    }
}
$total_orders = count($orders);
$revenue = 0;
if($orders) {
    foreach($orders as $o) {
        if(($o['status'] ?? '') == 'completed' || ($o['status'] ?? '') == 'confirmed') {
            $revenue += ($o['total'] ?? 0);
        }
    }
}

?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>البوابة الإدارية | تحكم كامل</title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: <?php echo $settings['primary_color']; ?>;
            --primary-glow: <?php echo $settings['primary_color']; ?>44;
            --bg-deep: #050608;
            --bg-surface: #0c0e12;
            --bg-sidebar: #08090b;
            --text-main: #ffffff;
            --text-dim: #8b949e;
            --border: rgba(255,255,255,0.06);
            --accent-success: #238636;
            --accent-danger: #f85149;
            --glass: rgba(255,255,255,0.02);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Tajawal', sans-serif; }
        body { background: var(--bg-deep); color: var(--text-main); display: flex; min-height: 100vh; overflow-x: hidden; }

        /* Premium Sidebar */
        .sidebar { 
            width: 280px; background: var(--bg-sidebar); border-left: 1px solid var(--border); 
            display: flex; flex-direction: column; position: fixed; height: 100vh; right: 0; z-index: 100;
            padding: 20px;
        }
        .nav-logo { padding: 30px 15px; font-size: 1.8rem; font-weight: 900; letter-spacing: -1px; }
        .nav-logo span { color: var(--primary); }
        
        .nav-menu { flex-grow: 1; margin-top: 20px; }
        .nav-item { 
            display: flex; align-items: center; padding: 14px 20px; color: var(--text-dim); 
            text-decoration: none; border-radius: 16px; margin-bottom: 5px; transition: 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            font-weight: 600;
        }
        .nav-item i { margin-left: 15px; font-size: 1.2rem; width: 25px; text-align: center; }
        .nav-item:hover { background: var(--glass); color: #fff; }
        .nav-item.active { background: var(--primary); color: #000; box-shadow: 0 10px 20px var(--primary-glow); }

        /* Main Area */
        .main-content { margin-right: 280px; padding: 40px 60px; width: calc(100% - 280px); }
        header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px; }
        header h1 { font-size: 1.8rem; font-weight: 900; }

        /* Data Cards */
        .data-card { 
            background: var(--bg-surface); border-radius: 28px; padding: 35px; 
            border: 1px solid var(--border); margin-bottom: 25px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
        }
        .data-card h2 { font-size: 1.3rem; margin-bottom: 20px; color: var(--primary); }

        /* Modern Table */
        table { width: 100%; border-collapse: separate; border-spacing: 0 10px; }
        th { text-align: right; color: var(--text-dim); padding: 0 15px 10px; font-size: 0.85rem; font-weight: 500; }
        td { background: var(--glass); padding: 18px 15px; }
        td:first-child { border-radius: 0 15px 15px 0; }
        td:last-child { border-radius: 15px 0 0 15px; }

        /* Buttons & Inputs */
        .btn-action { 
            padding: 12px 25px; border-radius: 14px; border: none; cursor: pointer; 
            font-weight: 800; transition: 0.3s; display: inline-flex; align-items: center; justify-content: center;
        }
        .preview-btn { background: #1a1e23; color: #fff; text-decoration: none; font-size: 0.85rem; border: 1px solid var(--border); }
        .preview-btn:hover { background: #252a33; }
        .btn-update { background: var(--primary); color: #000; }
        .btn-update:hover { transform: translateY(-2px); box-shadow: 0 10px 25px var(--primary-glow); }
        .btn-danger { background: var(--accent-danger); color: #fff; }
        .btn-danger:hover { background: #d93f37; }
        
        .edit-input { 
            background: #08090b; border: 1px solid var(--border); color: #fff; 
            padding: 14px 18px; border-radius: 14px; width: 100%; transition: 0.2s;
        }
        .edit-input:focus { border-color: var(--primary); outline: none; background: #0c0e12; }

        /* Responsive Enhancements */
        @media (max-width: 1024px) {
            .sidebar { width: 80px; padding: 20px 10px; }
            .sidebar span:not(.fa-solid), .sidebar .nav-logo span, .nav-item span { display: none; }
            .main-content { margin-right: 80px; width: calc(100% - 80px); padding: 20px; }
            .nav-item i { margin-left: 0; font-size: 1.4rem; }
            .nav-logo { font-size: 1.2rem; text-align: center; }
        }

        @media (max-width: 768px) {
            .sidebar { 
                transform: translateX(100%); 
                transition: 0.4s cubic-bezier(0.4, 0, 0.2, 1);
                width: 280px; z-index: 1000;
            }
            .sidebar.active { transform: translateX(0); }
            .sidebar span:not(.fa-solid), .sidebar .nav-logo span, .nav-item span { display: inline; }
            .main-content { margin-right: 0; width: 100%; padding: 15px; }
            header { flex-direction: column; align-items: flex-start; gap: 15px; }
            .stats-grid { grid-template-columns: 1fr; }
            .mobile-toggle { display: block !important; }
            
            /* Table responsiveness */
            .data-card { overflow-x: auto; -webkit-overflow-scrolling: touch; }
            table { min-width: 600px; }
        }

        .mobile-toggle {
            display: none;
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 60px;
            height: 60px;
            background: var(--primary);
            color: #000;
            border-radius: 50%;
            border: none;
            font-size: 1.5rem;
            z-index: 1100;
            box-shadow: 0 10px 20px var(--primary-glow);
            cursor: pointer;
        }

        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.8);
            backdrop-filter: blur(5px);
            z-index: 900;
        }
        .sidebar-overlay.active { display: block; }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="nav-logo">WINTER <span>ADMIN</span></div>
        <div class="nav-menu">
            <a href="?action=dashboard" class="nav-item <?php echo $action=='dashboard'?'active':''; ?>"><i class="fa-solid fa-chart-pie"></i> لوحة الإحصائيات</a>
            <a href="?action=products" class="nav-item <?php echo $action=='products'?'active':''; ?>"><i class="fa-solid fa-couch"></i> الجلسات</a>
            <a href="?action=orders" class="nav-item <?php echo $action=='orders'?'active':''; ?>"><i class="fa-solid fa-receipt"></i> المبيعات</a>
            <a href="?action=users" class="nav-item <?php echo $action=='users'?'active':''; ?>"><i class="fa-solid fa-users"></i> مستخدمي النظام</a>
            <a href="?action=coupons" class="nav-item <?php echo $action=='coupons'?'active':''; ?>"><i class="fa-solid fa-ticket"></i> الكوبونات</a>
            <a href="?action=reviews" class="nav-item <?php echo $action=='reviews'?'active':''; ?>"><i class="fa-solid fa-star"></i> التقييمات</a>
            <a href="?action=customers" class="nav-item <?php echo $action=='customers'?'active':''; ?>"><i class="fa-solid fa-user-group"></i> العملاء</a>
            <a href="?action=addons" class="nav-item <?php echo $action=='addons'?'active':''; ?>"><i class="fa-solid fa-plus-circle"></i> الملحقات</a>
            <a href="?action=settings" class="nav-item <?php echo $action=='settings'?'active':''; ?>"><i class="fa-solid fa-gears"></i> الإعدادات</a>
            <a href="?export=1" class="nav-item" style="margin-top:20px; border:1px dashed var(--border); color:var(--primary)"><i class="fa-solid fa-download"></i> <span>نسخة احتياطية</span></a>
            <a href="logout.php" class="nav-item" style="color:var(--accent-danger); margin-top: auto;"><i class="fa-solid fa-power-off"></i> تسجيل الخروج</a>
        </div>
    </div>

    <div class="main-content">
        <header>
            <h1>أهلاً بك، <?php echo $_SESSION['admin_username'] ?? 'المدير العام'; ?></h1>
            <a href="../index.php" target="_blank" class="btn-action preview-btn"><i class="fa-solid fa-eye" style="margin-left: 10px"></i> معاينة المتجر</a>
        </header>

        <?php if($msg) echo "<p style='color:var(--success); margin-bottom:20px; font-weight:700'>$msg</p>"; ?>

        <?php if($action === 'dashboard'): ?>
        <div class="stats-grid">
            <div class="stat-card"><h3>الزيارات</h3><div class="value"><?php echo $total_clicks; ?></div></div>
            <div class="stat-card"><h3>الطلبات</h3><div class="value"><?php echo $total_orders; ?></div></div>
            <div class="stat-card"><h3>الأرباح</h3><div class="value"><?php echo $revenue; ?> ر.س</div></div>
        </div>
        <?php endif; ?>

        <?php if($action === 'products'): ?>
        <div class="data-card">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px">
                <h2>إدارة الجلسات</h2>
                <button class="btn-action btn-update" onclick="document.getElementById('addModal').style.display='block'">+ إضافة جلسة جديدة</button>
            </div>
            <table>
                <?php foreach($products as $p): 
                    $img_src = (strpos($p['image'], 'http') === 0) ? $p['image'] : '../' . $p['image'];
                ?>
                <tr style="border-bottom: 1px solid var(--border)">
                    <td style="padding: 15px;"><img src="<?php echo $img_src; ?>" style="width:50px; height:50px; border-radius:10px; object-fit:cover"></td>
                    <td><?php echo $p['name']; ?></td>
                    <td>
                        <div style="font-size:0.8rem; color:var(--text-dim)">عادي: <?php echo $p['price_regular'] ?? 0; ?> ر.س</div>
                        <div style="font-size:0.8rem; color:var(--primary)">ويكند: <?php echo $p['price_weekend'] ?? ($p['price_regular'] ?? 0); ?> ر.س</div>
                    </td>
                    <td><button class="btn-action" style="background:var(--primary); color:#000" onclick='openEditProductModal(<?php echo json_encode($p); ?>)'>تعديل</button></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
        <?php endif; ?>

        <?php if($action === 'orders'): ?>
        <div class="data-card">
            <h2>طلبات العملاء (إدارة الحجوزات)</h2>
            <div class="mini-orders-list">
                <?php foreach(array_reverse($orders) as $o): ?>
                <div class="order-item-compact">
                    <div class="order-main">
                        <?php 
                            $item_names = [];
                            if (isset($o['cart'])) {
                                foreach($o['cart'] as $item) $item_names[] = $item['name'];
                            }
                            $display_items = !empty($item_names) ? implode(', ', $item_names) : ($o['items'] ?? 'طلب');
                        ?>
                        <?php 
                            $customer_info = '';
                            if (isset($o['customer_id'])) {
                                foreach($customers as $cust) {
                                    if ($cust['id'] == $o['customer_id']) {
                                        $customer_info = " <span style='color:var(--primary); font-weight:700'>(" . $cust['name'] . ")</span>";
                                        break;
                                    }
                                }
                            }
                        ?>
                        <div><strong>#<?php echo substr($o['id'], -6); ?></strong> - <?php echo $display_items; ?><?php echo $customer_info; ?></div>
                        <div style="font-size:0.8rem; color:var(--muted)"><?php echo $o['total']; ?> ر.س - تاريخ الحجز: <?php echo $o['booking_date'] ?? 'غير محدد'; ?> - <span class="order-status-tag status-<?php echo $o['status']; ?>"><?php echo $o['status']; ?></span></div>
                    </div>
                    <form method="POST" style="display:flex; gap:10px">
                        <input type="hidden" name="order_id" value="<?php echo $o['id']; ?>">
                        <select name="new_status" class="edit-input" style="width: auto; margin:0;" onchange="this.form.submit()">
                            <option value="">تغيير الحالة</option>
                            <option value="pending">انتظار</option>
                            <option value="completed">مكتمل</option>
                            <option value="cancelled">ملغى (إلغاء الحجز)</option>
                            <option value="delete">حذف نهائي</option>
                        </select>
                        <input type="hidden" name="update_order_status" value="1">
                    </form>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if($action === 'users'): ?>
        <div class="data-card">
            <h2>إدارة مدراء النظام</h2>
            <form method="POST" style="display:grid; grid-template-columns: 1fr 1fr 1fr auto; gap:15px; margin-bottom:30px">
                <input type="text" name="username" placeholder="اسم المستخدم" required class="edit-input">
                <input type="password" name="password" placeholder="كلمة المرور" required class="edit-input">
                <select name="role" class="edit-input">
                    <option value="admin">مسؤول (Admin)</option>
                    <option value="super-admin">مسؤول خارق (Super)</option>
                </select>
                <button type="submit" name="add_user" class="btn-action btn-update" style="margin-top:5px">إضافة عضو</button>
            </form>
            <table>
                <?php foreach($users as $u): ?>
                <tr style="border-bottom: 1px solid var(--border)">
                    <td style="padding: 15px;"><i class="fa-solid fa-user-shield"></i> <?php echo $u['username']; ?></td>
                    <td><span style="font-size:0.8rem; color:var(--muted)"><?php echo $u['role']; ?></span></td>
                    <td><button class="btn-action btn-danger" style="padding: 8px 15px; font-size:0.7rem">حذف</button></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
        <?php endif; ?>

        <?php if($action === 'coupons'): ?>
        <div class="data-card">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px">
                <h2>إدارة كوبونات الخصم</h2>
                <button class="btn-action btn-update" onclick="document.getElementById('couponModal').style.display='block'">+ إضافة كوبون</button>
            </div>
            <table>
                <tr>
                    <th>الكود</th>
                    <th>النوع</th>
                    <th>القيمة</th>
                    <th>الاستخدام</th>
                    <th>الصلاحية</th>
                    <th>الحالة</th>
                    <th>إجراءات</th>
                </tr>
                <?php if($coupons) foreach($coupons as $c): ?>
                <tr style="border-bottom: 1px solid var(--border)">
                    <td style="padding: 15px; font-weight:bold; letter-spacing:1px; color:var(--primary)"><?php echo $c['code']; ?></td>
                    <td><?php echo $c['discount_type'] === 'percentage' ? 'نسبة مئوية' : 'مبلغ ثابت'; ?></td>
                    <td><?php echo $c['discount_value']; ?> <?php echo $c['discount_type'] === 'percentage' ? '%' : 'ر.س'; ?></td>
                    <td><?php echo $c['used_count']; ?> / <?php echo $c['max_uses'] > 0 ? $c['max_uses'] : '∞'; ?></td>
                    <td><?php echo $c['valid_until']; ?></td>
                    <td><span class="status-label" style="background:<?php echo $c['status']=='active'?'#28a745':'#dc3545'; ?>; padding:5px 10px; border-radius:10px; font-size:0.8rem"><?php echo $c['status']; ?></span></td>
                    <td>
                        <form method="POST" onsubmit="return confirm('هل أنت متأكد؟');">
                            <input type="hidden" name="coupon_id" value="<?php echo $c['id']; ?>">
                            <button type="submit" name="delete_coupon" class="btn-action btn-danger" style="padding: 8px 15px; font-size:0.7rem">حذف</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
        <?php endif; ?>

        <?php if($action === 'reviews'): ?>
        <div class="data-card">
            <h2>إدارة تقييمات العملاء</h2>
            <table>
                <tr>
                    <th>العميل</th>
                    <th>التقييم</th>
                    <th>التعليق</th>
                    <th>التاريخ</th>
                    <th>الحالة</th>
                    <th>إجراءات</th>
                </tr>
                <?php if($reviews) foreach(array_reverse($reviews) as $r): ?>
                <tr style="border-bottom: 1px solid var(--border)">
                    <td style="padding: 15px;"><?php echo $r['name']; ?></td>
                    <td style="color:#FFD700"><?php for($i=0;$i<($r['rating']??5);$i++) echo '⭐'; ?></td>
                    <td style="max-width:300px; font-size:0.85rem; color:var(--text-dim)"><?php echo $r['comment'] ?? ''; ?></td>
                    <td><small><?php echo isset($r['date']) ? date('Y/m/d', strtotime($r['date'])) : ''; ?></small></td>
                    <td>
                        <span style="background:<?php echo ($r['status']??'')=='approved'?'#28a745':'#f39c12'; ?>; padding:5px 10px; border-radius:10px; font-size:0.75rem">
                            <?php echo ($r['status']??'') == 'approved' ? 'منشور' : 'بانتظار الموافقة'; ?>
                        </span>
                    </td>
                    <td>
                        <form method="POST" style="display:flex; gap:5px">
                            <input type="hidden" name="review_id" value="<?php echo $r['id']; ?>">
                            <?php if(($r['status']??'') !== 'approved'): ?>
                            <button type="submit" name="update_review" value="1" onclick="this.form.appendChild(document.createElement('input')).type='hidden'; this.form.lastChild.name='new_status'; this.form.lastChild.value='approved';" class="btn-action btn-update" style="padding:5px 10px; font-size:0.7rem">موافقة</button>
                            <?php endif; ?>
                            <button type="submit" name="update_review" value="1" onclick="this.form.appendChild(document.createElement('input')).type='hidden'; this.form.lastChild.name='new_status'; this.form.lastChild.value='delete'; return confirm('حذف؟');" class="btn-action btn-danger" style="padding:5px 10px; font-size:0.7rem">حذف</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
        <?php endif; ?>

        <?php if($action === 'customers'): ?>
        <div class="data-card">
            <h2>إدارة العملاء ونظام الولاء</h2>
            <table>
                <tr>
                    <th>العميل</th>
                    <th>البريد الإلكتروني</th>
                    <th>نقاط الولاء</th>
                    <th>تاريخ التسجيل</th>
                    <th>إجراءات</th>
                </tr>
                <?php if($customers) foreach(array_reverse($customers) as $c): ?>
                <?php if(($c['role'] ?? '') === 'super-admin') continue; ?>
                <tr style="border-bottom: 1px solid var(--border)">
                    <td style="padding: 15px;"><i class="fa-solid fa-user"></i> <?php echo $c['name']; ?></td>
                    <td><?php echo $c['email']; ?></td>
                    <td><span style="color:var(--primary); font-weight:900"><i class="fa-solid fa-gift"></i> <?php echo $c['points'] ?? 0; ?> نقطة</span></td>
                    <td><small><?php echo date('Y/m/d', strtotime($c['created_at'])); ?></small></td>
                    <td>
                        <button class="btn-action btn-danger" style="padding: 8px 15px; font-size:0.7rem">حظر</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
        <?php endif; ?>
        
        <?php if($action === 'addons'): ?>
        <div class="data-card">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px">
                <h2>إدارة الملحقات (Add-ons)</h2>
                <button class="btn-action btn-update" onclick="document.getElementById('addonModal').style.display='block'">+ إضافة ملحق</button>
            </div>
            <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap:20px;">
                <?php if($addons) foreach($addons as $a): ?>
                <div style="background:var(--glass); padding:20px; border-radius:20px; border:1px solid var(--border); text-align:center;">
                    <img src="<?php echo $a['image']; ?>" style="width:60px; height:60px; border-radius:50%; object-fit:cover; margin-bottom:10px;">
                    <h4><?php echo $a['name']; ?></h4>
                    <p style="color:var(--primary); font-weight:bold; margin:10px 0;"><?php echo $a['price']; ?> ر.س</p>
                    <div style="display:flex; gap:5px;">
                        <button type="button" class="btn-action" style="flex:1; background:var(--primary); color:#000; font-size:0.8rem" onclick='openEditAddonModal(<?php echo json_encode($a); ?>)'>تعديل</button>
                        <form method="POST" onsubmit="return confirm('هل أنت متأكد؟');" style="flex:1;">
                            <input type="hidden" name="addon_id" value="<?php echo $a['id']; ?>">
                            <button type="submit" name="delete_addon" class="btn-action btn-danger" style="width:100%; font-size:0.8rem">حذف</button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if($action === 'settings'): ?>
        <div class="data-card">
            <h2>إعدادات المتجر</h2>
            <form method="POST" style="display:grid; gap:20px;">
                <div>
                    <label style="display:block; margin-bottom:8px; font-size:0.9rem; color:var(--text-dim)">اسم المتجر</label>
                    <input type="text" name="store_name" class="edit-input" value="<?php echo $settings['store_name']; ?>" placeholder="اسم المتجر" required>
                </div>
                
                <div>
                    <label style="display:block; margin-bottom:8px; font-size:0.9rem; color:var(--text-dim)">رقم الواتساب (مع كود الدولة)</label>
                    <input type="text" name="store_phone" class="edit-input" value="<?php echo $settings['store_phone']; ?>" placeholder="+966561365655" required>
                </div>
                
                <div>
                    <label style="display:block; margin-bottom:8px; font-size:0.9rem; color:var(--text-dim)">حالة المتجر</label>
                    <select name="store_status" class="edit-input">
                        <option value="open" <?php echo $settings['store_status'] === 'open' ? 'selected' : ''; ?>>مفتوح</option>
                        <option value="closed" <?php echo $settings['store_status'] === 'closed' ? 'selected' : ''; ?>>مغلق مؤقتاً</option>
                    </select>
                </div>
                
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px;">
                    <div>
                        <label style="display:block; margin-bottom:8px; font-size:0.9rem; color:var(--text-dim)">حساب الإنستقرام</label>
                        <input type="text" name="instagram" class="edit-input" value="<?php echo $settings['instagram']; ?>" placeholder="@winter_sessions">
                    </div>
                    <div>
                        <label style="display:block; margin-bottom:8px; font-size:0.9rem; color:var(--text-dim)">حساب السناب شات</label>
                        <input type="text" name="snapchat" class="edit-input" value="<?php echo $settings['snapchat']; ?>" placeholder="@winter_sessions">
                    </div>
                </div>
                
                <div>
                    <label style="display:block; margin-bottom:8px; font-size:0.9rem; color:var(--text-dim)">اللون الأساسي (Hex)</label>
                    <div style="display:flex; gap:10px; align-items:center;">
                        <input type="color" name="primary_color" value="<?php echo $settings['primary_color']; ?>" style="width:60px; height:50px; border-radius:10px; border:1px solid var(--border); background:transparent; cursor:pointer;">
                        <input type="text" name="primary_color" class="edit-input" value="<?php echo $settings['primary_color']; ?>" placeholder="#c9a050" style="flex:1;">
                    </div>
                </div>
                
                <div style="display:flex; align-items:center; gap:15px; padding:15px; background:var(--glass); border-radius:15px;">
                    <input type="checkbox" name="snow_enabled" id="snow_toggle" <?php echo $settings['snow_enabled'] ? 'checked' : ''; ?> style="width:20px; height:20px; cursor:pointer;">
                    <label for="snow_toggle" style="cursor:pointer; font-size:0.95rem;">تفعيل تأثير الثلج في الموقع</label>
                </div>
                
                <button type="submit" name="update_settings" class="btn-action btn-update" style="margin-top:20px; width:100%; background:var(--primary); color:#000; padding:18px; font-size:1.1rem;">💾 حفظ جميع الإعدادات</button>
            </form>
        </div>
        <?php endif; ?>

    </div>

    <!-- Add Product Modal -->
    <div id="addModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.95); z-index:1000; backdrop-filter:blur(10px); overflow-y: auto; padding: 20px 0;">
        <div style="background:var(--bg-surface); width:95%; max-width:600px; margin:20px auto; padding:40px; border-radius:30px; border:1px solid var(--border); position: relative;">
            <button onclick="document.getElementById('addModal').style.display='none'" style="position: absolute; left: 20px; top: 20px; background: none; border: none; color: var(--text-dim); cursor: pointer; font-size: 1.5rem;"><i class="fa-solid fa-xmark"></i></button>
            <h2 style="margin-bottom:30px">إضافة جلسة جديدة</h2>
            <form method="POST" enctype="multipart/form-data">
                <div style="margin-bottom:15px">
                    <label style="font-size:0.8rem; color:var(--muted)">اختر صورة من جهازك:</label>
                    <input type="file" name="image_file" class="edit-input" accept="image/*">
                </div>
                <input type="text" name="image_url" placeholder="أو ضع رابط صورة جاهز" class="edit-input" style="margin-bottom:15px">
                <input type="text" name="name" placeholder="اسم الجلسة" required class="edit-input" style="margin-bottom:15px">
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px; margin-bottom:15px">
                    <input type="number" name="price_regular" placeholder="السعر العادي" required class="edit-input">
                    <input type="number" name="price_weekend" placeholder="سعر الويكند" required class="edit-input">
                </div>
                <textarea name="description" placeholder="وصف الجلسة" class="edit-input" style="margin-bottom:15px; height:100px"></textarea>
                <input type="text" name="ar_url" placeholder="رابط الـ 3D (اختياري)" class="edit-input" style="margin-bottom:15px">
                <div style="display:flex; gap:10px; margin-top:20px">
                    <button type="submit" name="add_product" class="btn-action btn-update" style="flex:1">حفظ الجلسة</button>
                    <button type="button" class="btn-action" style="background:#222; color:#fff" onclick="document.getElementById('addModal').style.display='none'">إلغاء</button>
                </div>
            </form>
        </div>
    </div>
    <!-- Add Coupon Modal -->
    <div id="couponModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.95); z-index:1000; backdrop-filter:blur(10px); overflow-y: auto; padding: 20px 0;">
        <div style="background:var(--bg-surface); width:95%; max-width:600px; margin:20px auto; padding:40px; border-radius:30px; border:1px solid var(--border); position: relative;">
            <button onclick="document.getElementById('couponModal').style.display='none'" style="position: absolute; left: 20px; top: 20px; background: none; border: none; color: var(--text-dim); cursor: pointer; font-size: 1.5rem;"><i class="fa-solid fa-xmark"></i></button>
            <h2 style="margin-bottom:30px">إضافة كوبون خصم</h2>
            <form method="POST">
                <input type="text" name="code" placeholder="كود الخصم (مثل: NEW2026)" required class="edit-input" style="margin-bottom:15px; text-transform: uppercase;">
                
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px; margin-bottom:15px">
                    <select name="discount_type" class="edit-input">
                        <option value="percentage">نسبة مئوية (%)</option>
                        <option value="fixed">مبلغ ثابت (ر.س)</option>
                    </select>
                    <input type="number" name="discount_value" placeholder="قيمة الخصم" required class="edit-input">
                </div>
                
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px; margin-bottom:15px">
                    <input type="number" name="min_order" placeholder="الحد الأدنى للطلب" value="0" class="edit-input">
                    <input type="number" name="max_uses" placeholder="أقصى عدد للاستخدام (0 = غير محدود)" value="0" class="edit-input">
                </div>

                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px; margin-bottom:15px">
                    <div>
                        <label style="display:block; margin-bottom:5px; font-size:0.8rem; color:var(--text-dim)">يبدأ من:</label>
                        <input type="date" name="valid_from" required class="edit-input">
                    </div>
                    <div>
                        <label style="display:block; margin-bottom:5px; font-size:0.8rem; color:var(--text-dim)">ينتهي في:</label>
                        <input type="date" name="valid_until" required class="edit-input">
                    </div>
                </div>

                <div style="display:flex; gap:10px; margin-top:20px">
                    <button type="submit" name="add_coupon" class="btn-action btn-update" style="flex:1">حفظ الكوبون</button>
                    <button type="button" class="btn-action" style="background:#222; color:#fff" onclick="document.getElementById('couponModal').style.display='none'">إلغاء</button>
                </div>
            </form>
        </div>
    </div>
    <!-- Add Add-on Modal -->
    <div id="addonModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.95); z-index:1000; backdrop-filter:blur(10px); padding-top: 100px;">
        <div style="background:var(--bg-surface); width:95%; max-width:500px; margin:0 auto; padding:40px; border-radius:30px; border:1px solid var(--border); position: relative;">
            <button onclick="document.getElementById('addonModal').style.display='none'" style="position: absolute; left: 20px; top: 20px; background: none; border: none; color: var(--text-dim); cursor: pointer; font-size: 1.5rem;"><i class="fa-solid fa-xmark"></i></button>
            <h2 style="margin-bottom:30px">إضافة ملحق جديد</h2>
            <form method="POST">
                <input type="text" name="name" placeholder="اسم الملحق (مثال: كيس فحم)" required class="edit-input" style="margin-bottom:15px">
                <input type="number" name="price" placeholder="السعر" required class="edit-input" style="margin-bottom:15px">
                <input type="text" name="image_url" placeholder="رابط صورة الملحق" required class="edit-input" style="margin-bottom:15px">
                
                <div style="display:flex; gap:10px; margin-top:20px">
                    <button type="submit" name="add_addon" class="btn-action btn-update" style="flex:1">حفظ الملحق</button>
                    <button type="button" class="btn-action" style="background:#222; color:#fff" onclick="document.getElementById('addonModal').style.display='none'">إلغاء</button>
                </div>
            </form>
        </div>
    </div>
    <!-- Edit Product Modal -->
    <div id="editProductModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.95); z-index:1000; backdrop-filter:blur(10px); overflow-y: auto; padding: 20px 0;">
        <div style="background:var(--bg-surface); width:95%; max-width:600px; margin:20px auto; padding:40px; border-radius:30px; border:1px solid var(--border); position: relative;">
            <button onclick="document.getElementById('editProductModal').style.display='none'" style="position: absolute; left: 20px; top: 20px; background: none; border: none; color: var(--text-dim); cursor: pointer; font-size: 1.5rem;"><i class="fa-solid fa-xmark"></i></button>
            <h2 style="margin-bottom:30px">تعديل الجلسة</h2>
            <form method="POST">
                <input type="hidden" name="id" id="editProdId">
                <div style="margin-bottom:15px">
                    <label style="font-size:0.8rem; color:var(--muted)">رابط الصورة:</label>
                    <input type="text" name="image_url" id="editProdImage" class="edit-input">
                </div>
                <input type="text" name="name" id="editProdName" placeholder="اسم الجلسة" required class="edit-input" style="margin-bottom:15px">
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px; margin-bottom:15px">
                    <input type="number" name="price_regular" id="editProdPriceReg" placeholder="السعر العادي" required class="edit-input">
                    <input type="number" name="price_weekend" id="editProdPriceWeek" placeholder="سعر الويكند" required class="edit-input">
                </div>
                <div style="margin-bottom:15px">
                    <label style="font-size:0.8rem; color:var(--muted)">الحالة:</label>
                    <select name="status" id="editProdStatus" class="edit-input">
                        <option value="available">متاحة</option>
                        <option value="booked">محجوزة</option>
                        <option value="maintenance">تحت الصيانة</option>
                    </select>
                </div>
                <input type="text" name="badge" id="editProdBadge" placeholder="كلمة تمييز (مثل: الأكثر طلباً)" class="edit-input" style="margin-bottom:15px">
                <textarea name="description" id="editProdDesc" placeholder="وصف الجلسة" class="edit-input" style="margin-bottom:15px; height:100px"></textarea>
                <input type="text" name="ar_url" id="editProdAr" placeholder="رابط الـ 3D" class="edit-input" style="margin-bottom:15px">
                
                <div style="display:flex; gap:10px; margin-top:20px">
                    <button type="submit" name="update_product" class="btn-action btn-update" style="flex:1">حفظ التغييرات</button>
                    <button type="button" class="btn-action" style="background:#222; color:#fff" onclick="document.getElementById('editProductModal').style.display='none'">إلغاء</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Addon Modal -->
    <div id="editAddonModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.95); z-index:1000; backdrop-filter:blur(10px); padding-top: 100px;">
        <div style="background:var(--bg-surface); width:95%; max-width:500px; margin:0 auto; padding:40px; border-radius:30px; border:1px solid var(--border); position: relative;">
            <button onclick="document.getElementById('editAddonModal').style.display='none'" style="position: absolute; left: 20px; top: 20px; background: none; border: none; color: var(--text-dim); cursor: pointer; font-size: 1.5rem;"><i class="fa-solid fa-xmark"></i></button>
            <h2 style="margin-bottom:30px">تعديل الملحق</h2>
            <form method="POST">
                <input type="hidden" name="addon_id" id="editAddonId">
                <input type="text" name="name" id="editAddonName" placeholder="اسم الملحق" required class="edit-input" style="margin-bottom:15px">
                <input type="number" name="price" id="editAddonPrice" placeholder="السعر" required class="edit-input" style="margin-bottom:15px">
                <input type="text" name="image_url" id="editAddonImage" placeholder="رابط صورة الملحق" required class="edit-input" style="margin-bottom:15px">
                
                <div style="display:flex; gap:10px; margin-top:20px">
                    <button type="submit" name="update_addon" class="btn-action btn-update" style="flex:1">حفظ التعديلات</button>
                    <button type="button" class="btn-action" style="background:#222; color:#fff" onclick="document.getElementById('editAddonModal').style.display='none'">إلغاء</button>
                </div>
            </form>
        </div>
    </div>

    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>
    <button class="mobile-toggle" onclick="toggleSidebar()">
        <i class="fa-solid fa-bars"></i>
    </button>

    <script>
        function toggleSidebar() {
            document.querySelector('.sidebar').classList.toggle('active');
            document.getElementById('sidebarOverlay').classList.toggle('active');
        }

        function openEditProductModal(p) {
            document.getElementById('editProdId').value = p.id;
            document.getElementById('editProdName').value = p.name;
            document.getElementById('editProdPriceReg').value = p.price_regular;
            document.getElementById('editProdPriceWeek').value = p.price_weekend;
            document.getElementById('editProdStatus').value = p.status || 'available';
            document.getElementById('editProdBadge').value = p.badge || '';
            document.getElementById('editProdDesc').value = p.description || '';
            document.getElementById('editProdImage').value = p.image;
            document.getElementById('editProdAr').value = p.ar_url || '';
            document.getElementById('editProductModal').style.display = 'block';
        }

        function openEditAddonModal(a) {
            document.getElementById('editAddonId').value = a.id;
            document.getElementById('editAddonName').value = a.name;
            document.getElementById('editAddonPrice').value = a.price;
            document.getElementById('editAddonImage').value = a.image;
            document.getElementById('editAddonModal').style.display = 'block';
        }
    </script>
</body>
</html>
