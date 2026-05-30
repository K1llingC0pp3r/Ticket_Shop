<?php
session_start();
header('Content-Type: application/json');
require_once 'db.php';

if (!isset($_SESSION['user_id']) || (($_SESSION['user_role'] ?? '') !== 'admin')) {
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        $user   = $_GET['user'] ?? '';
        $date   = $_GET['date'] ?? '';
        $status = $_GET['status'] ?? '';

        $sql = "
            SELECT 
              o.id AS order_id, 
              o.user_id, 
              o.total_price, 
              o.created_at, 
              o.status,
              u.name AS user_name, 
              u.email AS user_email,
              oi.event_id, 
              oi.quantity, 
              oi.price, 
              e.title
            FROM orders o
            JOIN users u ON o.user_id = u.id
            JOIN orderitems oi ON o.id = oi.order_id
            JOIN events e ON e.id = oi.event_id
            WHERE 1=1
        ";
        $params = [];
        if ($user !== '') {
            $sql .= " AND (u.name LIKE :user OR u.email LIKE :user) ";
            $params[':user'] = "%$user%";
        }
        if ($date !== '') {
            $sql .= " AND DATE(o.created_at) = :date ";
            $params[':date'] = $date;
        }
        if ($status !== '') {
            $sql .= " AND o.status = :status ";
            $params[':status'] = $status;
        }
        $sql .= " ORDER BY o.created_at DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $grouped = [];
        foreach ($rows as $r) {
            $oid = $r['order_id'];
            if (!isset($grouped[$oid])) {
                $grouped[$oid] = [
                    'id'          => $oid,
                    'user_name'   => $r['user_name'],
                    'user_email'  => $r['user_email'],
                    'total_price' => $r['total_price'],
                    'created_at'  => $r['created_at'],
                    'status'      => $r['status'],
                    'items'       => []
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
    } elseif ($method === 'POST') {
        $action = $_POST['action'] ?? null;
        if ($action === 'update_status') {
            $order_id = $_POST['order_id'] ?? null;
            $status   = $_POST['status'] ?? 'pending';
            if (!$order_id) {
                throw new Exception("Missing order_id");
            }
            $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
            $stmt->execute([$status, $order_id]);
            echo json_encode([
                'success' => true,
                'message' => "Order #$order_id status updated to $status"
            ]);
        } elseif ($action === 'delete_order') {
            $order_id = $_POST['order_id'] ?? null;
            if (!$order_id) {
                throw new Exception("Missing order_id");
            }
            $stmt = $pdo->prepare("DELETE FROM orderitems WHERE order_id = ?");
            $stmt->execute([$order_id]);
            $stmt2 = $pdo->prepare("DELETE FROM orders WHERE id = ?");
            $stmt2->execute([$order_id]);
            echo json_encode(['success' => true, 'message' => "Order #$order_id deleted"]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Unknown action']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => "Method not allowed"]);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
