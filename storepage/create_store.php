<?php
session_start();
require_once "../ld_db.php";

/* ================= AUTH ================= */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'store_owner') {
    die("no permission");
}

$user_id = $_SESSION['user_id'];

/* ================= เช็คว่ามีร้านแล้วหรือยัง ================= */
$stmt = $pdo->prepare("
    SELECT id
    FROM stores
    WHERE owner_id = ?
    LIMIT 1
");
$stmt->execute([$user_id]);
$exists = $stmt->fetchColumn();

if ($exists) {
    header("Location: index.php");
    exit;
}

/* ================= SUBMIT ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name    = trim($_POST['name'] ?? '');
    $phone   = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');

    if (!$name || !$phone) {
        $error = "กรุณากรอกข้อมูลให้ครบ";
    } else {

        $store_id = $pdo->query("SELECT UUID()")->fetchColumn();

        $stmt = $pdo->prepare("
            INSERT INTO stores
            (id, owner_id, name, phone, address, status)
            VALUES (?, ?, ?, ?, ?, 'pending')
        ");
        $stmt->execute([
            $store_id,
            $user_id,
            $name,
            $phone,
            $address
        ]);

        /* set session ร้าน */
        $_SESSION['store_id']   = $store_id;
        $_SESSION['store_name'] = $name;

        header("Location: index.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>สร้างร้านซักอบรีด</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="icon" href="../../../image/3.jpg">
<link href="../bootstrap/css/bootstrap.min.css" rel="stylesheet">

<style>
body {
            font-family: 'Kanit', sans-serif;
            background: linear-gradient(135deg, #4eaadfff, #1cc88a);
            min-height: 100vh;
    display: flex;
    align-items: center;
        }
.card{
    border:none;
    border-radius:16px;
}
</style>
</head>

<body>

<div class="container d-flex justify-content-center align-items-center"
     style="min-height:100vh">

<div class="card shadow p-4" style="width:420px">

    <h4 class="fw-bold text-center mb-3">🏪 สร้างร้านซักอบรีด</h4>
    <p class="text-muted text-center mb-4">
        กรุณากรอกข้อมูลร้านเพื่อเริ่มใช้งานระบบ
    </p>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <form method="post">

        <div class="mb-3">
            <label class="form-label">ชื่อร้าน</label>
            <input type="text"
                   name="name"
                   class="form-control"
                   required>
        </div>

        <div class="mb-3">
            <label class="form-label">เบอร์โทรร้าน</label>
            <input type="text"
                   name="phone"
                   class="form-control"
                   required>
        </div>

        <div class="mb-3">
            <label class="form-label">ที่อยู่ร้าน</label>
            <textarea name="address"
                      class="form-control"
                      rows="3"></textarea>
        </div>

        <button class="btn btn-primary w-100">
            ➕ สร้างร้าน
        </button>

    </form>

</div>
</div>

<script src="../bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
