<?php
session_start();
require_once 'db.php';
include 'header.php';

if (!isset($_SESSION['user_id']) || $_SESSION['is_admin'] != 1) {
    echo "Access denied.";
    exit;
}

$user_id = $_GET['id'];

if ($user_id == $_SESSION['user_id']) {
    echo "You cannot delete yourself.";
    exit;
}

$stmt = $pdo->prepare("DELETE FROM Users WHERE id = :id");
$stmt->execute(['id' => $user_id]);

header("Location: manage_users.php");
exit;
?>
