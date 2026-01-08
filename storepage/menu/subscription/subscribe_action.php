<?php
session_start();
require_once "../../../ld_db.php";

$store_id = $_SESSION['store_id'] ?? null;
$plan_id  = $_POST['plan_id'] ?? null;

if (!$store_id || !$plan_id || empty($_FILES['slip_image']['name'])) {
    die('ข้อมูลไม่ครบ');
}

/* ===== upload slip ===== */
$dir = "../../../uploads/slips/";
if (!is_dir($dir)) mkdir($dir,0777,true);

$ext = pathinfo($_FILES['slip_image']['name'], PATHINFO_EXTENSION);
$filename = 'slip_' . time() . '_' . rand(100,999) . '.' . $ext;
$path = $dir . $filename;

move_uploaded_file($_FILES['slip_image']['tmp_name'], $path);

/* ===== insert subscription ===== */
$stmt = $pdo->prepare("
    INSERT INTO store_subscriptions
    (id, store_id, plan_id, plan, monthly_fee, slip_image, paid_at, status)
    SELECT
        UUID(),
        :store_id,
        p.id,
        p.name,
        p.amount,
        :slip,
        NOW(),
        'waiting_approve'
    FROM billing_plans p
    WHERE p.id = :plan_id
");

$stmt->execute([
    ':store_id' => $store_id,
    ':plan_id'  => $plan_id,
    ':slip'     => 'uploads/slips/'.$filename
]);

header("Location: ../../index.php");
exit;
