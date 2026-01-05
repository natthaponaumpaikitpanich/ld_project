<?php
session_start();
require_once __DIR__ . "../../../ld_db.php";

$link = $_GET['link'] ?? 'home';
$report_id = $_GET['id'] ?? null;

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
    SELECT IFNULL(SUM(amount),0)
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">   
    </head>
    <style>
        body {
            font-family: 'Kanit', sans-serif;
        }
</style>
    <body>
        <script src="../bootstrap/js/bootstrap.bundle.min.js"></script>
    </body>
    </html>