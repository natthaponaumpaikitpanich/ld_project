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
        // 1. เช็คว่ามีร้านที่ "อนุมัติแล้ว" หรือไม่
        $stmt = $pdo->prepare("
            SELECT s.id, s.name, ss.status
            FROM store_staff ss
            JOIN stores s ON ss.store_id = s.id
            WHERE ss.user_id = ? AND ss.status = 'active'
            LIMIT 1
        ");
        $stmt->execute([$user['id']]);
        $active_store = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($active_store) {
            // ถ้ามีร้านที่อนุมัติแล้ว เข้าหน้าหลักพนักงานเลย
            $_SESSION['store_id']   = $active_store['id'];
            $_SESSION['store_name'] = $active_store['name'];
            header("Location: ../staffpage/index.php?link=Home");
            exit;
        } else {
            // 2. ถ้ายังไม่มีร้านที่อนุมัติ เช็คว่ากำลัง "รออนุมัติ" อยู่หรือเปล่า
            $stmtPending = $pdo->prepare("SELECT id FROM store_staff WHERE user_id = ? AND status = 'pending'");
            $stmtPending->execute([$user['id']]);
            
            if ($stmtPending->fetch()) {
                // ถ้าส่งคำขอไปแล้ว ให้ไปหน้า "รอการยืนยัน"
                header("Location: ../staffpage/waiting_approval.php");
            } else {
                // ถ้ายังไม่มีร้านเลย ให้ไปหน้า "ค้นหาและสมัครเข้าร่วมร้าน"
                header("Location: ../staffpage/join_store.php");
            }
            exit;
        }

    default:
        header("Location: ../userspage/index.php");
        exit;
}
