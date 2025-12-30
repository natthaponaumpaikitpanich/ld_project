<?php
session_start();
require_once "../../../ld_db.php";

if ($_SESSION['role'] !== 'staff') {
    die("ไม่มีสิทธิ์");
}

function uuidv4() {
    return sprintf(
        '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

$machine_id = $_POST['machine_id'] ?? null;
$order_id   = $_POST['order_id'] ?? null;

if (!$machine_id || !$order_id) die("ข้อมูลไม่ครบ");

$pdo->beginTransaction();
try {

    /* ปิด order เก่าของเครื่อง (ถ้ามี) */
    $pdo->prepare("
        UPDATE machine_orders
        SET active = 0
        WHERE machine_id = ?
    ")->execute([$machine_id]);

    /* ผูกใหม่ */
    $pdo->prepare("
        INSERT INTO machine_orders (id, machine_id, order_id)
        VALUES (?, ?, ?)
    ")->execute([
        uuidv4(),
        $machine_id,
        $order_id
    ]);

    /* update สถานะ order */
    $pdo->prepare("
        UPDATE orders
        SET status = 'in_process'
        WHERE id = ?
    ")->execute([$order_id]);

    $pdo->commit();

    header("Location: ../../../scan.php?machine_id=".$machine_id);
    exit;

} catch (Exception $e) {
    $pdo->rollBack();
    die($e->getMessage());
}
