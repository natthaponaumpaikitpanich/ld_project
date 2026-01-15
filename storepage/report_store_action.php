<?php
session_start();
require_once "../ld_db.php";

if (!isset($_SESSION['store_id'])) {
    die('no permission');
}

$store_id = $_SESSION['store_id'];
$title    = trim($_POST['title'] ?? '');
$message  = trim($_POST['message'] ?? '');

if (!$title || !$message) {
    header("Location: ../index.php");
    exit;
}

$stmt = $pdo->prepare("
    INSERT INTO reports (store_id, title, message, status)
    VALUES (?, ?, ?, 'new')
");
$stmt->execute([
    $store_id,
    $title,
    $message
]);

header("Location: index.php?link=management&report_sent=1");
exit;
