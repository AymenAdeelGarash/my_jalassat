<?php
require_once '../config.php';
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$code = $input['code'] ?? '';
$total = $input['total'] ?? 0;

if (!$code) {
    echo json_encode(['valid' => false, 'message' => 'الرجاء إدخال كود الخصم']);
    exit;
}

$coupons = getData('coupons');
$today = date('Y-m-d');
$found = null;

foreach ($coupons as $coupon) {
    if (strtoupper($coupon['code']) === strtoupper($code)) {
        $found = $coupon;
        break;
    }
}

if (!$found) {
    echo json_encode(['valid' => false, 'message' => 'كود الخصم غير صحيح']);
    exit;
}

// Validation logic
if ($found['status'] !== 'active') {
    echo json_encode(['valid' => false, 'message' => 'هذا الكوبون غير فعال']);
    exit;
}

if ($found['valid_until'] < $today) {
    echo json_encode(['valid' => false, 'message' => 'انتهت صلاحية هذا الكوبون']);
    exit;
}

if ($found['max_uses'] > 0 && $found['used_count'] >= $found['max_uses']) {
    echo json_encode(['valid' => false, 'message' => 'تم استنفاد عدد مرات استخدام هذا الكوبون']);
    exit;
}

if ($total < $found['min_order']) {
    echo json_encode(['valid' => false, 'message' => 'الحد الأدنى للطلب لاستخدام هذا الكوبون هو ' . $found['min_order'] . ' ر.س']);
    exit;
}

// Calculate discount
$discount_amount = 0;
if ($found['discount_type'] === 'percentage') {
    $discount_amount = ($total * $found['discount_value']) / 100;
} else {
    $discount_amount = $found['discount_value'];
}

$new_total = $total - $discount_amount;
if ($new_total < 0) $new_total = 0;

echo json_encode([
    'valid' => true,
    'discount_amount' => $discount_amount,
    'new_total' => $new_total,
    'message' => 'تم تطبيق الخصم بنجاح!'
]);
?>
