<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once 'config.php';
$settings = getSettings();
$products = getData('products');
$addons = getData('addons');
$orders = getData('orders');

// Calculate Popular Products (Ordered in last 48 hours)
$popular_ids = [];
if ($orders) {
    foreach ($orders as $order) {
        $order_time = isset($order['date']) ? strtotime($order['date']) : 0;
        if (time() - $order_time < 172800) { // 48 hours
            if (isset($order['item_ids'])) {
                $popular_ids = array_merge($popular_ids, $order['item_ids']);
            }
        }
    }
}
$popular_ids = array_unique($popular_ids);

$is_weekend = isWeekend();

// Get current customer data if logged in
$current_cust = null;
if (isset($_SESSION['customer_id'])) {
    $customers = getData('customers');
    foreach ($customers as $c) {
        if ($c['id'] == $_SESSION['customer_id']) {
            $current_cust = $c;
            break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <title>جلسات شتوية | أفخم الجلسات الشتوية في الرياض</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <!-- SEO Meta Tags - Optimized for Google -->
    <meta name="description" content="جلسات شتوية - وجهتك الأولى لأفخم الجلسات الخارجية في الرياض. نتميز بتصاميم ملكية فريدة تلبي تطلعاتك. احجز جلستك الشتوية الآن وعش رفاهية الشتاء.">
    <meta name="keywords" content="جلسات شتوية، جلسات شتويه، جلسات خارجية الرياض، تأجير جلسات شتوية، جلسات شتوية ملكية، جلسات شتوية مودرن">
    <meta name="author" content="جلسات شتوية">
    
    <!-- Open Graph for Social Sharing -->
    <meta property="og:title" content="<?php echo $settings['store_name']; ?> | فخامة الشتاء">
    <meta property="og:description" content="عش متعة الشتاء بطابع ملكي مع أفخم الجلسات الشتوية في الرياض.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="http://galassatshtawih.rf.gd/">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;600;900&family=Tajawal:wght@300;500;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style_v2.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="assets/css/animations.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="assets/css/info-section.css?v=<?php echo time(); ?>">
    
    <style>
        :root {
            --primary: <?php echo $settings['primary_color']; ?>;
        }
        /* Extra Winter Aesthetics */
        .snow-container {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            pointer-events: none; z-index: 1;
        }
        .snow { color: #fff; font-size: 1rem; position: absolute; top: -20px; animation: snowFall 10s linear infinite; opacity: 0.6; }
        @keyframes snowFall { 
            0% { transform: translateY(0) rotate(0deg); } 
            100% { transform: translateY(100vh) rotate(360deg); } 
        }
        /* FORCE VISUAL FIXES */
        .hero {
            /* Authentic Desert Fire Scene */
            background: url('https://images.unsplash.com/photo-1478131143081-80f7f84ca84d?q=80&w=1200&auto=format&fit=crop') center center/cover no-repeat !important;
            background-attachment: fixed !important;
            position: relative;
        }
        
        .hero-video-overlay {
            /* Dark Premium Gradient (Not Yellow) */
            background: linear-gradient(to bottom, rgba(0,0,0,0.3), rgba(0,0,0,0.8)) !important;
            z-index: 1 !important;
        }

        .hero-content {
            position: relative;
            z-index: 10 !important; /* Force text on top */
            opacity: 1 !important;
            visibility: visible !important;
        }

        .hero-content h1, .hero-content p, .hero-badge {
            text-shadow: 0 2px 10px rgba(0,0,0,0.8);
            color: #fff !important;
        }

        @media (max-width: 768px) {
            .hero { background-attachment: scroll !important; }
        }
    </style>
</head>
<body class="<?php echo $settings['store_status'] === 'closed' ? 'store-closed' : ''; ?>">
    
    <?php if($settings['snow_enabled']): ?>
    <div class="snow-container" id="snowContainer"></div>
    <?php endif; ?>

    <div id="loader" class="loader-wrapper">
        <div class="luxury-loader"></div>
        <p class="loader-text">نجهز لك دفء الشتاء...</p>
    </div>

    <nav class="glass-nav">
        <div class="container">
            <div class="nav-content">
                <div class="logo">
                    <span class="logo-text">
                        <?php 
                            $name_parts = explode(' ', $settings['store_name']);
                            echo htmlspecialchars($name_parts[0]); 
                        ?>
                        <span><?php echo isset($name_parts[1]) ? htmlspecialchars($name_parts[1]) : ''; ?></span>
                    </span>
                </div>
                <div class="nav-links desktop-only">
                    <a href="#products">المجموعات</a>
                    <a href="#how-it-works">كيفية الحجز</a>
                    <a href="#contact">اتصل بنا</a>
                </div>
                <div class="nav-actions">
                    <?php if(isset($_SESSION['customer_logged_in'])): ?>
                        <a href="account.php" class="nav-auth-btn"><i class="fa-solid fa-user-circle"></i> حسابي</a>
                    <?php else: ?>
                        <button onclick="openAuthModal()" class="nav-auth-btn"><i class="fa-solid fa-right-to-bracket"></i> دخول</button>
                    <?php endif; ?>
                    
                    <div class="cart-trigger" id="cartTrigger">
                        <i class="fa-solid fa-moon"></i>
                        <span class="cart-count">0</span>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <header class="hero">
        <div class="hero-video-overlay"></div>
        <div class="container hero-content">
            <div class="hero-badge">موسم شتاء 2026</div>
            <h1>عِش متعة الـ <span class="gradient-text">جلسات شتوية</span> بطابع ملكي</h1>
            <p أفخم الجلسات الشتوية في الرياض، مجهزة بالكامل لتناسب ذوقك الرفيع دكتور يحي.</p>
            <div class="hero-btns">
                <a href="#products" class="btn btn-primary">عرض التشكيلة</a>
                <a href="https://wa.me/<?php echo str_replace('+', '', $settings['store_phone']); ?>" class="btn btn-outline">استشارة سريعة</a>
            </div>
        </div>
    </header>



    <?php if($settings['store_status'] === 'closed'): ?>
    <div class="closed-banner">
        <i class="fa-solid fa-lock"></i> المتجر مغلق مؤقتاً لتجهيز طلبيات جديدة. نعود قريباً!
    </div>
    <?php endif; ?>

    <section id="products" class="products-section">
        <div class="container">
            <div class="section-header">
                <h2>اختر جلستك المثالية</h2>
                
                <!-- Category Filters -->
                <div class="category-filters" style="display:flex; justify-content:center; gap:10px; margin-top:20px; overflow-x:auto; padding-bottom:10px;">
                    <button class="filter-btn active" onclick="filterByCategory('all')">الكل</button>
                    <button class="filter-btn" onclick="filterByCategory('sessions')">جلسات</button>
                    <button class="filter-btn" onclick="filterByCategory('tents')">خيام</button>
                </div>

                <div class="booking-day-selector" style="margin-top: 25px;">
                    <label style="display:block; margin-bottom:10px; font-weight:700; color:var(--primary)">اختر يوم الحجز لتحديد السعر:</label>
                    <div class="day-btns">
                        <button class="day-btn <?php echo !$is_weekend ? 'active' : ''; ?>" onclick="setBookingDay('regular')">أيام الأسبوع (السبت - الأربعاء)</button>
                        <button class="day-btn <?php echo $is_weekend ? 'active' : ''; ?>" onclick="setBookingDay('weekend')">الويكند (الخميس - الجمعة)</button>
                    </div>
                </div>
            </div>

            <div class="products-grid">
                <?php 
                if (empty($products)) {
                    echo '<div style="color:white; text-align:center; grid-column:1/-1; padding:50px;">
                            <h3>عذراً، لا توجد جلسات متاحة حالياً للعرض.</h3>
                            <p>يرجى التأكد من ملف products.json</p>
                          </div>';
                }
                foreach ($products as $product): 
                    if (!isset($product['id'])) continue; // Skip malformed products
                    
                    $price_reg = isset($product['price_regular']) ? (int)$product['price_regular'] : (isset($product['price']) ? (int)$product['price'] : 0);
                    $price_end = isset($product['price_weekend']) ? (int)$product['price_weekend'] : $price_reg;
                    
                    $display_price = $is_weekend ? $price_end : $price_reg;
                    $is_popular = in_array($product['id'], $popular_ids);
                ?>
                <div class="product-card <?php echo ($product['status'] ?? 'available') !== 'available' ? 'is-reserved' : ''; ?>" data-id="<?php echo $product['id']; ?>" data-category="<?php echo $product['category'] ?? 'sessions'; ?>">
                    <div class="product-image" onclick="openProductDetail(<?php echo $product['id']; ?>)" style="cursor: pointer;">
                        <img src="<?php echo $product['image'] ?? ''; ?>" alt="<?php echo $product['name'] ?? ''; ?>">
                        <?php if ($is_popular): ?>
                        <div class="social-badge popularity-badge">مطلوب الآن 🔥</div>
                        <?php elseif (!empty($product['badge'])): ?>
                        <div class="social-badge"><?php echo htmlspecialchars($product['badge']); ?></div>
                        <?php endif; ?>
                        <div class="product-overlay">
                            <span class="view-details">عرض التفاصيل <i class="fa-solid fa-arrow-left"></i></span>
                        </div>
                    </div>
                    <div class="product-info">
                        <h3 onclick="openProductDetail(<?php echo $product['id']; ?>)" style="cursor: pointer;"><?php echo htmlspecialchars($product['name'] ?? 'جلسة غير مسمى'); ?></h3>
                        <p><?php echo htmlspecialchars($product['description'] ?? ''); ?></p>
                        <div class="price-row">
                            <span class="price" id="price-<?php echo $product['id']; ?>" 
                                  data-regular="<?php echo $price_reg; ?>" 
                                  data-weekend="<?php echo $price_end; ?>">
                                <?php echo $display_price; ?> <small>ر.س</small>
                            </span>
                            <?php if (($product['status'] ?? 'available') === 'available' && ($settings['store_status'] ?? 'open') === 'open'): ?>
                            <button class="add-to-cart" onclick="triggerAddToCart(<?php echo $product['id']; ?>)">
                                <i class="fa-solid fa-plus"></i>
                            </button>
                            <?php else: ?>
                            <span class="status-label"><?php echo ($product['status'] ?? '') === 'reserved' ? 'تم الحجز' : (($settings['store_status'] ?? 'open') === 'closed' ? 'مغلق' : 'مباع'); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>


    <!-- Cart Sidebar -->
    <div id="cartSidebar" class="cart-sidebar">
        <div class="cart-header">
            <h3>سلتك الخاصة</h3>
            <button id="closeCart"><i class="fa-solid fa-xmark"></i></button>
        </div>

        <div class="cart-scrollable-content" style="flex:1; overflow-y:auto; padding:0 20px;">
            <div id="cartItems" class="cart-items"></div>

            <!-- Luxury Add-ons Section (Restored) -->
            <div class="cart-addons" style="padding: 15px 0; border-top: 1px solid var(--border); border-bottom: 1px solid var(--border);">
                <h4 style="font-size: 0.9rem; margin-bottom: 15px; color: var(--primary);"><i class="fa-solid fa-sparkles"></i> تجهيزات شتوية إضافية:</h4>
                <div style="display: flex; gap: 10px; overflow-x: auto; padding-bottom: 5px;">
                    <?php 
                    if(isset($addons) && is_array($addons)):
                    foreach($addons as $addon):
                    ?>
                    <div class="addon-card" onclick="addAddonToCartById('<?php echo $addon['id']; ?>')" style="min-width: 120px; background: rgba(255,255,255,0.05); padding: 10px; border-radius: 12px; text-align: center; cursor: pointer; border: 1px solid transparent; transition: 0.3s; flex-shrink: 0;">
                        <img src="<?php echo $addon['image']; ?>" style="width: 40px; height: 40px; object-fit: cover; border-radius: 50%; margin-bottom: 5px;">
                        <h5 style="font-size: 0.75rem; margin-bottom: 3px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo $addon['name']; ?></h5>
                        <p style="font-size: 0.8rem; color: var(--primary);"><?php echo $addon['price']; ?> ر.س</p>
                    </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>
            
            <!-- Upsell Section (JS Dynamic - kept for compatibility) -->

            <!-- Coupon Section -->
            <div class="cart-options" style="padding: 15px 0 15px 0;">
                <label style="display:block; margin-bottom:5px; font-size:0.9rem;">كود الخصم:</label>
                <div style="display:flex; gap:5px;">
                    <input type="text" id="couponCode" placeholder="أدخل الكود" style="flex:1; padding:10px; border-radius:10px; border:1px solid #333; background:#111; color:#fff;">
                    <button onclick="applyCoupon()" style="background:var(--primary); color:#000; border:none; padding:0 15px; border-radius:10px; cursor:pointer; font-weight:bold;">تطبيق</button>
                </div>
                <p id="couponMsg" style="font-size:0.8rem; margin-top:5px; display:none;"></p>
            </div>
            
            <!-- Date Selection -->
            <div class="cart-options" style="padding: 15px 0; border-top: 1px solid var(--border);">
                <div style="display: grid; grid-template-columns: 1.5fr 1fr; gap: 10px;">
                    <div>
                        <label style="display:block; margin-bottom:5px; font-size:0.9rem;">تاريخ الحجز:</label>
                        <input type="date" id="bookingDate" class="form-input" style="width:100%; padding:10px; border-radius:10px; border:1px solid #333; background:#111; color:#fff;" required>
                    </div>
                    <div>
                        <label style="display:block; margin-bottom:5px; font-size:0.9rem;">وقت الوصول:</label>
                        <select id="bookingTime" class="form-input" style="width:100%; padding:10px; border-radius:10px; border:1px solid #333; background:#111; color:#fff;" required>
                            <option value="">اختر..</option>
                            <!-- Morning -->
                            <option value="01:00">01:00 ص</option>
                            <option value="02:00">02:00 ص</option>
                            <option value="03:00">03:00 ص</option>
                            <option value="04:00">04:00 ص</option>
                            <option value="05:00">05:00 ص</option>
                            <option value="06:00">06:00 ص</option>
                            <option value="07:00">07:00 ص</option>
                            <option value="08:00">08:00 ص</option>
                            <option value="09:00">09:00 ص</option>
                            <option value="10:00">10:00 ص</option>
                            <option value="11:00">11:00 ص</option>
                            <option value="12:00">12:00 م</option>
                            <!-- Evening -->
                            <option value="13:00">01:00 م</option>
                            <option value="14:00">02:00 م</option>
                            <option value="15:00">03:00 م</option>
                            <option value="16:00">04:00 م</option>
                            <option value="17:00">05:00 م</option>
                            <option value="18:00">06:00 م</option>
                            <option value="19:00">07:00 م</option>
                            <option value="20:00">08:00 م</option>
                            <option value="21:00">09:00 م</option>
                            <option value="22:00">10:00 م</option>
                            <option value="23:00">11:00 م</option>
                            <option value="00:00">12:00 ص</option>
                            <option value="01:00">01:00 ص (متأخر)</option>
                            <option value="02:00">02:00 ص (متأخر)</option>
                            <option value="03:00">03:00 ص (متأخر)</option>
                        </select>
                    </div>
                </div>
                <!-- End Time -->
                <div style="margin-top:10px;">
                    <label style="display:block; margin-bottom:5px; font-size:0.9rem;">إلى الساعة (وقت المغادرة):</label>
                    <select id="bookingEndTime" class="form-input" style="width:100%; padding:10px; border-radius:10px; border:1px solid #333; background:#111; color:#fff;">
                        <option value="">- أختر -</option>
                        <option value="18:00">06:00 م</option>
                        <option value="19:00">07:00 م</option>
                        <option value="20:00">08:00 م</option>
                        <option value="21:00">09:00 م</option>
                        <option value="22:00">10:00 م</option>
                        <option value="23:00">11:00 م</option>
                        <option value="00:00">12:00 ص</option>
                        <option value="01:00">01:00 ص</option>
                        <option value="02:00">02:00 ص</option>
                        <option value="03:00">03:00 ص</option>
                        <option value="04:00">04:00 ص</option>
                        <option value="05:00">05:00 ص</option>
                        <option value="06:00">06:00 ص (نهاية الدوام)</option>
                    </select>
                </div>
                
                <div style="margin-top:10px;">
                    <label style="display:block; margin-bottom:5px; font-size:0.9rem;">الموقع (حي / شارع / لوكيشن):</label>
                    <input type="text" id="bookingLocation" class="form-input" style="width:100%; padding:10px; border-radius:10px; border:1px solid #333; background:#111; color:#fff;" placeholder="مثال: الرياض، حي النرجس..." value="<?php echo htmlspecialchars($current_cust['location'] ?? ''); ?>" required>
                    <?php if(isset($_SESSION['customer_logged_in'])): ?>
                    <p style="font-size:0.75rem; color:var(--primary); margin-top:5px;"><i class="fa-solid fa-info-circle"></i> يتم حفظ موقعك تلقائياً لطلباتك القادمة.</p>
                    <?php endif; ?>
                </div>
            </div> <!-- Close cart-options from line 268 -->
        </div> <!-- Close cart-scrollable-content from line 235 -->

        <div class="cart-footer">
            <div class="total-row"><span>الإجمالي المقدر:</span><span id="cartTotal">0 ر.س</span></div>
            <button class="btn btn-checkout" id="checkoutBtn"><i class="fa-brands fa-whatsapp"></i> تأكيد الطلب والموقع</button>
        </div>
    </div>

    <!-- AR Modal -->
    <div id="arModal" class="modal">
        <div class="modal-content">
            <button class="close-modal" onclick="closeAR()">&times;</button>
            <model-viewer id="arViewer" src="" ar ar-modes="webxr scene-viewer quick-look" camera-controls shadow-intensity="1" auto-rotate></model-viewer>
        </div>
    </div>

    <!-- Gallery Modal -->
    <div id="galleryModal" class="modal" style="display:none; position:fixed; z-index:9999; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.95);">
        <button class="close-modal" onclick="closeGallery()" style="position:absolute; top:20px; right:30px; color:#fff; font-size:40px; background:none; border:none; cursor:pointer;">&times;</button>
        
        <div class="gallery-content" style="display:flex; flex-direction:column; align-items:center; justify-content:center; height:100%; padding:20px;">
            <div class="gallery-main-image" style="max-width:90%; max-height:70vh; margin-bottom:20px;">
                <img id="galleryMainImage" src="" style="width:100%; height:100%; object-fit:contain; border-radius:10px;">
            </div>
            
            <div class="gallery-thumbs" id="galleryThumbs" style="display:flex; gap:10px; overflow-x:auto; max-width:90%; padding-bottom:10px;">
                <!-- Thumbnails will be injected here -->
            </div>
            
            <div class="gallery-controls" style="margin-top:20px;">
                <button onclick="prevImage()" style="background:var(--primary); border:none; padding:10px 20px; border-radius:50%; margin:0 10px; cursor:pointer;"><i class="fa-solid fa-chevron-right"></i></button>
                <button onclick="nextImage()" style="background:var(--primary); border:none; padding:10px 20px; border-radius:50%; margin:0 10px; cursor:pointer;"><i class="fa-solid fa-chevron-left"></i></button>
            </div>
        </div>
    </div>

    <!-- Product Detail Modal -->
    <div id="productDetailModal" class="modal" style="display:none; position:fixed; z-index:9999; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.9);">
        <div class="modal-content detail-modal" style="max-width: 900px; background: #0c0e12; border: 1px solid var(--border); border-radius: 24px; padding: 0; overflow: hidden; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 90%;">
            <button class="close-modal" onclick="closeProductDetail()" style="position: absolute; top: 20px; right: 20px; z-index: 10;">&times;</button>
            <div class="detail-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));">
                <div class="detail-image" style="height: 400px; position: relative;">
                    <img id="detailMainImg" src="" style="width: 100%; height: 100%; object-fit: cover;">
                </div>
                <div class="detail-info" style="padding: 40px;">
                    <div id="detailBadge" class="hero-badge" style="margin-bottom: 15px;"></div>
                    <h2 id="detailTitle" style="font-size: 2rem; margin-bottom: 20px; color: var(--primary);"></h2>
                    <p id="detailLongDesc" style="color: var(--text-dim); line-height: 1.8; margin-bottom: 30px; font-size: 1.1rem;"></p>
                    <div class="detail-features" style="display: flex; gap: 15px; margin-bottom: 30px;">
                        <span style="background: rgba(255,255,255,0.05); padding: 8px 15px; border-radius: 10px; font-size: 0.9rem;"><i class="fa-solid fa-users"></i> عائلي</span>
                        <span style="background: rgba(255,255,255,0.05); padding: 8px 15px; border-radius: 10px; font-size: 0.9rem;"><i class="fa-solid fa-fire"></i> شبة نار</span>
                    </div>
                    <div style="display: flex; gap: 15px; align-items: center;">
                        <div id="detailPrice" style="font-size: 1.5rem; font-weight: 900;"></div>
                        <button id="detailAddBtn" class="btn btn-primary" onclick="" style="flex: 1;">إضافة للسلة</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <section id="about" class="about-section" style="position: relative; overflow: hidden;">
        <!-- Background decoration -->
        <div style="position: absolute; top: -100px; left: -100px; width: 300px; height: 300px; background: var(--primary); opacity: 0.05; filter: blur(100px); border-radius: 50%;"></div>
        
        <div class="container">
            <div class="about-content">
                <div class="about-text">
                    <div class="hero-badge">قصتنا</div>
                    <h2 style="font-size: 2.5rem; line-height: 1.2;">نبذة عن <br><span class="gradient-text" style="font-size: 3rem;">جلسات شتوية</span></h2>
                    <p style="font-size: 1.1rem; color: var(--text-dim); line-height: 1.8; margin-bottom: 40px;">
                        نحن فريق سعودي شغوف بابتكار تجارب شتوية استثنائية في قلب الرياض. <br>
                        نجمع بين دفء التراث العريق وفخامة التصميم المعاصر لنقدم لكم جلسات ملكية تحول لياليكم الباردة إلى ذكريات دافئة لا تُنسى.
                    </p>
                    
                    <div class="about-features-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 20px;">
                        <div class="feature-card" style="background: rgba(255,255,255,0.03); padding: 20px; border-radius: 15px; border: 1px solid var(--border); text-align: center;">
                            <i class="fa-solid fa-gem" style="font-size: 1.5rem; color: var(--primary); margin-bottom: 10px;"></i>
                            <h4 style="margin: 0;">جودة ملكية</h4>
                        </div>
                        <div class="feature-card" style="background: rgba(255,255,255,0.03); padding: 20px; border-radius: 15px; border: 1px solid var(--border); text-align: center;">
                            <i class="fa-solid fa-bolt" style="font-size: 1.5rem; color: var(--primary); margin-bottom: 10px;"></i>
                            <h4 style="margin: 0;">تنفيذ سريع</h4>
                        </div>
                        <div class="feature-card" style="background: rgba(255,255,255,0.03); padding: 20px; border-radius: 15px; border: 1px solid var(--border); text-align: center;">
                            <i class="fa-solid fa-map-location-dot" style="font-size: 1.5rem; color: var(--primary); margin-bottom: 10px;"></i>
                            <h4 style="margin: 0;">تغطية الرياض</h4>
                        </div>
                    </div>
                </div>
                <div class="about-image" style="position: relative;">
                    <div style="position: absolute; inset: 0; background: linear-gradient(45deg, var(--primary) 0%, transparent 100%); opacity: 0.1; border-radius: 40px; z-index: 1;"></div>
                    <img src="https://images.unsplash.com/photo-1478131143081-80f7f84ca84d?q=80&w=1000&auto=format&fit=crop" alt="Winter Sessions Premium" style="width: 100%; border-radius: 40px; box-shadow: 0 20px 50px rgba(0,0,0,0.5); border: 1px solid var(--border);">
                </div>
            </div>
        </div>
    </section>

    <!-- How to Book Steps -->
    <section id="how-it-works" class="steps-section" style="padding: 100px 0; background: var(--bg-dark);">
        <div class="container">
            <div class="section-header">
                <h2>كيف أحجز جلستي؟</h2>
                <p>خطوات بسيطة لتستمتع بليلة شتوية دافئة</p>
            </div>
            <div class="steps-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 30px; margin-top: 50px;">
                <div class="step-card" style="background: rgba(255,255,255,0.03); padding: 40px; border-radius: 20px; text-align: center; border: 1px solid var(--border);">
                    <div class="step-icon" style="width: 60px; height: 60px; background: var(--primary); color: #000; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; font-weight: 900; font-size: 1.5rem;">1</div>
                    <h3>اختر جلستك</h3>
                    <p style="color: var(--text-dim); margin-top: 15px;">تصفح المجموعات واختر الجلسة التي تناسب ذوقك وعدد ضيوفك.</p>
                </div>
                <div class="step-card" style="background: rgba(255,255,255,0.03); padding: 40px; border-radius: 20px; text-align: center; border: 1px solid var(--border);">
                    <div class="step-icon" style="width: 60px; height: 60px; background: var(--primary); color: #000; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; font-weight: 900; font-size: 1.5rem;">2</div>
                    <h3>أكد الحجز</h3>
                    <p style="color: var(--text-dim); margin-top: 15px;">حدد التاريخ والوقت في السلة، ثم أرسل الطلب عبر الواتساب.</p>
                </div>
                <div class="step-card" style="background: rgba(255,255,255,0.03); padding: 40px; border-radius: 20px; text-align: center; border: 1px solid var(--border);">
                    <div class="step-icon" style="width: 60px; height: 60px; background: var(--primary); color: #000; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; font-weight: 900; font-size: 1.5rem;">3</div>
                    <h3>استمتع بالشتاء</h3>
                    <p style="color: var(--text-dim); margin-top: 15px;">سيقوم فريقنا بتجهيز كل شيء في موقعك المختار لراحتك التامة.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Location & Info Section (Moved) -->
    <section class="info-section">
        <div class="container">
            <div class="info-grid">
                <div class="info-card">
                    <div class="info-icon"><i class="fa-solid fa-location-dot"></i></div>
                    <h3>الموقع</h3>
                    <p style="margin-bottom:10px">وادي لبن - تحت الجسر المعلق (داخل مزرعة)</p>
                    <a href="https://maps.app.goo.gl/LMPN3ASNrxNXsRak8?g_st=iw" target="_blank" style="color:var(--primary); text-decoration:underline; font-size:0.9rem;">عرض الموقع على الخريطة <i class="fa-solid fa-up-right-from-square"></i></a>
                </div>

                <div class="info-card">
                    <div class="info-icon"><i class="fa-solid fa-clock"></i></div>
                    <h3>أوقات العمل</h3>
                    <p>يومياً من 5:00 عصراً حتى 6:00 صباحاً</p>
                </div>

                <div class="info-card">
                    <div class="info-icon"><i class="fa-solid fa-users"></i></div>
                    <h3>ملاحظة مهمة</h3>
                    <p>الجلسات للعائلات فقط (نعتذر من العزاب)</p>
                </div>
            </div>

            <div class="amenities-section">
                <h3><i class="fa-solid fa-star"></i> عرضنا يشمل (مجاناً مع الجلسة)</h3>
                <div class="amenities-grid">
                    <div class="amenity-item"><i class="fa-solid fa-video"></i> بروجكتر للأفلام</div>
                    <div class="amenity-item"><i class="fa-solid fa-volume-high"></i> سماعة صوت نقي</div>
                    <div class="amenity-item"><i class="fa-solid fa-fire"></i> منقل للنار والدفا</div>
                    <div class="amenity-item"><i class="fa-solid fa-campground"></i> رواق خارجي للخصوصية</div>
                    <div class="amenity-item"><i class="fa-solid fa-restroom"></i> دورة مياه متوفرة</div>
                    <div class="amenity-item"><i class="fa-solid fa-hand-holding-droplet"></i> ضيافة (ماء + مناديل)</div>
                </div>
                <div style="text-align:center; margin-top:20px; color:var(--text-dim); font-size:0.9rem;">
                    <i class="fa-solid fa-mug-hot"></i> متوفر قهوة وشاي (يحتسب سعرها خارج قيمة الحجز)
                </div>

                <div style="background:rgba(201,160,80,0.1); border:1px solid var(--primary); padding:20px; border-radius:15px; margin-top:30px; text-align:center;">
                    <h4 style="color:var(--primary); margin-bottom:10px; font-size:1.1rem;"><i class="fa-solid fa-tags"></i> أسعار الجلسات</h4>
                    <div style="display:flex; justify-content:center; gap:30px; flex-wrap:wrap;">
                        <div>
                            <span style="display:block; font-weight:bold; font-size:1.2rem;">400 ر.س</span>
                            <small style="color:var(--text-dim)">أيام الأسبوع (أو 50 للساعة)</small>
                        </div>
                        <div style="border-right:1px solid var(--border); padding-right:30px;">
                            <span style="display:block; font-weight:bold; font-size:1.2rem;">500 ر.س</span>
                            <small style="color:var(--text-dim)">الويكند</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="booking-terms">
                <div class="terms-box">
                    <i class="fa-solid fa-circle-info"></i>
                    <div>
                        <h4>شروط الحجز</h4>
                        <p>يُطلب دفع عربون 50% من قيمة الحجز لتأكيد الموعد، والباقي عند الاستلام</p>
                        
                        <div class="bank-details">
                            <h5><i class="fa-solid fa-building-columns"></i> معلومات الحساب البنكي</h5>
                            <div class="bank-info-grid">
                                <div class="bank-item">
                                    <span class="bank-label">البنك:</span>
                                    <span class="bank-value">الراجحي</span>
                                </div>
                                <div class="bank-item">
                                    <span class="bank-label">الاسم:</span>
                                    <span class="bank-value">مؤسسة قمة الجلسات للتسويق الالكتروني</span>
                                </div>
                                <div class="bank-item">
                                    <span class="bank-label">رقم الآيبان:</span>
                                    <span class="bank-value" id="iban">SA8680000147608010162461</span>
                                    <button class="copy-btn" onclick="copyToClipboard('iban')"><i class="fa-solid fa-copy"></i></button>
                                </div>
                                <div class="bank-item">
                                    <span class="bank-label">رقم الحساب:</span>
                                    <span class="bank-value" id="account">147608010162461</span>
                                    <button class="copy-btn" onclick="copyToClipboard('account')"><i class="fa-solid fa-copy"></i></button>
                                </div>
                            </div>
                            <p class="bank-note"><i class="fa-solid fa-bell"></i> العربون نصف المبلغ، والباقي عند الوصول.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Reviews Section -->
    <section class="reviews-section" style="padding: 60px 0; background: var(--bg-surface);">
        <div class="container">
            <div class="section-header">
                <h2>ماذا يقول عملاؤنا</h2>
                <p>تجارب حقيقية لعملاء عاشوا التجربة</p>
            </div>
            
            <div class="reviews-grid" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap:20px; margin-top:30px;">
                <?php 
                $all_reviews = getData('reviews');
                $reviews = array_filter($all_reviews, fn($r) => isset($r['status']) && $r['status'] === 'approved');
                
                if(empty($reviews)) {
                    echo '<div style="grid-column: 1/-1; text-align:center; padding:40px; border:1px dashed var(--border); border-radius:15px; opacity:0.6;">
                            <i class="fa-solid fa-comments" style="font-size:2rem; margin-bottom:15px; display:block;"></i>
                            كن أول من يشاركنا تجربته! المراجعات تظهر بعد موافقة الإدارة.
                          </div>';
                } else {
                    foreach(array_slice($reviews, 0, 3) as $review): 
                ?>
                <div class="review-card" style="background:rgba(255,255,255,0.02); padding:30px; border-radius:25px; border:1px solid var(--border); transition:0.3s; position:relative;">
                    <div style="color:#FFD700; margin-bottom:15px; font-size:0.8rem;">
                        <?php for($i=0; $i<$review['rating']; $i++) echo '<i class="fa-solid fa-star"></i>'; ?>
                    </div>
                    <p style="margin-bottom:20px; line-height:1.6; font-style:italic;">"<?php echo htmlspecialchars($review['comment']); ?>"</p>
                    <div style="display:flex; align-items:center; gap:10px;">
                        <div style="width:30px; height:30px; background:var(--primary); color:#000; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:900; font-size:0.7rem;">
                            <?php echo mb_substr($review['name'], 0, 1, 'UTF-8'); ?>
                        </div>
                        <h4 style="color:var(--text-main); font-size:0.9rem; font-weight:700;"><?php echo htmlspecialchars($review['name']); ?></h4>
                    </div>
                </div>
                <?php endforeach; } ?>
            </div>

            <!-- Submit Review Form -->
            <div class="submit-review-form" style="margin-top:50px; background:rgba(255,255,255,0.03); padding:30px; border-radius:20px; border:1px solid var(--border); max-width:600px; margin-left:auto; margin-right:auto;">
                <h3 style="margin-bottom:20px; text-align:center;">شاركنا تجربتك</h3>
                <div id="reviewStatus" style="display:none; padding:10px; border-radius:10px; margin-bottom:20px; text-align:center;"></div>
                <form id="reviewForm" onsubmit="submitReview(event)">
                    <div style="margin-bottom:20px;">
                        <label style="display:block; margin-bottom:8px; font-weight:700; font-size:0.9rem;">اسمك الكريم:</label>
                        <input type="text" id="revName" placeholder="أدخل اسمك هنا..." required style="width:100%; padding:15px; background:#050608; border:1px solid #222; color:#fff; border-radius:15px; font-family:'Tajawal'; focus:border-color:var(--primary);">
                    </div>
                    <div style="margin-bottom:20px;">
                        <label style="display:block; margin-bottom:8px; font-weight:700; font-size:0.9rem;">كيف كانت التجربة؟</label>
                        <select id="revRating" style="width:100%; padding:15px; background:#050608; border:1px solid #222; color:#fff; border-radius:15px; font-family:'Tajawal';">
                            <option value="5">⭐⭐⭐⭐⭐ ممتاز جداً</option>
                            <option value="4">⭐⭐⭐⭐ جيد جداً</option>
                            <option value="3">⭐⭐⭐ متوسط</option>
                        </select>
                    </div>
                    <div style="margin-bottom:25px;">
                        <label style="display:block; margin-bottom:8px; font-weight:700; font-size:0.9rem;">تعليقك (اختياري):</label>
                        <textarea id="revComment" placeholder="أخبرنا عن أكثر شيء أعجبك..." required style="width:100%; padding:15px; background:#050608; border:1px solid #222; color:#fff; border-radius:15px; height:120px; font-family:'Tajawal'; resize:none;"></textarea>
                    </div>
                    <button type="submit" id="submitReviewBtn" class="btn btn-primary" style="width:100%;">إرسال التقييم</button>
                </form>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer id="contact">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-brand">
                    <h2><?php echo $settings['store_name']; ?></h2>
                    <p>نجعل ليالي الشتاء في الرياض ذكرى لا تُنسى بأفخم التصاميم الملكية.</p>
                    <div class="footer-links" style="margin-top:20px;">
                        <a href="terms.php" style="color:var(--text-dim); text-decoration:none; margin-left:15px; font-size:0.9rem;">الشروط والأحكام</a>
                    </div>
                </div>
                <div class="footer-social">
                    <a href="https://wa.me/message/5VPYJE6JGUJMC1" target="_blank" title="تواصل مع المبرمج"><i class="fa-brands fa-whatsapp-square" style="color:var(--primary)"></i></a>
                    <a href="https://instagram.com/<?php echo str_replace('@', '', $settings['instagram']); ?>"><i class="fa-brands fa-instagram"></i></a>
                    <a href="https://snapchat.com/add/<?php echo str_replace('@', '', $settings['snapchat']); ?>"><i class="fa-brands fa-snapchat"></i></a>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2026 جميع الحقوق محفوظة لـ <?php echo $settings['store_name']; ?></p>
                <p style="margin-top:10px; font-size: 0.7rem; opacity: 0.5;">تم التطوير بواسطة: <a href="https://wa.me/message/5VPYJE6JGUJMC1" style="color:inherit; text-decoration:underline;">AYMEN</a></p>
            </div>
        </div>
    </footer>

    <!-- Customer Auth Modal -->
    <div id="authModal" class="modal" style="display:none; position:fixed; z-index:9999; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); backdrop-filter: blur(8px);">
        <div class="modal-content auth-modal" style="max-width:400px; background:#0c0e12; border:1px solid var(--border); border-radius:24px; padding:40px; position:absolute; top:50%; left:50%; transform:translate(-50%, -50%); width:90%;">
            <button class="close-modal" onclick="closeAuthModal()" style="position:absolute; top:20px; right:20px; font-size:30px; color:#fff; border:none; background:none; cursor:pointer;">&times;</button>
            
            <div id="loginForm">
                <h3 style="text-align:center; margin-bottom:30px; font-size:1.8rem;">تسجيل الدخول</h3>
                <div id="loginStatus" style="display:none; padding:10px; border-radius:10px; margin-bottom:20px; text-align:center; font-size:0.9rem;"></div>
                <div style="margin-bottom:20px;">
                    <label style="display:block; margin-bottom:8px; font-weight:700;">اسم المستخدم أو البريد:</label>
                    <input type="text" id="loginEmail" placeholder="your@email.com" style="width:100%; padding:15px; background:#050608; border:1px solid #222; color:#fff; border-radius:15px;">
                </div>
                <div style="margin-bottom:25px;">
                    <label style="display:block; margin-bottom:8px; font-weight:700;">كلمة المرور:</label>
                    <input type="password" id="loginPassword" placeholder="••••••••" style="width:100%; padding:15px; background:#050608; border:1px solid #222; color:#fff; border-radius:15px;">
                </div>
                <button onclick="handleLogin()" class="btn btn-primary" style="width:100%; margin-bottom:20px;">دخول</button>

                <!-- Google Login Button -->
                <div style="margin-bottom: 25px;">
                    <div style="display:flex; align-items:center; gap:10px; margin-bottom:15px; color:var(--text-dim);">
                        <div style="flex:1; height:1px; background:var(--border);"></div>
                        <span style="font-size:0.7rem;">أو عبر</span>
                        <div style="flex:1; height:1px; background:var(--border);"></div>
                    </div>
                    
                    <div id="g_id_onload"
                        data-client_id="<?php echo $settings['google_client_id']; ?>"
                        data-callback="handleGoogleResponse"
                        data-auto_prompt="false">
                    </div>
                    <div class="g_id_signin"
                        data-type="standard"
                        data-size="large"
                        data-theme="outline"
                        data-text="sign_in_with"
                        data-shape="rectangular"
                        data-logo_alignment="left"
                        data-width="100%">
                    </div>
                </div>

                <p style="text-align:center; font-size:0.9rem; color:var(--text-dim);">ليس لديك حساب؟ <a href="javascript:void(0)" onclick="toggleAuthMode('register')" style="color:var(--primary); text-decoration:none;">سجل الآن</a></p>
            </div>

            <div id="registerForm" style="display:none;">
                <h3 style="text-align:center; margin-bottom:30px; font-size:1.8rem;">حساب جديد</h3>
                <div id="regStatus" style="display:none; padding:10px; border-radius:10px; margin-bottom:20px; text-align:center; font-size:0.9rem;"></div>
                <div style="margin-bottom:20px;">
                    <label style="display:block; margin-bottom:8px; font-weight:700;">الاسم:</label>
                    <input type="text" id="regName" placeholder="الاسم الكريم" style="width:100%; padding:15px; background:#050608; border:1px solid #222; color:#fff; border-radius:15px;">
                </div>
                <div style="margin-bottom:20px;">
                    <label style="display:block; margin-bottom:8px; font-weight:700;">اسم المستخدم أو البريد:</label>
                    <input type="text" id="regEmail" placeholder="أدخل اسم مستخدم أو بريد" style="width:100%; padding:15px; background:#050608; border:1px solid #222; color:#fff; border-radius:15px;">
                </div>
                <div style="margin-bottom:25px;">
                    <label style="display:block; margin-bottom:8px; font-weight:700;">كلمة المرور:</label>
                    <input type="password" id="regPassword" placeholder="••••••••" style="width:100%; padding:15px; background:#050608; border:1px solid #222; color:#fff; border-radius:15px;">
                </div>
                <button onclick="handleRegister()" class="btn btn-primary" style="width:100%; margin-bottom:20px;">إنشاء حساب</button>
                <p style="text-align:center; font-size:0.9rem; color:var(--text-dim);">لديك حساب بالفعل؟ <a href="javascript:void(0)" onclick="toggleAuthMode('login')" style="color:var(--primary); text-decoration:none;">دخول</a></p>
            </div>
        </div>
    </div>

    <script>
        const ALL_PRODUCTS = <?php echo json_encode($products); ?>;
        const ALL_ADDONS = <?php echo json_encode(getData('addons')); ?>;
        let selectedDayType = '<?php echo $is_weekend ? "weekend" : "regular"; ?>';
        const STORE_PHONE = "<?php echo str_replace('+', '', $settings['store_phone']); ?>";
    </script>
    <script src="https://accounts.google.com/gsi/client" async defer></script>
    <script src="assets/js/main.js?v=<?php echo time(); ?>"></script>
    <script>
        async function handleGoogleResponse(response) {
            try {
                const res = await fetch('api/auth_google.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ credential: response.credential })
                });
                const result = await res.json();
                if(result.success) {
                    location.reload();
                } else {
                    alert(result.message);
                }
            } catch(e) { alert('خطأ في الاتصال بقوقل'); }
        }
    </script>
    <script>
        <?php if($settings['snow_enabled']): ?>
        // Magical 3D Snow Simulation
        const canvas = document.createElement('canvas');
        canvas.className = 'snow-container';
        document.body.appendChild(canvas);
        const ctx = canvas.getContext('2d');

        let particles = [];
        const isMobile = window.innerWidth < 768;
        const layers = [
            { count: isMobile ? 15 : 40, size: {min: 4, max: 7}, speed: {min: 1.5, max: 2.5}, opacity: 0.8, blur: 4 }, // Foreground
            { count: isMobile ? 30 : 80, size: {min: 2, max: 4}, speed: {min: 0.8, max: 1.5}, opacity: 0.5, blur: 1 }, // Midground
            { count: isMobile ? 50 : 120, size: {min: 1, max: 2}, speed: {min: 0.3, max: 0.8}, opacity: 0.3, blur: 0 }  // Background
        ];

        function resize() {
            canvas.width = window.innerWidth;
            canvas.height = window.innerHeight;
        }

        window.addEventListener('resize', resize);
        resize();

        class SnowParticle {
            constructor(layer) {
                this.layer = layer;
                this.init();
            }
            init() {
                this.x = Math.random() * canvas.width;
                this.y = Math.random() * canvas.height;
                this.initY = true;
                this.reset();
            }
            reset() {
                if(!this.initY) this.y = -20;
                this.size = Math.random() * (this.layer.size.max - this.layer.size.min) + this.layer.size.min;
                this.speed = Math.random() * (this.layer.speed.max - this.layer.speed.min) + this.layer.speed.min;
                this.velX = (Math.random() - 0.5) * 1;
                this.swing = Math.random() * 3;
                this.swingSpeed = Math.random() * 0.02;
                this.opacity = this.layer.opacity;
                this.initY = false;
            }
            update() {
                this.y += this.speed;
                this.x += this.velX + Math.sin(this.y * this.swingSpeed) * 0.5;
                if (this.y > canvas.height + 10) this.reset();
                if (this.x > canvas.width + 10) this.x = -10;
                if (this.x < -10) this.x = canvas.width + 10;
            }
            draw() {
                ctx.beginPath();
                if (this.layer.blur > 0) {
                    ctx.shadowBlur = this.layer.blur;
                    ctx.shadowColor = 'white';
                }
                ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
                ctx.fillStyle = `rgba(255, 255, 255, ${this.opacity})`;
                ctx.fill();
                ctx.shadowBlur = 0;
            }
        }

        layers.forEach(layer => {
            for (let i = 0; i < layer.count; i++) particles.push(new SnowParticle(layer));
        });

        function animate() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            particles.forEach(p => {
                p.update();
                p.draw();
            });
            requestAnimationFrame(animate);
        }
        animate();
        <?php endif; ?>
    </script>
    <script>
        // Fallback: Force hide loader after 2 seconds if main.js fails
        window.addEventListener('load', function() {
            setTimeout(function() {
                var loader = document.getElementById('loader');
                if(loader) {
                    loader.style.opacity = '0';
                    setTimeout(function(){ loader.style.display = 'none'; }, 500);
                }
            }, 2000);
        });
        // Extra Fallback: Hide after 5 seconds regardless of load event
        setTimeout(function() {
             var loader = document.getElementById('loader');
             if(loader) loader.style.display = 'none';
        }, 5000);
    </script>
    <script>
        // Debug Google Client ID
        console.log("Google Client ID Active: ", "<?php echo $settings['google_client_id']; ?>");
    </script>
</body>
</html>

