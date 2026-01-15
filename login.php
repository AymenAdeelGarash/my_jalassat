<?php
require_once 'config.php';

// Redirect if already logged in
if (isset($_SESSION['customer_logged_in'])) {
    redirect('account.php');
}

$settings = getSettings();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <title>تسجيل الدخول | <?php echo $settings['store_name']; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;500;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style_v2.css?v=<?php echo time(); ?>">
    <script src="https://accounts.google.com/gsi/client" async defer></script>
    <style>
        body {
            background: #050608;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
        }
        .login-container {
            width: 100%;
            max-width: 450px;
            padding: 20px;
        }
        .auth-card {
            background: #0c0e12;
            border: 1px solid var(--border);
            border-radius: 30px;
            padding: 40px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.5);
        }
        .logo-box {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo-box .logo-text {
            font-size: 2rem;
            font-weight: 900;
            color: #fff;
        }
        .logo-box .logo-text span { color: var(--primary); }
        
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 700; color: #fff; }
        .form-group input {
            width: 100%;
            padding: 15px;
            background: #050608;
            border: 1px solid #222;
            color: #fff;
            border-radius: 15px;
            font-family: 'Tajawal';
        }
        .form-group input:focus { border-color: var(--primary); outline: none; }
    </style>
</head>
<body>

    <div class="login-container">
        <div class="logo-box">
            <a href="index.php" style="text-decoration:none;">
                <span class="logo-text">
                    <?php 
                        $name_parts = explode(' ', $settings['store_name']);
                        echo htmlspecialchars($name_parts[0]); 
                    ?>
                    <span><?php echo isset($name_parts[1]) ? htmlspecialchars($name_parts[1]) : ''; ?></span>
                </span>
            </a>
        </div>

        <div class="auth-card">
            <!-- Login Form -->
            <div id="loginForm">
                <h3 style="text-align:center; margin-bottom:30px; font-size:1.8rem;">تسجيل الدخول</h3>
                <div id="loginStatus" style="display:none; padding:10px; border-radius:10px; margin-bottom:20px; text-align:center; font-size:0.9rem;"></div>
                
                <div class="form-group">
                    <label>البريد الإلكتروني أو اسم المستخدم:</label>
                    <input type="text" id="loginEmail" placeholder="أدخل بياناتك هنا">
                </div>
                
                <div class="form-group">
                    <label>كلمة المرور:</label>
                    <input type="password" id="loginPassword" placeholder="••••••••">
                </div>
                
                <button onclick="handleLogin()" class="btn btn-primary" style="width:100%; margin-bottom:20px;">دخول</button>
                
                <!-- Google Login Button -->
                <div style="margin-bottom: 25px;">
                    <div style="display:flex; align-items:center; gap:10px; margin-bottom:20px; color:var(--text-dim);">
                        <div style="flex:1; height:1px; background:var(--border);"></div>
                        <span style="font-size:0.8rem;">أو عبر</span>
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
                        data-width="370">
                    </div>
                </div>

                <p style="text-align:center; font-size:0.9rem; color:var(--text-dim);">ليس لديك حساب؟ <a href="javascript:void(0)" onclick="toggleAuthMode('register')" style="color:var(--primary); text-decoration:none;">سجل الآن</a></p>
            </div>

            <!-- Register Form -->
            <div id="registerForm" style="display:none;">
                <h3 style="text-align:center; margin-bottom:30px; font-size:1.8rem;">حساب جديد</h3>
                <div id="regStatus" style="display:none; padding:10px; border-radius:10px; margin-bottom:20px; text-align:center; font-size:0.9rem;"></div>
                
                <div class="form-group">
                    <label>الاسم الكامل:</label>
                    <input type="text" id="regName" placeholder="أدخل اسمك الكريم">
                </div>
                
                <div class="form-group">
                    <label>البريد الإلكتروني أو اسم المستخدم:</label>
                    <input type="text" id="regEmail" placeholder="أدخل ماتريد استخدامه للدخول">
                </div>
                
                <div class="form-group">
                    <label>كلمة المرور:</label>
                    <input type="password" id="regPassword" placeholder="••••••••">
                </div>
                
                <button onclick="handleRegister()" class="btn btn-primary" style="width:100%; margin-bottom:20px;">إنشاء حساب</button>
                <p style="text-align:center; font-size:0.9rem; color:var(--text-dim);">لديك حساب بالفعل؟ <a href="javascript:void(0)" onclick="toggleAuthMode('login')" style="color:var(--primary); text-decoration:none;">دخول</a></p>
            </div>
        </div>

        <div style="text-align:center; margin-top:30px;">
            <a href="index.php" style="color:var(--text-dim); text-decoration:none; font-size:0.9rem;"><i class="fa-solid fa-arrow-right"></i> العودة للمتجر</a>
        </div>
    </div>

    <script>
        const STORE_PHONE = "<?php echo $settings['store_phone']; ?>";
        const ALL_PRODUCTS = []; 
        const ALL_ADDONS = [];
    </script>
    <script src="assets/js/main.js"></script>
    <script>
        // Override toggle for this specific page structure
        function toggleAuthMode(mode) {
            if (mode === 'register') {
                document.getElementById('loginForm').style.display = 'none';
                document.getElementById('registerForm').style.display = 'block';
            } else {
                document.getElementById('loginForm').style.display = 'block';
                document.getElementById('registerForm').style.display = 'none';
            }
        }

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
</body>
</html>
