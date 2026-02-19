<?php
session_start();
require_once "../../../ld_db.php";

// เช็คสิทธิ์เจ้าของร้าน
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'store_owner') {
    die('Unauthorized');
}

$staff_id = $_GET['id'] ?? '';
$action   = $_GET['action'] ?? '';
$store_id = $_SESSION['store_id'];

if ($action === 'approve') {
    // อัปเดตสถานะเป็น active และตั้งเวลาเริ่มงาน
    $stmt = $pdo->prepare("UPDATE store_staff SET status = 'active', created_at = NOW() WHERE id = ? AND store_id = ?");
    $stmt->execute([$staff_id, $store_id]);
} elseif ($action === 'reject') {
    // ถ้าปฏิเสธ ให้ลบแถวนั้นทิ้งไปเลย (เพื่อให้เขาสมัครใหม่ได้ถ้าจำเป็น)
    $stmt = $pdo->prepare("DELETE FROM store_staff WHERE id = ? AND store_id = ? AND status = 'pending'");
    $stmt->execute([$staff_id, $store_id]);
}

// กลับหน้าเดิม
header("Location: ../../index.php?link=management");
exit;