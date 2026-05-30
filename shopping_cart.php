<?php
session_start();
header('Content-Type: application/json');

require_once 'db.php'; 

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$action = $_GET['action'] ?? null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? null;
}

try {
    switch ($action) {

        case 'view_cart':
            echo json_encode([
                'success' => true,
                'data' => $_SESSION['cart']
            ]);
            break;

        case 'add_to_cart': {
            $event_id = $_POST['event_id'] ?? null;
            $quantity = (int)($_POST['quantity'] ?? 1);
            if (!$event_id) {
                throw new Exception("Missing event_id");
            }

            $stmt = $pdo->prepare("SELECT * FROM events WHERE id = ?");
            $stmt->execute([$event_id]);
            $event = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$event) {
                throw new Exception("Event not found (id=$event_id)");
            }

            $found = false;
            foreach ($_SESSION['cart'] as &$item) {
                if ($item['event']['id'] == $event_id) {
                    $item['quantity'] += $quantity;
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $_SESSION['cart'][] = [
                    'event' => $event,
                    'quantity' => $quantity
                ];
            }

            echo json_encode([
                'success' => true,
                'message' => 'Item added to cart'
            ]);
            break;
        }

        case 'update_quantity': {
            $event_id = $_POST['event_id'] ?? null;
            $quantity = (int)($_POST['quantity'] ?? 1);
            if (!$event_id) {
                throw new Exception("Missing event_id");
            }
            if ($quantity < 1) {
                throw new Exception("Quantity must be >= 1");
            }

            foreach ($_SESSION['cart'] as &$item) {
                if ($item['event']['id'] == $event_id) {
                    $item['quantity'] = $quantity;
                    break;
                }
            }
            echo json_encode([
                'success' => true,
                'message' => 'Quantity updated'
            ]);
            break;
        }

        case 'remove_from_cart': {
            $event_id = $_POST['event_id'] ?? null;
            if (!$event_id) {
                throw new Exception("Missing event_id");
            }
            foreach ($_SESSION['cart'] as $index => $item) {
                if ($item['event']['id'] == $event_id) {
                    unset($_SESSION['cart'][$index]);
                    $_SESSION['cart'] = array_values($_SESSION['cart']);
                    break;
                }
            }
            echo json_encode([
                'success' => true,
                'message' => 'Item removed from cart'
            ]);
            break;
        }

        case 'checkout': {
            if (!isset($_SESSION['user_id'])) {
                echo json_encode([
                    'success' => false,
                    'message' => 'You must be logged in to checkout.'
                ]);
                exit;
            }
            if (empty($_SESSION['cart'])) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Cart is empty.'
                ]);
                exit;
            }

            $total = 0;             
            $itemsDetail = "";      
            foreach ($_SESSION['cart'] as $item) {
                $price = $item['event']['price'];
                $qty   = $item['quantity'];
                $total += $price * $qty;

                $itemsDetail .= "- " . $item['event']['title'] . " x$qty (Price: $$price)\n";
            }

            $stmtUser = $pdo->prepare("SELECT email, name FROM users WHERE id = ?");
            $stmtUser->execute([$_SESSION['user_id']]);
            $userRow = $stmtUser->fetch(PDO::FETCH_ASSOC);
            if (!$userRow) {
                echo json_encode([
                    'success' => false,
                    'message' => 'User not found in DB.'
                ]);
                exit;
            }

            $pdo->beginTransaction();
            $stmtOrd = $pdo->prepare("
                INSERT INTO orders (user_id, total_price, status, created_at)
                VALUES (?, ?, 'Pending', NOW())
            ");
            $stmtOrd->execute([ $_SESSION['user_id'], $total ]);
            $orderId = $pdo->lastInsertId();

            foreach ($_SESSION['cart'] as $item) {
                $stmtItem = $pdo->prepare("
                    INSERT INTO orderitems (order_id, event_id, quantity, price)
                    VALUES (?, ?, ?, ?)
                ");
                $stmtItem->execute([
                    $orderId,
                    $item['event']['id'],
                    $item['quantity'],
                    $item['event']['price']
                ]);
            }
            $pdo->commit();

            $to      = $userRow['email'];
            $toName  = $userRow['name'];
            $subject = "Your Order #$orderId - TicketShop";
            $body    = "Hello $toName,\n\nThank you for your purchase!\n"
                     . "Order ID: $orderId\n\nOrdered Items:\n$itemsDetail"
                     . "\nTotal: $$total\n\nBest regards,\nTicketShop";
            $headers = "From: orders@ticketshop.local\r\n"; 
            mail($to, $subject, $body, $headers);

            $_SESSION['cart'] = [];

            echo json_encode([
                'success' => true,
                'message' => "Order #$orderId has been created. Confirmation email sent to $to."
            ]);
            break;
        }

        default:
            echo json_encode([
                'success'=>false,
                'message'=>"Unknown action"
            ]);
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
