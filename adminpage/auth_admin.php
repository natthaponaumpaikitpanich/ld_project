<?php
session_start();

if(!isset($_SESSION['user_id'])){
    header("Location: ../loginpage/login.php");
    exit;
}

$allow = ["platform_admin", "store_owner", "staff"];

if(!in_array($_SESSION['role'], $allow)){
    header("Location: ../userspage/home.php");
    exit;
}
?>
