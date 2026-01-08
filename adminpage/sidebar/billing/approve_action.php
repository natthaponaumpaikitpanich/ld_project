<?php
session_start();
require_once "../../../ld_db.php";

if ($_SESSION['role'] !== 'platform_admin') {
    die('no permission');
}

$id     = $_POST['id'] ?? null;
$action = $_POST['action'] ?? null;

if (!$id || !$action) {
    die('invalid request');
}

/* ===== ดึงข้อมูล subscription ===== */
$stmt = $pdo->prepare("
    SELECT ss.*, bp.id AS plan_id
    FROM store_subscriptions ss
    JOIN billing_plans bp ON ss.plan_id = bp.id
    WHERE ss.id = ?
");
$stmt->execute([$id]);
$sub = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$sub) {
    die('not found');
}

$pdo->beginTransaction();

try {

if ($action === 'approve') {

    /* 1) update subscription */
    $pdo->prepare("
        UPDATE store_subscriptions
        SET status='active',
            approved_by=?,
            approved_at=NOW(),
            start_date=CURDATE(),
            end_date=DATE_ADD(CURDATE(), INTERVAL 1 MONTH)
        WHERE id=?
    ")->execute([
        $_SESSION['user_id'],
        $id
    ]);

    /* 2) unlock store */
    $pdo->prepare("
        UPDATE stores
        SET status='active',
            billing_plan_id=?
        WHERE id=?
    ")->execute([
        $sub['plan_id'],
        $sub['store_id']
    ]);

}

if ($action === 'reject') {

    $pdo->prepare("
        UPDATE store_subscriptions
        SET status='rejected'
        WHERE id=?
    ")->execute([$id]);
}

$pdo->commit();

header("Location: ../sidebar.php?link=overdue");
exit;

} catch (Exception $e) {
    $pdo->rollBack();
    die($e->getMessage());
}
