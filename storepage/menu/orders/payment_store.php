<?php
session_start();
require_once "../../../ld_db.php";

if (!isset($_SESSION['user_id'], $_SESSION['store_id'])) {
    die('no permission');
}

$user_id  = $_SESSION['user_id'];
$store_id = $_SESSION['store_id'];

$order_id = $_POST['order_id'] ?? null;
$amount   = $_POST['amount'] ?? null;
$provider = $_POST['provider'] ?? null;

if (!$order_id || !$amount || !$provider) {
    die("ข้อมูลไม่ครบ");
}

/* verify order */
$stmt = $pdo->prepare("
    SELECT id FROM orders
    WHERE id=? AND store_id=?
");
$stmt->execute([$order_id,$store_id]);
if (!$stmt->fetch()) {
    die("order ไม่ถูกต้อง");
}

$pdo->beginTransaction();

$stmt = $pdo->prepare("
    INSERT INTO payments
    (id, order_id, amount, provider, status, paid_at)
    VALUES (UUID(), ?, ?, ?, 'success', NOW())
");
$stmt->execute([$order_id,$amount,$provider]);

$pdo->prepare("
    UPDATE orders SET payment_status='paid'
    WHERE id=?
")->execute([$order_id]);

$pdo->commit();

header("Location: order_view.php?id=".$order_id);
exit;