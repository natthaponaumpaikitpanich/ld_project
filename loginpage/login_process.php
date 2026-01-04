<?php
session_start();
require_once "../ld_db.php"; // mysqli => $conn

$email    = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';
$phone    = $_POST['phone'] ?? '';

// --------------------
// 1) หา user
// --------------------
$sql = "
    SELECT id, email, phone, password_hash, display_name, role
    FROM users
    WHERE email = ?
    LIMIT 1
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    $_SESSION['error'] = "ไม่พบผู้ใช้งาน";
    header("Location: login.php");
    exit;
}

// --------------------
// 2) ตรวจรหัสผ่าน (bcrypt)
// --------------------
if (!password_verify($password, $user['password_hash'])) {
    $_SESSION['error'] = "รหัสผ่านไม่ถูกต้อง";
    header("Location: login.php");
    exit;
}

// --------------------
// 3) set session กลาง
// --------------------
$_SESSION['user_id']   = $user['id'];
$_SESSION['role']      = $user['role'];
$_SESSION['user_name'] = $user['display_name'];

// --------------------
// 4) redirect ตาม role
// --------------------
switch ($user['role']) {

    case 'platform_admin':
        header("Location: ../adminpage/sidebar/sidebar.php?link=Dashboard");
        exit;

    case 'store_owner':

        $sql = "
            SELECT id, name
            FROM stores
            WHERE owner_id = ?
            LIMIT 1
        ";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $user['id']);
        $stmt->execute();
        $store = $stmt->get_result()->fetch_assoc();

        if (!$store) {
            $_SESSION['error'] = "ยังไม่มีร้านในระบบ";
            header("Location: login.php");
            exit;
        }

        $_SESSION['store_id']   = $store['id'];
        $_SESSION['store_name'] = $store['name'];

        header("Location: ../storepage/index.php");
        exit;

    case 'staff':

        $sql = "
            SELECT s.id, s.name
            FROM store_staff ss
            JOIN stores s ON ss.store_id = s.id
            WHERE ss.user_id = ?
            LIMIT 1
        ";
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

        header("Location: ../staffpage/index.php?link=Home");
        exit;

    case 'customer':
    default:
        header("Location: ../userspage/index.php");
        exit;
}
