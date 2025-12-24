<?php
session_start();
require_once "../ld_db.php"; // ต้องเป็น mysqli ($conn)

$email    = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';
$phone    = $_POST['phone'] ?? '';

// --------------------
// 1) หา user
// --------------------
$sql = "SELECT id, email, phone, password_hash, display_name, role 
        FROM users 
        WHERE email = ? AND phone = ?
        LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $email, $phone);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    $_SESSION['error'] = "Email หรือเบอร์โทรไม่ถูกต้อง";
    header("Location: login.php");
    exit;
}

// --------------------
// 2) ตรวจรหัสผ่าน
// (ตอนนี้ยังไม่ hash ตามที่คุณบอก)
// --------------------
if ($password !== $user['password_hash']) {
    $_SESSION['error'] = "รหัสผ่านไม่ถูกต้อง";
    header("Location: login.php");
    exit;
}

// --------------------
// 3) Set session พื้นฐาน
// --------------------
$_SESSION['user_id']   = $user['id'];
$_SESSION['role']      = $user['role'];
$_SESSION['user_name'] = $user['display_name'];

// --------------------
// 4) แยกตาม role
// --------------------
if ($user['role'] === 'platform_admin') {

    header("Location: ../adminpage/sidebar/sidebar.php?link=Dashboard");
    exit;

} elseif ($user['role'] === 'store_owner') {

    // 🔑 ดึงร้านของเจ้าของร้าน
    $sql = "SELECT id, name 
            FROM stores 
            WHERE owner_id = ? 
            LIMIT 1";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $user['id']);
    $stmt->execute();
    $store = $stmt->get_result()->fetch_assoc();

    if (!$store) {
        $_SESSION['error'] = "ไม่พบข้อมูลร้าน";
        header("Location: login.php");
        exit;
    }

    // ⭐ สำคัญมาก
    $_SESSION['store_id']   = $store['id'];
    $_SESSION['store_name'] = $store['name'];

    header("Location: ../storepage/index.php");
    exit;

} elseif ($user['role'] === 'staff') {

    // 🔑 staff ต้องสังกัดร้าน
    $sql = "SELECT s.id, s.name
            FROM store_staff ss
            JOIN stores s ON ss.store_id = s.id
            WHERE ss.user_id = ?
            LIMIT 1";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $user['id']);
    $stmt->execute();
    $store = $stmt->get_result()->fetch_assoc();

    if (!$store) {
        $_SESSION['error'] = "บัญชีพนักงานยังไม่ผูกกับร้าน";
        header("Location: login.php");
        exit;
    }

    $_SESSION['store_id']   = $store['id'];
    $_SESSION['store_name'] = $store['name'];

    // 👉 หน้าเดียวสำหรับ staff + rider
    header("Location: ../staffpage/index.php");
    exit;
}


else {

    header("Location: ../userspage/users.php");
    exit;} ?>