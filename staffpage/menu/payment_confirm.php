<?php
session_start();
require_once "../../../ld_db.php";

/* ========= AUTH ========= */
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['store_owner','staff'])) {
    die('no permission');
}

$user_id    = $_SESSION['user_id'];
$payment_id = $_POST['payment_id'] ?? null;
$action     = $_POST['action'] ?? null;

if (!$payment_id || !in_array($action,['confirm','reject'])) {
    die('invalid request');
}

$pdo->beginTransaction();

/* ========= FETCH PAYMENT ========= */
$stmt = $pdo->prepare("
    SELECT p.*, o.id AS order_id
    FROM payments p
    JOIN orders o ON o.id = p.order_id
    JOIN store_staff ss ON ss.store_id = o.store_id
    WHERE p.id = ? AND ss.user_id = ?
");
$stmt->execute([$payment_id, $user_id]);
$payment = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$payment) {
    $pdo->rollBack();
    die('payment not found');
}

/* ========= ACTION ========= */
if ($action === 'confirm') {

    // 1. confirm payment
    $stmt = $pdo->prepare("
        UPDATE payments
        SET status = 'confirmed',
            confirmed_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$payment_id]);

    // 2. update order
    $stmt = $pdo->prepare("
        UPDATE orders
        SET payment_status = 'paid'
        WHERE id = ?
    ");
    $stmt->execute([$payment['order_id']]);

} else {

    // reject
    $stmt = $pdo->prepare("
        UPDATE payments
        SET status = 'rejected'
        WHERE id = ?
    ");
    $stmt->execute([$payment_id]);
}

$pdo->commit();

header("Location: payments_pending.php");
exit;
