<?php
session_start();
require_once __DIR__ . "../../../ld_db.php";

$total_today = $conn->query("SELECT COUNT(*) AS num FROM orders WHERE DATE(created_at)=CURDATE()")->fetch_assoc()['num'];

$in_process = $conn->query("SELECT COUNT(*) AS num FROM orders WHERE status='in_process'")->fetch_assoc()['num'];

$ready = $conn->query("SELECT COUNT(*) AS num FROM orders WHERE status='ready'")->fetch_assoc()['num'];

$revenue = $conn->query("SELECT IFNULL(SUM(amount),0) AS total FROM payments WHERE DATE(paid_at)=CURDATE() AND status='success'")
    ->fetch_assoc()['total'];
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.css" rel="stylesheet
    </head>
    <body>
        <script src="../bootstrap/js/bootstrap.bundle.min.js"></script>
    </body>
    </html>