<?php
require_once '../config.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if ($data) {
    // 1. Save to orders.json (Historical Record)
    $orders = getData('orders');
    $newOrder = [
        'id' => uniqid(),
        'date' => date('Y-m-d H:i:s'),
        'booking_date' => $data['date'] ?? '',
        'booking_time' => $data['time'] ?? '',
        'booking_end_time' => $data['end_time'] ?? '', // Add end time
        'cart' => $data['cart'],
        'total' => $data['total'],
        'coupon' => $data['coupon'] ?? '',
        'discount' => $data['discount'] ?? 0,
        'customer_id' => $_SESSION['customer_id'] ?? null, // Link to customer if logged in
        'status' => 'pending', // pending, confirmed, completed, cancelled
        'payment_status' => 'unpaid'
    ];
    $orders[] = $newOrder;
    saveData('orders', $orders);

    // 2. Save to bookings.json (Availability Management)
    // We strictly record which products are booked at what time/date
    $bookings = getData('bookings');
    
    foreach ($data['cart'] as $item) {
        $newBooking = [
            'id' => uniqid('bk_'),
            'order_id' => $newOrder['id'],
            'product_id' => $item['id'],
            'date' => $data['date'] ?? '',
            'time' => $data['time'] ?? '', // Booking start time
            'end_time' => $data['end_time'] ?? '', // Booking end time
            'status' => 'pending' // pending bookings also block the slot to avoid race conditions
        ];
        $bookings[] = $newBooking;
    }
    saveData('bookings', $bookings);
    
    // 3. Update customer's saved location if logged in
    if (isset($_SESSION['customer_id']) && !empty($data['location'])) {
        $customers = getData('customers');
        foreach ($customers as &$cust) {
            if ($cust['id'] == $_SESSION['customer_id']) {
                $cust['location'] = $data['location'];
                break;
            }
        }
        saveData('customers', $customers);
    }

    echo json_encode(['success' => true, 'order_id' => $newOrder['id']]);
} else {
    echo json_encode(['status' => 'error']);
}
?>
