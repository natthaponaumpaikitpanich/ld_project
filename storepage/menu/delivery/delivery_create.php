<?php
session_start();
require_once "../../../ld_db.php";

$store_id = $_SESSION['store_id'] ?? null;
$order_id = $_GET['order_id'] ?? null;

if (!$store_id || !$order_id) {
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

/* ---------- กันสร้าง pickup ซ้ำ ---------- */
$check = $pdo->prepare("
    SELECT id FROM pickups WHERE order_id = ?
");
$check->execute([$order_id]);

if ($check->fetch()) {
    die("ออเดอร์นี้มีงานจัดส่งแล้ว");
}

/* ---------- สร้างงานจัดส่ง ---------- */
$stmt = $pdo->prepare("
    INSERT INTO pickups
    (id, order_id, pickup_address, scheduled_at, status)
    VALUES (UUID(), ?, 'ที่อยู่จากลูกค้า', NULL, 'scheduled')
");
$stmt->execute([$order_id]);

/* ---------- อัปเดตสถานะ Order ---------- */
$stmt = $pdo->prepare("
    UPDATE orders
    SET status = 'picked_up'
    WHERE id = ?
");
$stmt->execute([$order_id]);

header("Location: ../../index.php?link=delivery");
exit;
