<?php
session_start();
header('Content-Type: application/json');
require_once 'db.php';

$email = $_POST['email'] ?? '';
$pass  = $_POST['password'] ?? '';

$stmt = $pdo->prepare("SELECT * FROM users WHERE email=?");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo json_encode(['success'=>false, 'message'=>'User not found']);
    exit;
}
if (!password_verify($pass, $user['password'])) {
    echo json_encode(['success'=>false, 'message'=>'Invalid password']);
    exit;
}
if (!$user['is_verified']) {
    echo json_encode(['success'=>false, 'message'=>'Email not verified yet.']);
    exit;
}

$_SESSION['user_id']   = $user['id'];
$_SESSION['user_name'] = $user['name'];
$_SESSION['user_role'] = $user['role'];

echo json_encode([
    'success' => true,
    'message' => 'Login successful!',
    'data'    => ['role'=>$user['role']]
]);
