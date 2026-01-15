<?php
require_once '../config.php';
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$name = $input['name'] ?? '';
$comment = $input['comment'] ?? '';
$rating = (int)($input['rating'] ?? 5);

if (empty($name) || empty($comment)) {
    echo json_encode(['success' => false, 'message' => 'جميع الحقول مطلوبة']);
    exit;
}

$reviews = getData('reviews');
$new_review = [
    'id' => uniqid(),
    'name' => htmlspecialchars($name),
    'comment' => htmlspecialchars($comment),
    'rating' => $rating,
    'date' => date('Y-m-d H:i:s'),
    'status' => 'pending' // Admin must approve
];

$reviews[] = $new_review;
saveData('reviews', $reviews);

echo json_encode(['success' => true, 'message' => 'شكراً لتقييمك! سيتم عرضه بعد مراجعة الإدارة.']);
?>
