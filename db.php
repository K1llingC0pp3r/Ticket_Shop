<?php
$host = 'db.mp.spse-net.cz';
$db   = 'klusonma21_1';
$user = 'klusonma21';
$pass = 'biredekojeche';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  } catch (Exception $e) {
    die("DB Error: " . $e->getMessage());
  }
