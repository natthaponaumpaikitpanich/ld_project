<?php
session_start();
require_once "../../../ld_db.php";

if ($_SESSION['role'] !== 'staff') {
    http_response_code(403);
    echo json_encode(['error' => 'no permission']);
    exit;
}

$order_id  = $_POST['order_id'] ?? null;
$new_status = $_POST['next_status'] ?? null;
$staff_id  = $_SESSION['user_id'];

if (!$order_id || !$new_status) {
    http_response_code(400);
    echo json_encode(['error' => 'data missing']);
    exit;
}

$pdo->beginTransaction();

try {
    // update order
    $stmt = $pdo->prepare("UPDATE orders SET status=? WHERE id=?");
    $stmt->execute([$new_status, $order_id]);

    // log
    $stmt = $pdo->prepare("
        INSERT INTO order_status_logs (id, order_id, status, changed_by)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([
        bin2hex(random_bytes(16)), // แทน uuid_create
        $order_id,
        $new_status,
        $staff_id
    ]);

    $pdo->commit();

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
