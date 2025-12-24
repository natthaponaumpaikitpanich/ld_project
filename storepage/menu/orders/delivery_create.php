<?php
session_start();
require_once "../../../ld_db.php";

$store_id = $_SESSION['store_id'] ?? null;
$order_id = $_POST['order_id'] ?? null;
$pickup_address = $_POST['pickup_address'] ?? null;
$scheduled_at = $_POST['scheduled_at'] ?? null;

if (!$store_id || !$order_id || !$pickup_address) {
    die("ข้อมูลไม่ครบ");
}

/* ---------- ตรวจสอบว่า order เป็นของร้านนี้ ---------- */
$stmt = $pdo->prepare("
    SELECT id
    FROM orders
    WHERE id = ? AND store_id = ?
");
$stmt->execute([$order_id, $store_id]);

if (!$stmt->fetch()) {
    die("ออเดอร์ไม่ถูกต้อง");
}

/* ---------- สร้างงานจัดส่ง ---------- */
$stmt = $pdo->prepare("
    INSERT INTO pickups
    (id, order_id, pickup_address, scheduled_at, status)
    VALUES (UUID(), ?, ?, ?, 'scheduled')
");
$stmt->execute([
    $order_id,
    $pickup_address,
    $scheduled_at
]);

/* ---------- อัปเดตสถานะ Order ---------- */
$stmt = $pdo->prepare("
    UPDATE orders
    SET status = 'picked_up'
    WHERE id = ?
");
$stmt->execute([$order_id]);

header("Location: order_view.php?id=" . $order_id);
exit;
