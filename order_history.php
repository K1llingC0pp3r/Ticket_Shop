<?php
session_start();
header('Content-Type: application/json');
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("
        SELECT o.id AS order_id, o.total_price, o.created_at, o.status,
               oi.event_id, oi.quantity, oi.price, e.title
        FROM orders o
        JOIN orderitems oi ON o.id = oi.order_id
        JOIN events e ON e.id = oi.event_id
        WHERE o.user_id = ?
        ORDER BY o.created_at DESC
    ");
    $stmt->execute([$user_id]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $grouped = [];
    foreach ($rows as $r) {
        $oid = $r['order_id'];
        if (!isset($grouped[$oid])) {
            $grouped[$oid] = [
                'order_id' => $oid,
                'total_price' => $r['total_price'],
                'created_at' => $r['created_at'],
                'status' => $r['status'],
                'items' => []
            ];
        }
        $grouped[$oid]['items'][] = [
            'event_id' => $r['event_id'],
            'title'    => $r['title'],
            'quantity' => $r['quantity'],
            'price'    => $r['price']
        ];
    }
    $orders = array_values($grouped);
    echo json_encode(['success' => true, 'data' => $orders]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
