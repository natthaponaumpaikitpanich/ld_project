<?php
session_start();
require_once "../../../ld_db.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'platform_admin') {
    die('no permission');
}

$subscription_id = $_POST['subscription_id'] ?? null;
$action = $_POST['action'] ?? null;
$reject_reason = $_POST['reject_reason'] ?? null;
$admin_id = $_SESSION['user_id'];

if (!$subscription_id) die('invalid request');

// ดึงข้อมูล store_id จาก subscription ก่อน
$stmtStore = $pdo->prepare("SELECT store_id FROM store_subscriptions WHERE id = ?");
$stmtStore->execute([$subscription_id]);
$sub_data = $stmtStore->fetch();
$store_id = $sub_data['store_id'];

$pdo->beginTransaction();
try {
    if ($action === 'approve') {
        // ... (Code อนุมัติเดิมของคุณ) ...
        $stmt = $pdo->prepare("UPDATE store_subscriptions SET status = 'active', approved_by = ? WHERE id = ?");
        $stmt->execute([$admin_id, $subscription_id]);
    } 
    elseif ($action === 'reject') {
        // 1. อัปเดตสถานะเป็น Rejected
        $stmt = $pdo->prepare("UPDATE store_subscriptions SET status = 'rejected', reject_reason = ? WHERE id = ?");
        $stmt->execute([$reject_reason, $subscription_id]);

        // 2. สร้างห้องแชทในตาราง reports (ถ้ายังไม่มีเคสสำหรับการสมัครสมาชิก)
        // เช็คก่อนว่ามีห้องแชทหัวข้อนี้ที่ยังเปิดอยู่ไหม
        $stmtCheckChat = $pdo->prepare("SELECT id FROM reports WHERE store_id = ? AND title LIKE '%การสมัครสมาชิก%' AND status != 'resolved' LIMIT 1");
        $stmtCheckChat->execute([$store_id]);
        $report = $stmtCheckChat->fetch();

        if ($report) {
            $report_id = $report['id'];
        } else {
            // ถ้าไม่มี ให้สร้างห้องใหม่
            $stmtNewChat = $pdo->prepare("INSERT INTO reports (store_id, title, status, created_at) VALUES (?, ?, 'new', NOW())");
            $stmtNewChat->execute([$store_id, 'แจ้งผลการสมัครสมาชิกไม่ผ่านอนุมัติ']);
            $report_id = $pdo->lastInsertId();
        }

        // 3. ส่งข้อความอัตโนมัติจาก Admin เข้าไปในแชท
        $chat_msg = "ระบบขอแจ้งว่าการสมัครสมาชิกของท่านไม่ผ่านการอนุมัติเนื่องจาก: \n" . $reject_reason . "\n\nหากแก้ไขแล้วหรือมีข้อสงสัย สามารถพิมพ์สอบถามได้ที่นี่ครับ";
        $stmtMsg = $pdo->prepare("INSERT INTO report_messages (report_id, sender_id, sender_role, message, created_at) VALUES (?, ?, 'admin', ?, NOW())");
        $stmtMsg->execute([$report_id, $admin_id, $chat_msg]);
    }
    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    die($e->getMessage());
}

header("Location: ../approve_list.php");
exit;