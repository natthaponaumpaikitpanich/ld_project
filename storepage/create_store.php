<?php
session_start();
require_once "../ld_db.php";

/* AUTH */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'store_owner') {
    die("no permission");
}

$user_id = $_SESSION['user_id'];

/* check existing */
$stmt = $pdo->prepare("SELECT id FROM stores WHERE owner_id=? LIMIT 1");
$stmt->execute([$user_id]);
$exists = $stmt->fetchColumn();

if ($exists) {
    header("Location: index.php");
    exit;
}

/* submit */
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
        $stmt->execute([$store_id,$user_id,$name,$phone,$address]);

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

<link rel="icon" href="../image/3.jpg">
<link href="../bootstrap/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;600;700&display=swap" rel="stylesheet">

<style>
body{
    font-family:'Kanit',sans-serif;
    background:linear-gradient(135deg,#1e3c72,#2a5298);
    min-height:100vh;
    display:flex;
    align-items:center;
}

.card{
    border:none;
    border-radius:22px;
    box-shadow:0 20px 40px rgba(0,0,0,.25);
}

.form-control{
    border-radius:12px;
}
.form-control:focus{
    border-color:#2a5298;
    box-shadow:0 0 0 .2rem rgba(42,82,152,.25);
}

.btn-main{
    background:#1e3c72;
    color:#fff;
    border-radius:14px;
    font-weight:600;
}
.btn-main:hover{
    background:#2a5298;
}
</style>
</head>

<body>

<div class="container">
<div class="row justify-content-center">
<div class="col-md-5">

<div class="card p-4">

    <div class="text-center mb-4">
        <h4 class="fw-bold mb-1">🏪 สร้างร้านซักอบรีด</h4>
        <small class="text-muted">
            เริ่มต้นใช้งานระบบจัดการร้านของคุณ
        </small>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger text-center"><?= $error ?></div>
    <?php endif; ?>

    <form method="post" id="storeForm">

        <div class="mb-3">
            <label class="form-label">ชื่อร้าน</label>
            <input type="text" name="name"
                   class="form-control"
                   placeholder="เช่น สะดวกซัก"
                   required>
        </div>

        <div class="mb-3">
            <label class="form-label">เบอร์โทรร้าน</label>
            <input type="text" name="phone"
                   class="form-control"
                   placeholder="เช่น 089xxxxxxx"
                   required>
        </div>

        <div class="mb-4">
            <label class="form-label">ที่อยู่ร้าน</label>
            <textarea name="address"
                      class="form-control"
                      rows="3"
                      placeholder="บ้านเลขที่ ซอย ถนน ตำบล อำเภอ จังหวัด"></textarea>
        </div>

        <button class="btn btn-main w-100 py-2" id="submitBtn">
            ➕ สร้างร้าน
        </button>

    </form>

</div>

</div>
</div>
</div>

<script src="../bootstrap/js/bootstrap.bundle.min.js"></script>

<script>
const form = document.getElementById('storeForm');
const btn = document.getElementById('submitBtn');

form.addEventListener('submit',()=>{
    btn.innerText='กำลังสร้างร้าน...';
    btn.disabled=true;
});
</script>

</body>
</html>
