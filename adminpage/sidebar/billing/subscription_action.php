<?php
require_once "../../../ld_db.php";
session_start();

$data = json_decode(file_get_contents("php://input"), true);
$id = $data['id'] ?? null;
$action = $data['action'] ?? null;

if (!$id || !$action) exit;

if ($action === 'approve') {

    $stmt = $pdo->prepare("
        UPDATE store_subscriptions
        SET status='active',
            approved_by=?,
            approved_at=NOW(),
            start_date=CURDATE(),
            end_date=DATE_ADD(CURDATE(), INTERVAL 1 MONTH)
        WHERE id=?
    ");
    $stmt->execute([$_SESSION['user_id'], $id]);

}

if ($action === 'reject') {

    $stmt = $pdo->prepare("
        UPDATE store_subscriptions
        SET status='pending_payment',
            slip=NULL
        WHERE id=?
    ");
    $stmt->execute([$id]);
}
