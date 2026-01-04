<?php
session_start();
require_once "../ld_db.php";

/* =====================
   UUID v4
===================== */
function uuidv4() {
    return sprintf(
        '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0xffff)
    );
}

/* =====================
   รับค่าจากฟอร์ม
===================== */
$display_name = $_POST['display_name'] ?? null;
$email        = $_POST['email'] ?? null;
$phone        = $_POST['phone'] ?? null;
$password     = $_POST['password'] ?? null;
$role         = $_POST['role'] ?? 'customer';
$detail = $_POST['detail'] ?? null;

/* =====================
   validate ขั้นต้น
===================== */
if (!$display_name || !$email || !$password || !$role) {
    echo '
<script>
    alert("ข้อมูลไม่ครบ");
    window.location.href = "register.php";
</script>
';
}

/* จำกัด role */
$allowed_roles = ['customer','staff','store_owner'];
if (!in_array($role, $allowed_roles)) {
    die("role ไม่ถูกต้อง");
}

/* =====================
   hash password (ถูกต้องแล้ว)
===================== */
$password_hash = password_hash($password, PASSWORD_DEFAULT);

/* =====================
   detail (optional)
===================== */


/* =====================
   upload รูป (optional)
===================== */
$profile_image = null;

if (!empty($_FILES['profile_image']['name'])) {
    $dir = "../uploads/profile/";
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }

    $ext = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg','jpeg','png','webp'];

    if (!in_array($ext, $allowed)) {
        die("ไฟล์รูปไม่ถูกต้อง");
    }

    $filename = uuidv4() . "." . $ext;
    $path = $dir . $filename;

    if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $path)) {
        $profile_image = "uploads/profile/" . $filename;
    }
}

/* =====================
   insert DB
===================== */
try {
    $stmt = $pdo->prepare("
        INSERT INTO users (
            id,
            email,
            phone,
            password_hash,
            display_name,
            role,
            profile_image,
            detail
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?
        )
    ");

    $stmt->execute([
        uuidv4(),
        $email,
        $phone,
        $password_hash,
        $display_name,
        $role,
        $profile_image,
        $detail
    ]);

    header("Location: ../loginpage/login.php?register=success");
    exit;

} catch (PDOException $e) {

    if ($e->getCode() == 23000) {
        die("อีเมลนี้ถูกใช้งานแล้ว");
    }

    die("สมัครสมาชิกไม่สำเร็จ: " . $e->getMessage());
}
