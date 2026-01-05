<?php

require_once __DIR__ . "../../ld_db.php";

/* ---------- orders วันนี้ ---------- */
$stmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM orders 
    WHERE DATE(created_at) = CURDATE()
");
$stmt->execute();
$total_today = $stmt->fetchColumn();

/* ---------- in_process ---------- */
$stmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM orders 
    WHERE status = 'in_process'
");
$stmt->execute();
$in_process = $stmt->fetchColumn();

/* ---------- ready ---------- */
$stmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM orders 
    WHERE status = 'ready'
");
$stmt->execute();
$ready = $stmt->fetchColumn();

/* ---------- revenue วันนี้ ---------- */
$stmt = $pdo->prepare("
    SELECT IFNULL(SUM(amount), 0)
    FROM payments
    WHERE DATE(paid_at) = CURDATE()
      AND status = 'success'
");
$stmt->execute();
$revenue = $stmt->fetchColumn();

/* ---------- auth ---------- */
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../loginpage/login.php");
    exit;
}

$allow = ['platform_admin', 'store_owner', 'staff'];
if (!in_array($_SESSION['role'] ?? '', $allow)) {
    header("Location: ../userspage/home.php");
    exit;
}
