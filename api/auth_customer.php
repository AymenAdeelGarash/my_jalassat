<?php
require_once '../config.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

if ($action === 'register') {
    $data = json_decode(file_get_contents('php://input'), true);
    $name = trim($data['name'] ?? '');
    $email = trim($data['email'] ?? ''); // Flexible identifier (username/email)
    $password = $data['password'] ?? '';

    if (empty($name) || empty($email) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'يرجى ملء كافة الحقول']);
        exit;
    }

    if (strlen($password) < 6) {
        echo json_encode(['success' => false, 'message' => 'كلمة المرور يجب أن لا تقل عن 6 خانات']);
        exit;
    }

    $customers = getData('customers');
    
    // Check if identifier exists
    foreach ($customers as $c) {
        if (isset($c['email']) && strtolower($c['email']) === strtolower($email)) {
            echo json_encode(['success' => false, 'message' => 'اسم المستخدم أو البريد الإلكتروني مسجل مسبقاً']);
            exit;
        }
    }

    $new_customer = [
        'id' => time(),
        'name' => $name,
        'email' => $email,
        'points' => 0,
        'location' => '',
        'password' => password_hash($password, PASSWORD_DEFAULT),
        'created_at' => date('Y-m-d H:i:s')
    ];

    $customers[] = $new_customer;
    saveData('customers', $customers);

    $_SESSION['customer_logged_in'] = true;
    $_SESSION['customer_id'] = $new_customer['id'];
    $_SESSION['customer_name'] = $new_customer['name'];
    $_SESSION['customer_email'] = $new_customer['email'];

    echo json_encode(['success' => true, 'message' => 'تم التسجيل بنجاح']);
} 

elseif ($action === 'login') {
    $data = json_decode(file_get_contents('php://input'), true);
    $email = trim($data['email'] ?? '');
    $password = $data['password'] ?? '';

    if (empty($email) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'يرجى إدخال اسم المستخدم وكلمة المرور']);
        exit;
    }

    $customers = getData('customers');
    foreach ($customers as $c) {
        if (isset($c['email']) && strtolower($c['email']) === strtolower($email) && password_verify($password, $c['password'])) {
            $_SESSION['customer_logged_in'] = true;
            $_SESSION['customer_id'] = $c['id'];
            $_SESSION['customer_name'] = $c['name'];
            $_SESSION['customer_email'] = $c['email'];

            echo json_encode(['success' => true, 'message' => 'تم تسجيل الدخول بنجاح', 'redirect' => 'account.php']);
            exit;
        }
    }

    // NEW: Check if this is an Admin login
    $admin_users = getData('users');
    foreach ($admin_users as $u) {
        if (strtolower($u['username']) === strtolower($email) && password_verify($password, $u['password'])) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_id'] = time();
            $_SESSION['admin_username'] = $u['username'];
            
            echo json_encode(['success' => true, 'message' => 'مرحباً بك أيها المدير!', 'redirect' => 'master-control/index.php']);
            exit;
        }
    }

    echo json_encode(['success' => false, 'message' => 'البيانات المدخلة غير صحيحة']);
} 

elseif ($action === 'update_profile') {
    if (!isset($_SESSION['customer_logged_in'])) exit;
    
    $data = json_decode(file_get_contents('php://input'), true);
    $new_name = trim($data['name'] ?? '');
    $new_pass = $data['password'] ?? '';
    $cid = $_SESSION['customer_id'];

    $customers = getData('customers');
    $found = false;
    foreach ($customers as &$c) {
        if ($c['id'] == $cid) {
            if (!empty($new_name)) $c['name'] = $new_name;
            if (!empty($new_pass)) $c['password'] = password_hash($new_pass, PASSWORD_DEFAULT);
            
            $_SESSION['customer_name'] = $c['name'];
            $found = true;
            break;
        }
    }

    if ($found) {
        saveData('customers', $customers);
        echo json_encode(['success' => true, 'message' => 'تم تحديث البيانات بنجاح']);
    } else {
        echo json_encode(['success' => false, 'message' => 'حدث خطأ']);
    }
}

elseif ($action === 'logout') {
    unset($_SESSION['customer_logged_in']);
    unset($_SESSION['customer_id']);
    unset($_SESSION['customer_name']);
    unset($_SESSION['customer_email']); // Fixed from customer_phone
    echo json_encode(['success' => true]);
} 

else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
