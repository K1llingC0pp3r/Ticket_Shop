<?php
header('Content-Type: application/json');
require_once 'db.php';

$stmtUp = $pdo->query("SELECT * FROM events ORDER BY date_time ASC LIMIT 4");
$up = $stmtUp->fetchAll(PDO::FETCH_ASSOC);

$stmtF = $pdo->query("SELECT * FROM events ORDER BY RAND() LIMIT 6");
$fe = $stmtF->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
  'success'=>true,
  'data'=>[
    'upcoming'=>$up,
    'featured'=>$fe
  ]
]);
