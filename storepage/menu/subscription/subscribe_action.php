<?php
session_start();
require_once "../../../ld_db.php";

$store_id = $_SESSION['store_id'] ?? null;
$plan_id  = $_POST['plan_id'] ?? null;

if (!$store_id || !$plan_id) {
    die('ข้อมูลไม่ครบ');
}

/* ===== กันสมัครซ้ำ ===== */
$chk = $pdo->prepare("
    SELECT id
    FROM store_subscriptions
    WHERE store_id = ?
      AND status IN ('waiting_approve','active')
");
$chk->execute([$store_id]);

if ($chk->fetch()) {
    die('คุณได้ส่งคำขอสมัครไปแล้ว');
}

/* ===== ตรวจไฟล์สลิป ===== */
if (empty($_FILES['slip_image']['name'])) {
    die('กรุณาอัปโหลดสลิป');
}

/* ===== upload slip ===== */
$uploadDir = "../../../uploads/slips/";
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$ext = pathinfo($_FILES['slip_image']['name'], PATHINFO_EXTENSION);
$filename = 'slip_' . time() . '_' . rand(100,999) . '.' . $ext;
$filepath = $uploadDir . $filename;

if (!move_uploaded_file($_FILES['slip_image']['tmp_name'], $filepath)) {
    die('อัปโหลดไฟล์ไม่สำเร็จ');
}

/* ===== insert subscription ===== */
$stmt = $pdo->prepare("
    INSERT INTO store_subscriptions
    (
        id,
        store_id,
        plan_id,
        plan,
        monthly_fee,
        slip_image,
        paid_at,
        status,
        created_at
    )
    SELECT
        UUID(),
        :store_id,
        p.id,
        p.name,
        p.amount,
        :slip_image,
        NOW(),
        'waiting_approve',
        NOW()
    FROM billing_plans p
    WHERE p.id = :plan_id
");

$stmt->execute([
    ':store_id'   => $store_id,
    ':plan_id'    => $plan_id,
    ':slip_image' => 'uploads/slips/' . $filename
]);

header("Location: ../../index.php");
exit;
