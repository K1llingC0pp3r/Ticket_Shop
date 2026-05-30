<?php
session_start();
header('Content-Type: application/json');
ini_set('display_errors', 0);
error_reporting(0);

require_once 'db.php';


$action = $_POST['action'] ?? '';

try {
    if ($action === 'request_reset') {
        $email = $_POST['email'] ?? '';
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            echo json_encode(['success' => false, 'message' => 'User not found']);
            exit;
        }

        $token = bin2hex(random_bytes(16));
        $stmt2 = $pdo->prepare("UPDATE users SET reset_token = ? WHERE id = ?");
        $stmt2->execute([$token, $user['id']]);

        $resetLink = $link = "https://klusonma21.mp.spse-net.cz/reset_password_form.html?token=$token";


        $subject = "Reset your password";
        $body = "Hello,\n\nClick the link to reset your password:\n$resetLink\n\nBest regards,\nTicketShop";
        $headers = "From: noreply@ticketshop.local\r\n";
        $mailResult = mail($email, $subject, $body, $headers);

        if ($mailResult) {
            echo json_encode(['success' => true, 'message' => 'Reset link sent to your email.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to send reset link email.']);
        }

        exit;
    }
    elseif ($action === 'reset_password') {
        $token = $_POST['token'] ?? '';
        $password = $_POST['password'] ?? '';

        $stmt = $pdo->prepare("SELECT id FROM users WHERE reset_token = ?");
        $stmt->execute([$token]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            echo json_encode(['success' => false, 'message' => 'Invalid reset token']);
            exit;
        }

        $newHash = password_hash($password, PASSWORD_DEFAULT);
        $stmt2 = $pdo->prepare("UPDATE users SET password = ?, reset_token = '' WHERE id = ?");
        $stmt2->execute([$newHash, $user['id']]);

        echo json_encode(['success' => true, 'message' => 'Password has been reset. You can now log in.']);
        exit;
    }
    else {
        echo json_encode(['success' => false, 'message' => 'Unknown action']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
