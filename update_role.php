<?php
session_start();
require_once 'db.php';
include 'header.php';

if (!isset($_SESSION['user_id']) || $_SESSION['is_admin'] != 1) {
    echo "Access denied.";
    exit;
}

$user_id = $_GET['id'];
$new_role = ($_GET['role'] == 'admin') ? 1 : 0;

$stmt = $pdo->prepare("UPDATE Users SET is_admin = :is_admin WHERE id = :id");
$stmt->execute(['is_admin' => $new_role, 'id' => $user_id]);

header("Location: manage_users.php");
exit;
?>
