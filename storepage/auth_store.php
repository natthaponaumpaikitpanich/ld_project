<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'store_owner') {
    header("Location: ../loginpage/login.php");
    exit;
}