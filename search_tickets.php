<?php
session_start();
header('Content-Type: application/json');
require_once 'db.php';

$title    = $_GET['title']    ?? '';
$genre    = $_GET['genre']    ?? '';
$location = $_GET['location'] ?? '';
$minPrice = (float)($_GET['minPrice'] ?? 0);
$maxPrice = (float)($_GET['maxPrice'] ?? 0);

try {
    $sql = "SELECT * FROM events WHERE 1=1 ";
    $params = [];

    if ($title !== '') {
        $sql .= " AND title LIKE :title";
        $params[':title'] = "%$title%";
    }
    if ($genre !== '') {
        $sql .= " AND genre LIKE :genre";
        $params[':genre'] = "%$genre%";
    }
    if ($location !== '') {
        $sql .= " AND location LIKE :location";
        $params[':location'] = "%$location%";
    }
    if ($minPrice > 0) {
        $sql .= " AND price >= :minPrice";
        $params[':minPrice'] = $minPrice;
    }
    if ($maxPrice > 0 && $maxPrice >= $minPrice) {
        $sql .= " AND price <= :maxPrice";
        $params[':maxPrice'] = $maxPrice;
    }

    $sql .= " ORDER BY date_time ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $events
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
