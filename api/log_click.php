<?php
header('Content-Type: application/json');
require_once '../config.php';

$input = json_decode(file_get_contents('php://input'), true);

if (isset($input['product_id'])) {
    $product_id = $input['product_id'];
    $stats = getData('stats');
    $date = date('Y-m-d');
    
    if (!isset($stats[$date])) {
        $stats[$date] = ['clicks' => 0, 'products' => []];
    }
    
    $stats[$date]['clicks']++;
    $stats[$date]['products'][$product_id] = ($stats[$date]['products'][$product_id] ?? 0) + 1;
    
    saveData('stats', $stats);
    echo json_encode(['status' => 'success']);
} else {
    echo json_encode(['status' => 'error']);
}
?>
