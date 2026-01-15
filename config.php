<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('PROJECT_ROOT', __DIR__);
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$dir = str_replace('\\', '/', dirname($_SERVER['PHP_SELF']));
if ($dir === '/') $dir = '';
define('BASE_URL', $protocol . '://' . $_SERVER['HTTP_HOST'] . $dir . '/');

$data_dir = PROJECT_ROOT . '/data';
if (!file_exists($data_dir)) mkdir($data_dir, 0777, true);

// Create products images directory
$upload_dir = PROJECT_ROOT . '/assets/images/products';
if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);

$paths = [
    'products' => $data_dir . '/products.json',
    'orders'   => $data_dir . '/orders.json',
    'stats'    => $data_dir . '/stats.json',
    'settings' => $data_dir . '/settings.json',
    'users'    => $data_dir . '/users.json',
    'coupons'  => $data_dir . '/coupons.json',
    'bookings' => $data_dir . '/bookings.json',
    'reviews'  => $data_dir . '/reviews.json',
    'addons'   => $data_dir . '/addons.json',
    'customers'=> $data_dir . '/customers.json'
];

// Default Settings
$default_settings = [
    'store_name' => 'جلسات شتوية',
    'store_phone' => '+966561365655',
    'store_status' => 'open',
    'instagram' => '@winter_sessions',
    'snapchat' => '@winter_sessions',
    'location_default' => 'الرياض، المملكة العربية السعودية',
    'primary_color' => '#c9a050',
    'snow_enabled' => true,
    'google_client_id' => '48052349972-66esfqafhpjbuiv4kk44pk3ccbqdr5us.apps.googleusercontent.com'
];

// Initialize Files
foreach ($paths as $key => $p) {
    if (!file_exists($p)) {
        if ($key === 'settings') $init = $default_settings;
        elseif ($key === 'users') $init = [['username' => 'admin', 'password' => password_hash('Winter2026', PASSWORD_DEFAULT), 'role' => 'super-admin']];
        else $init = [];
        file_put_contents($p, json_encode($init, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}

// Helpers
function getSettings() {
    global $paths, $default_settings;
    $settings = $default_settings;
    if (file_exists($paths['settings'])) {
        $saved = json_decode(file_get_contents($paths['settings']), true);
        if (is_array($saved)) {
            $settings = array_merge($settings, $saved);
        }
    }
    // FINAL PIXEL-PERFECT SYNC (Matches Screenshot 964 exactly):
    $settings['google_client_id'] = '48052349972-66esfqafhpjbuiv4kk44pk3ccbqdr5us.apps.googleusercontent.com';
    return $settings;
}

function getData($key) {
    global $paths;
    if (!file_exists($paths[$key])) return [];
    return json_decode(file_get_contents($paths[$key]), true) ?: [];
}

function saveData($key, $data) {
    global $paths;
    file_put_contents($paths[$key], json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

function isWeekend() {
    $day = date('l'); // Get current day name
    // Weekend is Thursday and Friday only
    return in_array($day, ['Thursday', 'Friday']);
}

function getAdjustedPrice($price) {
    if (isWeekend()) {
        return round($price * 1.25); // 25% increase for weekends
    }
    return $price;
}

// Analytics: Track product popularity
function logProductView($id) {
    $stats = getData('stats');
    $date = date('Y-m-d');
    
    if (!isset($stats[$date])) {
        $stats[$date] = ['clicks' => 0, 'products' => []];
    }
    
    $stats[$date]['clicks']++;
    $stats[$date]['products'][$id] = ($stats[$date]['products'][$id] ?? 0) + 1;
    
    saveData('stats', $stats);
}

function isAdmin() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

function redirect($url) {
    header("Location: $url");
    exit;
}
?>
