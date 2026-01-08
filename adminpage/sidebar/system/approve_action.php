<?php
session_start();
require_once "../../../ld_db.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'platform_admin') {
    die('no permission');
}

$subscription_id = $_POST['subscription_id'] ?? null;
$action = $_POST['action'] ?? null;

if (!$subscription_id || !in_array($action, ['approve','reject'])) {
    die('invalid request');
}

$admin_id = $_SESSION['user_id'];

$pdo->beginTransaction();

try {

    if ($action === 'approve') {

        $stmt = $pdo->prepare("
            UPDATE store_subscriptions
            SET
                status = 'active',
                start_date = CURDATE(),
                end_date = DATE_ADD(CURDATE(), INTERVAL 1 MONTH),
                approved_by = ?,
                approved_at = NOW(),
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$admin_id, $subscription_id]);

    } else {

        $stmt = $pdo->prepare("
            UPDATE store_subscriptions
            SET
                status = 'rejected',
                approved_by = ?,
                approved_at = NOW(),
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$admin_id, $subscription_id]);

    }

    $pdo->commit();

} catch (Exception $e) {
    $pdo->rollBack();
    die($e->getMessage());
}

header("Location: approve_list.php");
exit;
