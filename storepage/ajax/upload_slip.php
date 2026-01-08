<?php
session_start();
require_once "../../ld_db.php";

$sub_id = $_POST['sub_id'] ?? null;

if (!$sub_id || empty($_FILES['slip'])) {
    echo json_encode(['ok'=>false,'error'=>'ข้อมูลไม่ครบ']);
    exit;
}

$ext = pathinfo($_FILES['slip']['name'], PATHINFO_EXTENSION);
$filename = 'slip_' . time() . '.' . $ext;

$path = "../../adminpage/uploads/slips/" . $filename;
if (!move_uploaded_file($_FILES['slip']['tmp_name'], $path)) {
    echo json_encode(['ok'=>false,'error'=>'upload failed']);
    exit;
}

$stmt = $pdo->prepare("
    UPDATE store_subscriptions
    SET slip = ?, status='pending_approve'
    WHERE id = ?
");
$stmt->execute([$filename, $sub_id]);

echo json_encode(['ok'=>true]);
