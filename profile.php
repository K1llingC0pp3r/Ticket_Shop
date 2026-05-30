<?php
session_start();
header('Content-Type: application/json');
require_once 'db.php';

$action = $_GET['action'] ?? ($_POST['action'] ?? null);

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}
$user_id = $_SESSION['user_id'];

try {
    switch($action) {
        case 'get_user':
            $stmt = $pdo->prepare("SELECT name, email FROM users WHERE id=?");
            $stmt->execute([$user_id]);
            $u = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$u) {
                echo json_encode(['success' => false, 'message' => 'User not found']);
                exit;
            }
            echo json_encode(['success' => true, 'data' => $u]);
            break;

        case 'update_email':
            $email = $_POST['email'] ?? '';
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                echo json_encode(['success' => false, 'message' => 'Invalid email']);
                exit;
            }
            $stmt = $pdo->prepare("UPDATE users SET email=? WHERE id=?");
            $stmt->execute([$email, $user_id]);

            echo json_encode(['success' => true, 'message' => 'Email updated']);
            break;

        case 'update_password':
            $password = $_POST['password'] ?? '';
            if (strlen($password) < 6) {
                echo json_encode(['success' => false, 'message' => 'Password must be >= 6 chars']);
                exit;
            }
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password=? WHERE id=?");
            $stmt->execute([$hash, $user_id]);
            echo json_encode(['success' => true, 'message' => 'Password changed']);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Unknown action']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
