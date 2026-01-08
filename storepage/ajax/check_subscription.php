<?php
session_start();
require_once "../../ld_db.php";

$store_id = $_SESSION['store_id'] ?? null;
if (!$store_id) {
    echo json_encode(['status'=>'none']);
    exit;
}

$stmt = $pdo->prepare("
    SELECT status
    FROM store_subscriptions
    WHERE store_id=?
    ORDER BY created_at DESC
    LIMIT 1
");
$stmt->execute([$store_id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode([
    'status' => $row['status'] ?? 'none'
]);
