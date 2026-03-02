<?php
session_start();
require_once "../ld_db.php";

$store_id = $_SESSION['user_id'];
$message = $_POST['message'] ?? '';
$attachment = null;

// --- 1. ต้องหา Report ID ก่อนเสมอ ---
$stmt = $pdo->prepare("SELECT id FROM reports WHERE store_id = ? AND status != 'resolved' ORDER BY created_at DESC LIMIT 1");
$stmt->execute([$store_id]);
$report = $stmt->fetch();

if (!$report) {
    // ถ้าไม่มี Report ให้สร้างใหม่
    $stmt = $pdo->prepare("INSERT INTO reports (store_id, title, status) VALUES (?, 'แชทสอบถาม', 'new')");
    $stmt->execute([$store_id]);
    $report_id = $pdo->lastInsertId();
} else {
    $report_id = $report['id'];
}

// --- 2. จัดการรูปภาพ (ปรับ Path เป็น ../../) ---
if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === 0) {
    $ext = pathinfo($_FILES['attachment']['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '.' . $ext;
    
    // ออกจาก system/ และ storepage/ ไปที่หน้าหลักของโปรเจกต์
    $uploadDir = '../uploads/chat/'; 
    $uploadPath = $uploadDir . $filename;
    
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    if (move_uploaded_file($_FILES['attachment']['tmp_name'], $uploadPath)) {
        // เก็บลง DB โดยเริ่มจากชื่อโฟลเดอร์ (เพื่อให้แอดมินดึงไปใช้ง่ายๆ)
        $attachment = 'uploads/chat/' . $filename;
    }
}

// --- 3. ตรวจสอบว่ามีทั้งข้อความหรือรูปภาพ ถึงจะยอมให้บันทึก ---
if (!empty($message) || !empty($attachment)) {
    try {
        $stmt = $pdo->prepare("INSERT INTO report_messages (report_id, sender_id, sender_role, message, attachment) VALUES (?, ?, 'store', ?, ?)");
        $stmt->execute([$report_id, $store_id, $message, $attachment]);
        echo json_encode(['status' => 'success']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}