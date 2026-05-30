<?php
session_start();
header('Content-Type: application/json');
require_once 'db.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        $title    = $_GET['title']    ?? '';
        $genre    = $_GET['genre']    ?? '';
        $location = $_GET['location'] ?? '';
        $sql = "SELECT * FROM events WHERE 1=1";
        $params = [];
    
        if ($title !== '') {
            $sql .= " AND title LIKE :title";
            $params[':title'] = "%$title%";
        }
        if ($genre !== '') {
            $sql .= " AND genre = :genre";
            $params[':genre'] = $genre;
        }
        if ($location !== '') {
            $sql .= " AND location LIKE :location";
            $params[':location'] = "%$location%";
        }
        $sql .= " ORDER BY date_time ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'data' => $events]);    
    } elseif ($method === 'POST') {

        $action = $_POST['action'] ?? null;

        $title       = $_POST['title']       ?? '';
        $description = $_POST['description'] ?? '';
        $location    = $_POST['location']    ?? '';
        $date_time   = $_POST['date_time']   ?? '';
        $genre       = $_POST['genre']       ?? '';
        $price       = (float)($_POST['price'] ?? 0);
        $event_id    = $_POST['id']          ?? null;

        $artist_name = $_POST['artist_name'] ?? 'Unknown Artist';
        $songs       = $_POST['songs']       ?? ''; 

        $image_url = null;

        if (isset($_FILES['image']) && $_FILES['image']['tmp_name']) {
            $allowedExt = ['png','jpg','jpeg','webp'];
            $pathInfo   = pathinfo($_FILES['image']['name']);
            $ext        = strtolower($pathInfo['extension']);

            if(!in_array($ext, $allowedExt)){
                echo json_encode([
                    'success'=>false,
                    'message'=>"Unsupported file format. Allowed: png, jpg, jpeg, webp."
                ]);
                exit;
            }

            $targetDir = "uploads/";
            if(!is_dir($targetDir)){
                mkdir($targetDir, 0777, true);
            }

            $newFileName = uniqid("evt_").".".$ext;
            $targetPath  = $targetDir.$newFileName;

            if(move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)){
                $image_url = $targetPath; 
            } else {
                echo json_encode([
                    'success'=>false,
                    'message'=>"Failed to move uploaded file."
                ]);
                exit;
            }
        }

        if ($action === 'add') {
            $stmt = $pdo->prepare("
                INSERT INTO events 
                    (title, description, location, date_time, genre, price, artist_name, songs, image_url, created_at, updated_at)
                VALUES
                    (?,     ?,           ?,        ?,         ?,     ?,     ?,            ?,     ?,         NOW(),    NOW())
            ");
            $stmt->execute([
                $title,
                $description,
                $location,
                $date_time,
                $genre,
                $price,
                $artist_name,
                $songs,
                $image_url 
            ]);

            echo json_encode(['success' => true, 'message' => 'Event added']);

        } elseif ($action === 'edit') {
            if(!$event_id){
                echo json_encode(['success'=>false, 'message'=>'Missing event ID']);
                exit;
            }

            if($image_url){
                $stmt = $pdo->prepare("
                  UPDATE events
                  SET title=?, description=?, location=?, date_time=?, genre=?, price=?, 
                      artist_name=?, songs=?, image_url=?, updated_at=NOW()
                  WHERE id=?
                ");
                $stmt->execute([
                    $title, $description, $location, $date_time, $genre, $price,
                    $artist_name, $songs, $image_url,
                    $event_id
                ]);
            } else {
                $stmt = $pdo->prepare("
                  UPDATE events
                  SET title=?, description=?, location=?, date_time=?, genre=?, price=?,
                      artist_name=?, songs=?, updated_at=NOW()
                  WHERE id=?
                ");
                $stmt->execute([
                    $title, $description, $location, $date_time, $genre, $price,
                    $artist_name, $songs,
                    $event_id
                ]);
            }

            echo json_encode(['success' => true, 'message' => 'Event updated']);

        } else {
            echo json_encode(['success' => false, 'message' => 'Unknown action']);
        }

    } elseif ($method === 'DELETE') {
        parse_str(file_get_contents("php://input"), $_DELETE);
        $event_id = $_DELETE['id'] ?? null;
        if (!$event_id) {
            echo json_encode(['success' => false, 'message' => 'Missing id']);
            exit;
        }

        $stmt = $pdo->prepare("DELETE FROM events WHERE id=?");
        $stmt->execute([$event_id]);
        echo json_encode(['success' => true, 'message' => 'Event deleted']);

    } else {
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
