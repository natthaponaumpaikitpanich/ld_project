<?php
session_start();
require_once "../../../ld_db.php";
include_once "../../assets/boostap.php";
$store_id = $_SESSION['store_id'] ?? null;
if (!$store_id) die("ไม่พบร้าน");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = $_POST['email'];

    // หา user
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        $error = "ไม่พบผู้ใช้";
    } else {

        // เช็คซ้ำ
        $stmt = $pdo->prepare("
            SELECT 1 FROM store_staff 
            WHERE store_id = ? AND user_id = ?
        ");
        $stmt->execute([$store_id, $user['id']]);

        if ($stmt->fetch()) {
            $error = "ผู้ใช้นี้อยู่ในร้านแล้ว";
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO store_staff (id, store_id, user_id, role)
                VALUES (?, ?, ?, 'staff')
            ");
            $stmt->execute([
                uniqid(),
                $store_id,
                $user['id']
            ]);

            header("Location: staff_index.php");
            exit;
        }
    }
}
?>

<div class="container mt-4">
    <h4>➕ เพิ่มพนักงาน</h4>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <form method="post">
        <div class="mb-3">
            <label>Email พนักงาน</label>
            <input type="email" name="email" class="form-control" required>
        </div>

        <button class="btn btn-primary">บันทึก</button>
        <a href="../../index.php?link=management" class="btn btn-secondary">ยกเลิก</a>
    </form>
</div>
