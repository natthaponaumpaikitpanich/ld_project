<?php
require '../../ld_db.php'; // เรียกไฟล์เชื่อมต่อฐานข้อมูล

// ตรวจสอบว่ามีส่ง id มามั้ย
if (!isset($_GET['id'])) {
    die("Error: Missing promotion ID.");
}

$promo_id = $_GET['id'];

// เตรียมคำสั่ง SQL เพื่อลบข้อมูล
$sql = "DELETE FROM promotions WHERE id = :id";
$stmt = $pdo->prepare($sql);

if ($stmt->execute([':id' => $promo_id])) {
    // ลบสำเร็จ -> กลับหน้า index
    header("Location: ../sidebar/sidebar.php?link=promotion");
    exit();
} else {
    echo "Error: Unable to delete promotion.";
}
