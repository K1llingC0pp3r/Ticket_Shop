<?php
session_start();
header('Content-Type: application/json');
require_once 'db.php';

$name     = $_POST['name']     ?? '';
$email    = $_POST['email']    ?? '';
$password = $_POST['password'] ?? '';

if (!$name || !$email || !$password) {
    echo json_encode([
        'success' => false,
        'message' => 'Please fill all fields'
    ]);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        echo json_encode([
            'success' => false,
            'message' => 'User with this email already exists'
        ]);
        exit;
    }

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    
    $token = bin2hex(random_bytes(16));

    $stmt2 = $pdo->prepare("
        INSERT INTO users (name, email, password, role, created_at, is_verified, verification_token)
        VALUES (?, ?, ?, ?, NOW(), ?, ?)
    ");
    $stmt2->execute([
        $name,
        $email,
        $passwordHash,
        'user',
        0,           
        $token
    ]);

    $userId = $pdo->lastInsertId();

    $verifyLink = "https://klusonma21.mp.spse-net.cz/verify_email.php?token=$token";
    $subject = "Please verify your email";
    $body = "Dobrý den $name,\n\nKlikněte prosím na následující odkaz, abyste ověřili svůj e-mail:\n$verifyLink\n\nDěkujeme,\nTicketShop";
    $headers = "From: noreply@ticketshop.cz\r\n" .
           "Reply-To: noreply@ticketshop.cz\r\n" .
           "X-Mailer: PHP/" . phpversion();
    $mailResult = mail($email, $subject, $body, $headers);


    if ($mailResult) {
        echo json_encode([
            'success' => true,
            'message' => 'Registration successful! Check your email to verify your account.'
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'message' => 'Registration done, but failed to send verification email. 
                          Please contact support or check server logs.'
        ]);
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
