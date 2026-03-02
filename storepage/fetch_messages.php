<?php
session_start();
// ตรวจสอบ Path: ถ้าไฟล์นี้อยู่ใน storepage/system/ ต้องถอย 2 ชั้นไปเจอ ld_db.php
require_once "../ld_db.php"; 

$store_id = $_SESSION['user_id'] ?? null;

if (!$store_id) {
    echo "Session lost";
    exit;
}

// 1. หา Report ล่าสุด
$stmt = $pdo->prepare("SELECT id FROM reports WHERE store_id = ? ORDER BY created_at DESC LIMIT 1");
$stmt->execute([$store_id]);
$report = $stmt->fetch();

if ($report) {
    $report_id = $report['id'];
    $stmt = $pdo->prepare("SELECT * FROM report_messages WHERE report_id = ? ORDER BY created_at ASC");
    $stmt->execute([$report_id]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($messages as $msg) {
        $is_me = ($msg['sender_role'] === 'store' || $msg['sender_role'] === 'store_owner');
        $class = $is_me ? 'msg-me mb-2 align-self-end' : 'msg-admin mb-2 align-self-start';
        
        echo '<div class="' . $class . '" style="max-width: 80%;">';
        
        // --- ส่วนที่ 1: แสดงรูปภาพ (ถ้ามีข้อมูลใน attachment) ---
        if (!empty($msg['attachment'])) {
            // Path รูป: ถอยออกจาก system/ และ storepage/ ไปหาโฟลเดอร์ uploads ที่หน้าหลัก
            $img_url = "../" . $msg['attachment']; 
            echo '<div class="mb-1">';
            echo '<a href="' . $img_url . '" target="_blank">';
            echo '<img src="' . $img_url . '" style="max-width: 100%; border-radius: 10px; cursor: pointer;" class="img-fluid shadow-sm">';
            echo '</a>';
            echo '</div>';
        }

        // --- ส่วนที่ 2: แสดงข้อความ (ถ้ามี) ---
        if (!empty($msg['message'])) {
            echo '<div>' . nl2br(htmlspecialchars($msg['message'])) . '</div>';
        }
        
        echo '</div>';
    }
} else {
    echo '<div class="text-center text-muted">ยังไม่มีประวัติการสนทนา</div>';
}