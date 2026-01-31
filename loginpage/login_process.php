<?php
session_start();
require_once "../ld_db.php";

$email    = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

/* ================= หา user ================= */
$stmt = $pdo->prepare("
    SELECT id, email, password_hash, display_name, role
    FROM users
    WHERE email = ?
    LIMIT 1
");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    $_SESSION['error'] = "ไม่พบผู้ใช้งาน";
    header("Location: login.php");
    exit;
}

/* ================= ตรวจรหัสผ่าน ================= */
if (!password_verify($password, $user['password_hash'])) {
    $_SESSION['error'] = "รหัสผ่านไม่ถูกต้อง";
    header("Location: login.php");
    exit;
}

/* ================= session กลาง ================= */
$_SESSION['user_id']   = $user['id'];
$_SESSION['role']      = $user['role'];
$_SESSION['user_name'] = $user['display_name'];

/* ================= redirect ตาม role ================= */
switch ($user['role']) {

    case 'platform_admin':
        header("Location: ../adminpage/sidebar/sidebar.php?link=Dashboard");
        exit;

    case 'store_owner':

        $stmt = $pdo->prepare("
            SELECT id, name
            FROM stores
            WHERE owner_id = ?
            LIMIT 1
        ");
        $stmt->execute([$user['id']]);
        $store = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$store) {
            // 🔥 แก้ตรงนี้
            header("Location: ../storepage/create_store.php");
            exit;
        }

        $_SESSION['store_id']   = $store['id'];
        $_SESSION['store_name'] = $store['name'];

        header("Location: ../storepage/index.php?link=orders");
        exit;

    case 'staff':

        $stmt = $pdo->prepare("
            SELECT s.id, s.name
            FROM store_staff ss
            JOIN stores s ON ss.store_id = s.id
            WHERE ss.user_id = ?
            LIMIT 1
        ");
        $stmt->execute([$user['id']]);
        $store = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$store) {
            $_SESSION['error'] = "บัญชีพนักงานยังไม่ผูกกับร้าน";
            header("Location: login.php");
            exit;
        }

        $_SESSION['store_id']   = $store['id'];
        $_SESSION['store_name'] = $store['name'];

        header("Location: ../staffpage/index.php?link=Home");
        exit;

    default:
        header("Location: ../userspage/index.php");
        exit;
}
