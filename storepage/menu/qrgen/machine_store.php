<?php
session_start();
require_once "../../../ld_db.php";

if (!isset($_SESSION['store_id'])) {
    die("ไม่พบร้าน");
}

$store_id     = $_SESSION['store_id'];
$machine_no   = $_POST['machine_no'] ?? null;
$machine_name = $_POST['machine_name'] ?? null;

if (!$machine_no) {
    die("ข้อมูลไม่ครบ");
}

$machine_id = bin2hex(random_bytes(16));

$stmt = $pdo->prepare("
    INSERT INTO machines (
        id,
        store_id,
        machine_no,
        machine_name
    ) VALUES (?, ?, ?, ?)
");

$stmt->execute([
    $machine_id,
    $store_id,
    $machine_no,
    $machine_name
]);

header("Location: ../../index.php?link=qrcode");
exit;