<?php
session_start();
require_once 'db.php';

$token = $_GET['token'] ?? '';

if (!$token) {
    echo "Missing token";
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE verification_token=? AND is_verified=0");
    $stmt->execute([$token]);
    $row = $stmt->fetch();
    if (!$row) {
        echo "Invalid or already verified.";
        exit;
    }

    $upd = $pdo->prepare("UPDATE users SET is_verified=1, verification_token='' WHERE id=?");
    $upd->execute([$row['id']]);

    echo "Email verified! Now you can <a href='login.html'>log in</a>.";
} catch (Exception $e) {
    echo "Error: ".$e->getMessage();
}
