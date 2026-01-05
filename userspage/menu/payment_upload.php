<?php
session_start();
require_once "../../../ld_db.php";

/* ========= AUTH ========= */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    die('no permission');
}

$customer_id = $_SESSION['user_id'];

/* ========= VALIDATE ========= */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('invalid request');
}

$order_id = $_POST['order_id'] ?? null;

if (!$order_id) {
    die('no order id');
}

/* ========= CHECK ORDER ========= */
$stmt = $pdo->prepare("
    SELECT o.id, o.total_amount, o.payment_status
    FROM orders o
    WHERE o.id = ?
      AND o.customer_id = ?
");
$stmt->execute([$order_id, $customer_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    die('order not found');
}

if ($order['total_amount'] <= 0) {
    die('order amount invalid');
}

if ($order['payment_status'] === 'paid') {
    die('already paid');
}

/* ========= UPLOAD SLIP ========= */
if (
    !isset($_FILES['slip']) ||
    $_FILES['slip']['error'] !== UPLOAD_ERR_OK
) {
    die('no slip uploaded');
}

$allowed = ['image/jpeg','image/png'];
if (!in_array($_FILES['slip']['type'], $allowed)) {
    die('only jpg / png allowed');
}

$ext = pathinfo($_FILES['slip']['name'], PATHINFO_EXTENSION);
$filename = uniqid('slip_') . '.' . $ext;

$upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/ld_project/uploads/slips/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

$filepath = $upload_dir . $filename;

if (!move_uploaded_file($_FILES['slip']['tmp_name'], $filepath)) {
    die('upload failed');
}

$slip_path = 'uploads/slips/' . $filename;

/* ========= INSERT PAYMENT ========= */
$pdo->beginTransaction();

$stmt = $pdo->prepare("
    INSERT INTO payments
    (id, order_id, amount, method, status, note, created_at)
    VALUES (UUID(), ?, ?, 'promptpay', 'pending', ?, NOW())
");
$stmt->execute([
    $order_id,
    $order['total_amount'],
    $slip_path
]);

$stmt = $pdo->prepare("
    UPDATE orders
    SET payment_status = 'pending'
    WHERE id = ?
");
$stmt->execute([$order_id]);

$pdo->commit();

/* ========= REDIRECT ========= */
header("Location: orders/order_detail.php?id=" . $order_id);
exit;
