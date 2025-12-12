<?php
require_once '../../../ld_db.php';

if (!isset($_GET['id'])) {
    die("ไม่พบแพ็กเกจที่ต้องการลบ");
}

$id = $_GET['id'];

// ตรวจว่ามีร้านค้าที่ใช้งานอยู่ไหม
$check = $pdo->prepare("SELECT COUNT(*) FROM stores WHERE billing_plan_id = ?");
$check->execute([$id]);
$inUse = $check->fetchColumn();

if ($inUse > 0) {
    header("Location:../sidebar.php?link=setting");
    exit;
}

// ลบแพ็กเกจได้
$stmt = $pdo->prepare("DELETE FROM billing_plans WHERE id = ?");
$stmt->execute([$id]);

header("Location:../sidebar.php?link=setting");
exit;
