<?php
require_once 'config.php';

if (!isset($_SESSION['customer_logged_in'])) {
    redirect('index.php');
}

$settings = getSettings();
$customer_id = $_SESSION['customer_id'];
$customer_name = $_SESSION['customer_name'];

$all_orders = getData('orders');
$my_orders = array_filter($all_orders, function($o) use ($customer_id) {
    return isset($o['customer_id']) && $o['customer_id'] == $customer_id;
});

// Sort by date new to old
usort($my_orders, function($a, $b) {
    return strtotime($b['date']) - strtotime($a['date']);
});

$products = getData('products');
$products_map = [];
foreach($products as $p) $products_map[$p['id']] = $p;

// Get current customer data (for points and other fields)
$customers = getData('customers');
$current_cust = null;
foreach($customers as $c) {
    if($c['id'] == $customer_id) {
        $current_cust = $c;
        break;
    }
}

// Logic for Next Booking Countdown
$next_booking = null;
$now = time();
foreach($my_orders as $o) {
    if(($o['status'] ?? '') === 'cancelled') continue;
    $b_time = strtotime(($o['booking_date'] ?? '') . ' ' . ($o['booking_time'] ?? '00:00'));
    if($b_time > $now) {
        if(!$next_booking || $b_time < strtotime($next_booking['booking_date'] . ' ' . $next_booking['booking_time'])) {
            $next_booking = $o;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <title>حسابي | <?php echo $settings['store_name']; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;500;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style_v2.css?v=<?php echo time(); ?>">
    <style>
        body { background: #050608; }
        .account-hero {
            padding: 100px 0 50px;
            background: linear-gradient(to bottom, rgba(201, 160, 80, 0.05), transparent);
            text-align: center;
        }
        .order-card {
            background: rgba(255,255,255,0.02);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 20px;
            transition: 0.3s;
        }
        .order-card:hover { border-color: var(--primary); transform: translateY(-3px); }
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }
        .status-badge {
            padding: 5px 12px;
            border-radius: 8px;
            font-size: 0.8rem;
            font-weight: 700;
        }
        .status-pending { background: rgba(255, 193, 7, 0.1); color: #ffc107; }
        .status-confirmed { background: rgba(0, 123, 255, 0.1); color: #007bff; }
        .status-completed { background: rgba(40, 167, 69, 0.1); color: #28a745; }
        .status-cancelled { background: rgba(220, 53, 69, 0.1); color: #dc3545; }
        
        .order-items { display: flex; flex-direction: column; gap: 10px; }
        .order-item { display: flex; justify-content: space-between; align-items: center; color: var(--text-dim); }
        
        .empty-history {
            text-align: center;
            padding: 60px 20px;
            border: 2px dashed var(--border);
            border-radius: 30px;
            opacity: 0.5;
        }
    </style>
</head>
<body>
    <nav class="glass-nav" style="background: rgba(5,6,8,0.8);">
        <div class="container">
            <div class="nav-content">
                <a href="index.php" style="text-decoration:none; color:inherit;">
                    <div class="logo">
                        <span class="logo-text"><?php echo $settings['store_name']; ?></span>
                    </div>
                </a>
                <div class="nav-actions">
                    <button onclick="logoutCustomer()" class="nav-auth-btn" style="border-color:#ff4747; color:#ff4747;"><i class="fa-solid fa-right-from-bracket"></i> خروج</button>
                </div>
            </div>
        </div>
    </nav>

    <header class="account-hero">
        <div class="container">
            <div style="display:flex; justify-content:center; gap:20px; margin-bottom:20px;">
                <div class="hero-badge" style="background:var(--primary); color:#000;"><i class="fa-solid fa-gift"></i> نقاطي: <?php echo $current_cust['points'] ?? 0; ?></div>
                <button onclick="document.getElementById('profileModal').style.display='block'" class="hero-badge" style="cursor:pointer; background:rgba(255,255,255,0.1);"><i class="fa-solid fa-user-gear"></i> إعدادات الحساب</button>
            </div>
            <h1>أهلاً بك، <?php echo htmlspecialchars($customer_name); ?></h1>
            <p style="color:var(--primary); font-weight:700;"><?php echo htmlspecialchars($_SESSION['customer_email']); ?></p>
            
            <?php if($next_booking): ?>
            <div id="countdown-box" style="margin-top:30px; background:rgba(201,160,80,0.1); padding:20px; border-radius:15px; border:1px solid var(--primary); display:inline-block;">
                <h4 style="font-size:0.9rem; margin-bottom:10px; color:var(--text-dim)">موعد حجزك القادم بعد:</h4>
                <div id="timer" style="font-size:1.5rem; font-weight:900; letter-spacing:2px;" data-time="<?php echo $next_booking['booking_date'].' '.$next_booking['booking_time']; ?>">-- : -- : --</div>
            </div>
            <?php endif; ?>
        </div>
    </header>

    <section style="padding: 50px 0;">
        <div class="container" style="max-width: 800px;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:30px;">
                <h2 style="font-size:1.5rem;"><i class="fa-solid fa-clock-rotate-left"></i> سجل حجوزاتي</h2>
                <span class="hero-badge"><?php echo count($my_orders); ?> طلبات</span>
            </div>

            <?php if(empty($my_orders)): ?>
                <div class="empty-history">
                    <i class="fa-solid fa-box-open" style="font-size:3rem; margin-bottom:20px; display:block;"></i>
                    <h3>لا توجد حجوزات سابقة حتى الآن</h3>
                    <p style="margin-top:10px;">باشر حجز جلستك الشتوية الأولى الآن!</p>
                    <a href="index.php" class="btn btn-primary" style="margin-top:20px; display:inline-block;">تصفح الجلسات</a>
                </div>
            <?php else: ?>
                <?php foreach($my_orders as $order): ?>
                <div class="order-card">
                    <div class="order-header">
                        <div>
                            <span style="display:block; font-size:0.8rem; color:var(--text-dim);">تاريخ الطلب: <?php echo date('Y/m/d', strtotime($order['date'])); ?></span>
                            <span style="font-weight:700; font-size:1.1rem; color:var(--primary);">#<?php echo substr($order['id'], -6); ?></span>
                        </div>
                        <span class="status-badge status-<?php echo $order['status']; ?>">
                            <?php 
                            $status_ar = [
                                'pending' => 'قيد الانتظار',
                                'confirmed' => 'مؤكد',
                                'completed' => 'مكتمل',
                                'cancelled' => 'ملغي'
                            ];
                            echo $status_ar[$order['status']] ?? $order['status'];
                            ?>
                        </span>
                    </div>
                    
                    <div style="margin-bottom: 20px; background: rgba(255,255,255,0.03); padding: 15px; border-radius: 12px;">
                        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px; font-size:0.9rem;">
                            <span><i class="fa-solid fa-calendar" style="color:var(--primary); margin-left:5px;"></i> حجز ليوم: <?php echo $order['booking_date']; ?></span>
                            <span><i class="fa-solid fa-clock" style="color:var(--primary); margin-left:5px;"></i> من: <?php echo $order['booking_time']; ?> إلى: <?php echo $order['booking_end_time']; ?></span>
                        </div>
                    </div>

                    <div class="order-items">
                        <?php foreach($order['cart'] as $item): ?>
                        <div class="order-item">
                            <span><?php echo $item['name']; ?></span>
                            <span style="font-weight:700;"><?php echo $item['price']; ?> ر.س</span>
                        </div>
                        <?php endforeach; ?>
                        
                        <?php if($order['discount'] > 0): ?>
                        <div class="order-item" style="color:#00c851; border-top: 1px solid rgba(255,255,255,0.05); padding-top:10px; margin-top:5px;">
                            <span>خصم (<?php echo $order['coupon']; ?>)</span>
                            <span>-<?php echo $order['discount']; ?> ر.س</span>
                        </div>
                        <?php endif; ?>

                        <div class="order-item" style="border-top: 1px solid rgba(255,255,255,0.05); padding-top:10px; margin-top:5px; font-weight:900; color:#fff; font-size:1.2rem;">
                            <span>الإجمالي</span>
                            <span style="color:var(--primary);"><?php echo $order['total']; ?> ر.س</span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>

    <!-- Profile Update Modal -->
    <div id="profileModal" class="modal" style="display:none; position:fixed; z-index:9999; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); backdrop-filter: blur(8px);">
        <div class="modal-content" style="max-width:400px; background:#0c0e12; border:1px solid var(--border); border-radius:24px; padding:40px; position:absolute; top:50%; left:50%; transform:translate(-50%, -50%); width:90%;">
            <button class="close-modal" onclick="document.getElementById('profileModal').style.display='none'" style="position:absolute; top:20px; right:20px; font-size:30px; color:#fff; border:none; background:none; cursor:pointer;">&times;</button>
            <h3 style="text-align:center; margin-bottom:30px;">تحديث بيانات الحساب</h3>
            <div id="profileStatus" style="display:none; padding:10px; border-radius:10px; margin-bottom:20px; text-align:center;"></div>
            
            <div style="margin-bottom:20px;">
                <label style="display:block; margin-bottom:8px;">الاسم الجديد:</label>
                <input type="text" id="updateName" value="<?php echo htmlspecialchars($customer_name); ?>" style="width:100%; padding:12px; background:#050608; border:1px solid #222; color:#fff; border-radius:10px;">
            </div>
            <div style="margin-bottom:25px;">
                <label style="display:block; margin-bottom:8px;">كلمة مرور جديدة (اختياري):</label>
                <input type="password" id="updatePass" placeholder="اتركه فارغاً للحفاظ على القديمة" style="width:100%; padding:12px; background:#050608; border:1px solid #222; color:#fff; border-radius:10px;">
            </div>
            <button onclick="updateProfile()" class="btn btn-primary" style="width:100%;">حفظ التغييرات</button>
        </div>
    </div>

    <script src="assets/js/main.js"></script>
    <script>
        // Next Booking Countdown Logic
        const timerEl = document.getElementById('timer');
        if(timerEl) {
            const targetDate = new Date(timerEl.dataset.time).getTime();
            setInterval(() => {
                const now = new Date().getTime();
                const diff = targetDate - now;
                if(diff < 0) {
                    document.getElementById('countdown-box').style.display = 'none';
                    return;
                }
                const days = Math.floor(diff / (1000 * 60 * 60 * 24));
                const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const mins = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                const secs = Math.floor((diff % (1000 * 60)) / 1000);
                timerEl.innerText = `${days}ي ${hours}سا ${mins}د ${secs}ث`;
            }, 1000);
        }

        async function updateProfile() {
            const name = document.getElementById('updateName').value;
            const pass = document.getElementById('updatePass').value;
            const status = document.getElementById('profileStatus');

            try {
                const response = await fetch('api/auth_customer.php?action=update_profile', {
                    method: 'POST',
                    body: JSON.stringify({ name, password: pass })
                });
                const res = await response.json();
                status.innerText = res.message;
                status.style.display = 'block';
                status.style.background = res.success ? 'rgba(0,200,81,0.1)' : 'rgba(255,71,71,0.1)';
                status.style.color = res.success ? '#00c851' : '#ff4747';
                if(res.success) setTimeout(() => location.reload(), 1500);
            } catch(e) { alert('خطأ في الاتصال'); }
        }

        // Logout override
        async function logoutCustomer() {
            await fetch('api/auth_customer.php?action=logout');
            window.location.href = 'index.php';
        }
    </script>
</body>
</html>
