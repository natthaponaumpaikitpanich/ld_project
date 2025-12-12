<?php
// adminpage/auth_admin.php
session_start();
require_once __DIR__ . "../../ld_db.php";

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

<style>
    body {
        margin-left: 260px !important;
        background: #f7f7f7;
    }

    .sidebar {
        width: 260px;
        height: 100vh;
        position: fixed;
        left: 0;
        top: 0;
        background: #0d0d1a;
        padding: 20px;
        color: white;
        overflow-y: auto;
        z-index: 1000;
    }

    .menu-item {
        display: block;
        padding: 10px 0;
        color: #e0e0e0;
        text-decoration: none;
    }

    .menu-item:hover {
        color: #ffffff;
        padding-left: 5px;
        transition: 0.2s;
    }

    .content-wrapper {
        margin-left: 260px;
        padding: 25px;
    }
</style>
<head>
    <meta charset="UTF-8">
    <link rel="icon" href="../../image/3.jpg">
    </link>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

    <title>ซักอบรีด</title>
</head>


<body>
    
</body>

</html>