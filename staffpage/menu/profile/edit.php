<?php
session_start();
require_once "../../../ld_db.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    die("ไม่มีสิทธิ์ใช้งาน");
}

$staff_id = $_SESSION['user_id'];

$display_name = trim($_POST['display_name'] ?? '');
$phone        = trim($_POST['phone'] ?? '');
$email        = trim($_POST['email'] ?? '');
$new_password = $_POST['new_password'] ?? '';
$confirm      = $_POST['confirm_password'] ?? '';

if (!$display_name) {
    die("ชื่อที่แสดงห้ามว่าง");
}

try {
    $pdo->beginTransaction();

    // 1) update profile info
    $stmt = $pdo->prepare("
        UPDATE users
        SET display_name = ?,
            phone = ?,
            email = ?
        WHERE id = ?
          AND role = 'staff'
    ");
    $stmt->execute([
        $display_name,
        $phone,
        $email,
        $staff_id
    ]);

    // 2) ถ้ามีการเปลี่ยนรหัสผ่าน
    if ($new_password !== '') {
        if ($new_password !== $confirm) {
            throw new Exception("รหัสผ่านไม่ตรงกัน");
        }

        if (strlen($new_password) < 6) {
            throw new Exception("รหัสผ่านต้องอย่างน้อย 6 ตัวอักษร");
        }

        $hash = password_hash($new_password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("
            UPDATE users
            SET password = ?
            WHERE id = ?
        ");
        $stmt->execute([$hash, $staff_id]);
    }

    $pdo->commit();

    header("Location: ../../index.php?link=Profile");
    exit;
} catch (Exception $e) {
    $pdo->rollBack();
    die("เกิดข้อผิดพลาด: " . $e->getMessage());
}
