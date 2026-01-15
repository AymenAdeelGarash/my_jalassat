<?php
require_once '../config.php';
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$date = $input['date'] ?? null;
$product_id = $input['product_id'] ?? null;

if (!$date) {
    echo json_encode(['booked_times' => []]);
    exit;
}

$bookings = getData('bookings');
$booked_times = [];

// 1. Get booked ranges from database
foreach ($bookings as $booking) {
    if ($booking['date'] === $date && ($booking['status'] ?? 'pending') !== 'cancelled') {
        if (!$product_id || $booking['product_id'] == $product_id) {
            $booked_times[] = [
                'start' => $booking['time'], // Start Time
                'end' => $booking['end_time'] ?? date('H:i', strtotime($booking['time']) + 3600), // Default to 1 hour if not set
                'product_id' => $booking['product_id']
            ];
        }
    }
}

// 2. Disable past times if date is today
$current_date = date('Y-m-d');
$current_time_hour = (int)date('H'); 

if ($date === $current_date) {
    // We don't block range; we just return a "past" marker for frontend to handle
    // OR we block the hours.
    // Ideally, frontend validates that Start Time > Current Time.
    // Let's keep logic simple: Return intervals.
}

echo json_encode(['booked_intervals' => $booked_times, 'current_server_hour' => $current_time_hour]);
?>
