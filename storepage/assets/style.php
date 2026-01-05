<?php

require_once __DIR__ . "../../../ld_db.php";
require_once 'auth_store.php';

/* ---------- ออเดอร์วันนี้ ---------- */
$stmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM orders 
    WHERE DATE(created_at) = CURDATE()
");
$stmt->execute();
$total_today = $stmt->fetchColumn();

/* ---------- กำลังซัก ---------- */
$stmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM orders 
    WHERE status = 'in_process'
");
$stmt->execute();
$in_process = $stmt->fetchColumn();

/* ---------- ซักเสร็จ ---------- */
$stmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM orders 
    WHERE status = 'ready'
");
$stmt->execute();
$ready = $stmt->fetchColumn();

/* ---------- รายได้วันนี้ ---------- */
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
?>
   
   <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link href="../../../bootstrap/css/bootstrap.min.css" rel="stylesheet">
        <link rel="icon" href="../image/3.jpg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.css" rel="stylesheet">
    </head>
    <body>
        <script src="../bootstrap/js/bootstrap.bundle.min.js"></script>
    </body>
    </html>