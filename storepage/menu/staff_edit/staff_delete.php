<?php
session_start();
require_once "../../../ld_db.php";
if ($_SESSION['role'] !== 'store_owner') {
    die('no permission');
}
$store_id = $_SESSION['store_id'] ?? null;
$id = $_GET['id'] ?? null;

if (!$store_id || !$id) die("ข้อมูลไม่ครบ");

$stmt = $pdo->prepare("
    DELETE FROM store_staff 
    WHERE id = ? AND store_id = ?
");
$stmt->execute([$id, $store_id]);

header("Location: ../../index.php?link=management");
exit;
