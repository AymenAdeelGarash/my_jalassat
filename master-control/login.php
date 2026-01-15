<?php
require_once '../config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_input = $_POST['username'] ?? '';
    $pass_input = $_POST['password'] ?? '';

    $users = getData('users');
    $auth_success = false;

    foreach ($users as $u) {
        if ($u['username'] === $user_input && password_verify($pass_input, $u['password'])) {
            $auth_success = true;
            $_SESSION['admin_username'] = $u['username'];
            $_SESSION['admin_role'] = $u['role'] ?? 'admin';
            break;
        }
    }

    if ($auth_success) {
        $_SESSION['admin_logged_in'] = true;
        redirect('index.php');
    } else {
        $error = 'بيانات الدخول غير صحيحة';
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>دخول المسؤولين | جلسات شتوية</title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { 
            background: #050608 url('https://images.unsplash.com/photo-1478131143081-80f7f84ca84d?q=80&w=2000&auto=format&fit=crop') center/cover;
            background-attachment: fixed;
            color: #fff; font-family: 'Tajawal'; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0;
            position: relative;
        }
        body::before {
            content: ''; position: absolute; top:0; left:0; width:100%; height:100%;
            background: radial-gradient(circle, rgba(0,0,0,0.4) 0%, rgba(5,6,8,0.95) 100%);
            z-index: 1;
        }
        .login-card { 
            background: rgba(12, 14, 18, 0.8); backdrop-filter: blur(25px);
            padding: 60px; border-radius: 40px; width: 100%; max-width: 480px; 
            border: 1px solid rgba(255,255,255,0.08); text-align: center; 
            z-index: 10;
            box-shadow: 0 40px 100px rgba(0,0,0,0.8);
        }
        .logo { font-size: 2.5rem; font-weight: 900; margin-bottom: 40px; letter-spacing: -1px; }
        .logo span { color: #c9a050; text-shadow: 0 0 20px rgba(201,160,80,0.4); }
        input { 
            width: 100%; padding: 18px 25px; background: rgba(0,0,0,0.4); border: 1px solid rgba(255,255,255,0.1); 
            border-radius: 20px; color: #fff; margin-bottom: 20px; font-family: 'Tajawal'; font-size: 1rem;
            transition: 0.3s;
        }
        input:focus { border-color: #c9a050; background: rgba(0,0,0,0.6); outline: none; }
        button { 
            width: 100%; padding: 18px; background: #c9a050; border: none; border-radius: 20px; 
            color: #000; font-weight: 900; font-size: 1.2rem; cursor: pointer; transition: 0.4s; 
            margin-top: 10px;
        }
        button:hover { transform: translateY(-5px); box-shadow: 0 15px 30px rgba(201,160,80,0.4); }
        .error { color: #f85149; margin-bottom: 25px; background: rgba(248,81,73,0.1); padding: 10px; border-radius: 10px; font-size: 0.9rem; }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="logo">WINTER <span>ADMIN</span></div>
        <?php if($error) echo "<div class='error'>$error</div>"; ?>
        <form method="POST">
            <input type="text" name="username" placeholder="اسم المستخدم" required>
            <input type="password" name="password" placeholder="كلمة المرور" required>
            <button type="submit">دخول إلى النظام</button>
        </form>
    </div>
</body>
</html>
