<?php
session_start();
require_once "../../../ld_db.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'store_owner') {
    die('no permission');
}

$store_id = $_SESSION['store_id'] ?? null;

$name     = trim($_POST['store_name'] ?? '');
$address  = trim($_POST['address'] ?? '');
$phone    = trim($_POST['phone'] ?? '');
$timezone = $_POST['timezone'] ?? 'Asia/Bangkok';

if ($name === '') {
    die('ชื่อร้านห้ามว่าง');
}

$stmt = $pdo->prepare("
    UPDATE stores
    SET name=?, address=?, phone=?, timezone=?
    WHERE id=?
");
$stmt->execute([$name,$address,$phone,$timezone,$store_id]);

header("location: ../../index.php?link=profile");
exit;
