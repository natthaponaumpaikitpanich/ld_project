<?php
// save_message.php (ฝั่ง Admin)
session_start();
require_once "../../../ld_db.php";

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $report_id = $_POST['report_id'] ?? null;
    $message = isset($_POST['message']) ? trim($_POST['message']) : '';
    // ID ของแอดมิน (ใช้จาก Session หรือค่า Default ตามที่คุณกำหนด)
    $sender_id = $_SESSION['user_id'] ?? '3d25e2d5-74fe-40c2-8a59-1530a0734da7'; 
    $attachment = null;

    if (!$report_id) {
        echo json_encode(['status' => 'error', 'message' => 'Missing report_id']);
        exit;
    }

    // 1. จัดการการอัปโหลดรูปภาพ (ถ้ามี)
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === 0) {
        // Path สำหรับเก็บไฟล์ (ถอยออกจาก adminpage/sidebar/chat/ ไปที่หน้าหลักของโปรเจกต์)
        $uploadDir = '../../../uploads/chat/';
        
        // ตรวจสอบและสร้างโฟลเดอร์ถ้ายังไม่มี
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $fileExtension = pathinfo($_FILES['attachment']['name'], PATHINFO_EXTENSION);
        $newFileName = uniqid('admin_msg_') . '.' . $fileExtension;
        $targetFile = $uploadDir . $newFileName;

        if (move_uploaded_file($_FILES['attachment']['tmp_name'], $targetFile)) {
            // บันทึก Path ลง DB ให้เริ่มจากโฟลเดอร์ uploads (เพื่อให้ฝั่ง Store เรียกใช้ง่ายๆ)
            $attachment = 'uploads/chat/' . $newFileName;
        }
    }

    // 2. ตรวจสอบว่ามีข้อมูลส่งมาหรือไม่ (มีข้อความ หรือ มีรูป อย่างใดอย่างหนึ่ง)
    if (!empty($message) || !empty($attachment)) {
        try {
            // บันทึกข้อความและ Path รูปภาพลงในตาราง report_messages
            $sql = "INSERT INTO report_messages (report_id, sender_id, sender_role, message, attachment) 
                    VALUES (?, ?, 'admin', ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$report_id, $sender_id, $message, $attachment]);
            
            // อัปเดตสถานะในตาราง reports เป็น in_progress เมื่อแอดมินตอบกลับ
            $pdo->prepare("UPDATE reports SET status = 'in_progress' WHERE id = ?")->execute([$report_id]);

            echo json_encode([
                'status' => 'success',
                'message' => 'บันทึกสำเร็จ',
                'attachment' => $attachment
            ]);
        } catch (PDOException $e) {
            echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'กรุณากรอกข้อความหรือเลือกรูปภาพ']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}