<?php
require_once "../../../phpqrcode/qrlib.php";
require_once "../../../ld_db.php";

$machine_id = $_GET['id'] ?? null;
if (!$machine_id) {
    die("ไม่พบเครื่อง");
}

/* ตรวจสอบเครื่อง */
$stmt = $pdo->prepare("SELECT * FROM machines WHERE id = ?");
$stmt->execute([$machine_id]);
$machine = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$machine) {
    die("เครื่องไม่ถูกต้อง");
}

/* URL ที่จะฝังใน QR */
$url = "http://localhost/ld_project/scan.php?machine_id=".$machine_id;

/* สร้าง QR */
QRcode::png($url, null, QR_ECLEVEL_Q, 8);
