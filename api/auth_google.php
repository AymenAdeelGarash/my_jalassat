<?php
require_once '../config.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$token = $data['credential'] ?? '';

if (empty($token)) {
    echo json_encode(['success' => false, 'message' => 'No token provided']);
    exit;
}

// Verify Token with Google API
$url = "https://oauth2.googleapis.com/tokeninfo?id_token=" . $token;
$response = file_get_contents($url);
$user_data = json_decode($response, true);

if (!isset($user_data['email'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid Google token']);
    exit;
}

$email = $user_data['email'];
$name = $user_data['name'] ?? 'Google User';
$google_id = $user_data['sub']; // Unique Google ID

$customers = getData('customers');
$found_user = null;

foreach ($customers as &$c) {
    if (isset($c['google_id']) && $c['google_id'] === $google_id) {
        $found_user = &$c;
        break;
    }
    if (isset($c['email']) && strtolower($c['email']) === strtolower($email)) {
        // Link existing email to Google ID
        $c['google_id'] = $google_id;
        $found_user = &$c;
        break;
    }
}

if (!$found_user) {
    // Create new user
    $new_user = [
        'id' => time(),
        'name' => $name,
        'email' => $email,
        'google_id' => $google_id,
        'points' => 0,
        'location' => '',
        'created_at' => date('Y-m-d H:i:s')
    ];
    $customers[] = $new_user;
    $found_user = $new_user;
}

saveData('customers', $customers);

// Set Session
$_SESSION['customer_logged_in'] = true;
$_SESSION['customer_id'] = $found_user['id'];
$_SESSION['customer_name'] = $found_user['name'];
$_SESSION['customer_email'] = $found_user['email'];

echo json_encode(['success' => true, 'message' => 'تم تسجيل الدخول بواسطة Google بنجاح']);
