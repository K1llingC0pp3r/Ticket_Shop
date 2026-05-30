<?php
session_start();
header('Content-Type: application/json');
require_once 'db.php';

if(!isset($_SESSION['user_id']) || ($_SESSION['user_role']??'')!=='admin'){
    echo json_encode(['success'=>false,'message'=>'Not authorized']);
    exit;
}

$method=$_SERVER['REQUEST_METHOD'];
try{
    if($method==='GET'){
        $stmt=$pdo->query("SELECT id, name, email, role, is_verified FROM users ORDER BY id ASC");
        $users=$stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success'=>true,'data'=>$users]);
    }
    elseif($method==='POST'){
        $action=$_POST['action']??null;
        if($action==='update_role'){
            $user_id=$_POST['user_id']??null;
            $role=$_POST['role']??null;
            if(!$user_id||!$role){
                throw new Exception("Missing user_id or role");
            }
            $stmt=$pdo->prepare("UPDATE users SET role=? WHERE id=?");
            $stmt->execute([$role,$user_id]);
            echo json_encode(['success'=>true,'message'=>"Role updated"]);
        }else{
            echo json_encode(['success'=>false,'message'=>'Unknown POST action']);
        }
    }
    elseif($method==='DELETE'){
        parse_str(file_get_contents("php://input"),$_DELETE);
        $user_id=$_DELETE['user_id']??null;
        if(!$user_id) throw new Exception("Missing user_id");
        $stmt=$pdo->prepare("DELETE FROM users WHERE id=?");
        $stmt->execute([$user_id]);
        echo json_encode(['success'=>true,'message'=>"User deleted"]);
    }
    else{
        echo json_encode(['success'=>false,'message'=>'Method not allowed']);
    }
}catch(Exception $e){
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
