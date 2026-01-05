<?php
session_start();
require_once "../../ld_db.php";

/* ========= AUTH ========= */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    die('no permission');
}

$user_id  = $_SESSION['user_id'];
$order_id = $_POST['order_id'] ?? null;
if (!$order_id) {
    header("Location: my_orders.php");
    exit;
}

/* ========= FETCH ORDER ========= */
$stmt = $pdo->prepare("
    SELECT *
    FROM orders
    WHERE id = ? AND customer_id = ?
");
$stmt->execute([$order_id, $user_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) die('order not found');

/* ========= PREVENT DUPLICATE ========= */
$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM payments
    WHERE order_id = ? AND status IN ('pending','confirmed')
");
$stmt->execute([$order_id]);
if ($stmt->fetchColumn() > 0) {
    header("Location: payment_promptpay.php?id=".$order_id);
    exit;
}

/* ========= HANDLE SLIP UPLOAD ========= */
if (empty($_FILES['slip_image']['name'])) {
    die('กรุณาอัปโหลดสลิป');
}

$allowed = ['image/jpeg','image/png','image/webp'];
if (!in_array($_FILES['slip_image']['type'], $allowed)) {
    die('ไฟล์ต้องเป็นรูปภาพ');
}

$ext = pathinfo($_FILES['slip_image']['name'], PATHINFO_EXTENSION);
$filename = 'slip_' . uniqid() . '.' . $ext;

$upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/ld_project/uploads/slips/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

if (!move_uploaded_file($_FILES['slip_image']['tmp_name'], $upload_dir.$filename)) {
    die('อัปโหลดสลิปไม่สำเร็จ');
}

$slip_path = 'uploads/slips/'.$filename;

/* ========= INSERT PAYMENT ========= */
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

/* ========= UPDATE ORDER ========= */
$stmt = $pdo->prepare("
    UPDATE orders
    SET payment_status = 'pending'
    WHERE id = ?
");
$stmt->execute([$order_id]);

header("Location: payment_promptpay.php?id=".$order_id);
exit;
?>

<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>แจ้งชำระเงินแล้ว</title>
<link href="../bootstrap/css/bootstrap.min.css" rel="stylesheet">
<style>
body{background:#f4f6f9}
</style>
</head>

<body>
<div class="container py-5">
<div class="card shadow mx-auto" style="max-width:420px">
<div class="card-body text-center p-4">

    <h4 class="text-success fw-bold mb-2">✅ แจ้งชำระเงินแล้ว</h4>
    <p class="text-muted">
        ระบบได้บันทึกการแจ้งชำระเงินของคุณแล้ว  
        กรุณารอร้านยืนยันการชำระเงิน
    </p>

    <a href="index.php" class="btn btn-primary w-100 mt-3">
        กลับหน้าหลัก
    </a>

</div>
</div>
</div>
</body>
</html>
