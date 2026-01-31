<?php
session_start();
require_once "../../../ld_db.php"; // ตรวจสอบ Path ให้ถูกต้องตามโครงสร้างโฟลเดอร์จริง

// 1. ตรวจสอบสิทธิ์ (ถ้ายังไม่มี session_id ให้เช็คตามความเหมาะสม)
if ($_SESSION['role'] !== 'platform_admin') {
    die('ไม่มีสิทธิ์เข้าถึงส่วนนี้');
}

// 2. รับค่าจากทั้ง $_POST (ฟอร์ม) หรือ $_GET (ลิ้งก์)
$id     = $_REQUEST['id'] ?? null;
$action = $_REQUEST['action'] ?? null;

// กรณีหน้า UI ของคุณส่ง id มาทาง GET แต่ไม่ได้ส่ง action มาตรงๆ 
// ให้เช็คจากเงื่อนไขในไฟล์นี้ได้เลย
if (!$id) {
    die('ไม่พบข้อมูล ID');
}

/* ===== ดึงข้อมูล subscription เพื่อหา plan_id และ store_id ===== */
$stmt = $pdo->prepare("
    SELECT ss.*
    FROM store_subscriptions ss
    WHERE ss.id = ?
");
$stmt->execute([$id]);
$sub = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$sub) {
    die('ไม่พบรายการสมัครนี้ในระบบ');
}

try {
    $pdo->beginTransaction();

    // กรณี Action คือ Approve (อนุมัติ)
    if ($action === 'approve' || isset($_GET['id'])) { 
        
        // 1) อัปเดตสถานะ subscription
        $stmtSub = $pdo->prepare("
            UPDATE store_subscriptions
            SET status = 'active',
                approved_by = ?,
                approved_at = NOW(),
                start_date = CURDATE(),
                end_date = DATE_ADD(CURDATE(), INTERVAL 1 MONTH)
            WHERE id = ?
        ");
        $stmtSub->execute([$_SESSION['user_id'], $id]);

        // 2) ปลดล็อกร้านค้า (Active) และผูก Plan ID เข้ากับร้านค้า
        // หมายเหตุ: ใช้ค่า plan_id ที่ดึงมาได้จาก $sub
        $stmtStore = $pdo->prepare("
            UPDATE stores
            SET status = 'active',
                billing_plan_id = ?
            WHERE id = ?
        ");
        $stmtStore->execute([
            $sub['plan_id'],
            $sub['store_id']
        ]);
    }

    // กรณี Action คือ Reject (ถ้ามีการส่ง action=reject มา)
    if ($action === 'reject') {
        $pdo->prepare("
            UPDATE store_subscriptions
            SET status = 'rejected'
            WHERE id = ?
        ")->execute([$id]);
    }

    $pdo->commit();

    // กลับไปยังหน้าหลัก (ปรับ Path ให้ตรงกับไฟล์หน้าตารางของคุณ)
    header("Location: ../sidebar.php?link=allstore"); // หรือชื่อไฟล์ที่คุณใช้
    exit;

} catch (Exception $e) {
    $pdo->rollBack();
    die("เกิดข้อผิดพลาด: " . $e->getMessage());
}