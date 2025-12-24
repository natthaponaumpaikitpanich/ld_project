<?php
require_once "../../../ld_db.php";

$action = $_GET['action'] ?? null;
$id = $_GET['id'] ?? null;

// ป้องกัน error
if (!$action || !$id) {
    echo "<script>alert('ข้อมูลไม่ครบ'); history.back();</script>";
    exit;
}

// ตรวจว่ามี report จริงไหม
$check = $pdo->prepare("SELECT id FROM reports WHERE id = ?");
$check->execute([$id]);

if (!$check->fetch()) {
    echo "<script>alert('ไม่พบรายการแจ้งปัญหา'); history.back();</script>";
    exit;
}

// ❗ ไม่ว่าจะ accept หรือ reject → ลบ 1 รายการ
$sql = $pdo->prepare("DELETE FROM reports WHERE id = ?");
$sql->execute([$id]);

echo "<script>
    alert('ดำเนินการเรียบร้อยแล้ว');
    window.location='../sidebar.php?link=reports';
</script>";
exit;