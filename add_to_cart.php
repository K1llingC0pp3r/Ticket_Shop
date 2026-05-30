<?php
require_once 'db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? null;
    $event_id = isset($_POST['event_id']) ? (int)$_POST['event_id'] : null;
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

    if (!$event_id) {
        echo json_encode(["success" => false, "message" => "Invalid event ID."]);
        exit;
    }

    if ($action === 'add') {
        // Fetch event details
        $stmt = $pdo->prepare("SELECT id, title, price FROM Events WHERE id = :event_id");
        $stmt->execute(['event_id' => $event_id]);
        $event = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($event) {
            // Initialize cart
            if (!isset($_SESSION['cart'])) {
                $_SESSION['cart'] = [];
            }

            // Add or update the cart item
            if (isset($_SESSION['cart'][$event_id])) {
                $_SESSION['cart'][$event_id]['quantity'] += $quantity;
            } else {
                $_SESSION['cart'][$event_id] = [
                    'id' => $event['id'],
                    'title' => $event['title'],
                    'price' => $event['price'],
                    'quantity' => $quantity
                ];
            }

            echo json_encode(["success" => true, "message" => "Item added to cart.", "cart" => $_SESSION['cart']]);
        } else {
            echo json_encode(["success" => false, "message" => "Event not found."]);
        }
    } elseif ($action === 'remove') {
        if (isset($_SESSION['cart'][$event_id])) {
            unset($_SESSION['cart'][$event_id]);
            echo json_encode(["success" => true, "message" => "Item removed from cart.", "cart" => $_SESSION['cart']]);
        } else {
            echo json_encode(["success" => false, "message" => "Item not in cart."]);
        }
    } else {
        echo json_encode(["success" => false, "message" => "Invalid action specified."]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Invalid request method."]);
}
?>
