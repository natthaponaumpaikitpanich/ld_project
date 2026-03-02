<?php
session_start();
require_once "../../../ld_db.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['store_id'])) {
    $store_id = $_SESSION['store_id'];
    $name = $_POST['store_name'];
    $phone = $_POST['store_phone'];
    $address = $_POST['store_address'];
    
    // 1. อัปเดตข้อมูลพื้นฐานก่อน
    $stmt = $pdo->prepare("UPDATE stores SET name = ?, phone = ?, address = ? WHERE id = ?");
    $stmt->execute([$name, $phone, $address, $store_id]);

    // 2. ตรวจสอบว่ามีการอัปโหลดไฟล์ QR Code มาหรือไม่
    if (isset($_FILES['promptpay_qr']) && $_FILES['promptpay_qr']['error'] === 0) {
        $upload_dir = '../uploads/qr_codes/';
        if (!is_dir($upload_dir)) { mkdir($upload_dir, 0777, true); }

        $file_ext = pathinfo($_FILES['promptpay_qr']['name'], PATHINFO_EXTENSION);
        $file_name = 'qr_' . $store_id . '_' . time() . '.' . $file_ext;
        $upload_path = $upload_dir . $file_name;
        $db_path = '../uploads/qr_codes/' . $file_name;

        if (move_uploaded_file($_FILES['promptpay_qr']['tmp_name'], $upload_path)) {
            // อัปเดต Path ของรูปภาพใน Database (ตาราง stores ฟิลด์ promptpay_qr)
            $stmtImg = $pdo->prepare("UPDATE stores SET promptpay_qr = ? WHERE id = ?");
            $stmtImg->execute([$db_path, $store_id]);
        }
    }

    echo json_encode(['success' => true]);
}
?>