<?php
session_start();
require_once "../../../ld_db.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'store_owner') {
    die('no permission');
}

$store_id = $_POST['store_id'] ?? null;
$email    = trim($_POST['email']);
$phone    = trim($_POST['phone']);

if (!$store_id || !$email || !$phone) {
    die('ข้อมูลไม่ครบ');
}

/* === หา user เดิม === */
$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user) {
    $user_id = $user['id'];
} else {
    // สร้าง user ใหม่
    $stmt = $pdo->query("SELECT UUID()");
    $user_id = $stmt->fetchColumn();

    $stmt = $pdo->prepare("
        INSERT INTO users (id, email, phone, role, status)
        VALUES (?, ?, ?, 'staff', 'active')
    ");
    $stmt->execute([$user_id, $email, $phone]);
}

/* === ผูกเข้าร้าน === */
$stmt = $pdo->prepare("
    INSERT INTO store_staff (id, store_id, user_id, role)
    VALUES (UUID(), ?, ?, 'staff')
");
$stmt->execute([$store_id, $user_id]);

header("Location: ../../index.php?link=management");
exit;
