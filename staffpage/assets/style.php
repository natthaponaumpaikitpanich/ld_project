<?php

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