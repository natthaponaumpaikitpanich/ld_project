<?php
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: ../loginpage/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

/* ===== USER INFO ===== */
$stmt = $pdo->prepare("
    SELECT display_name, email, phone, profile_image
    FROM users
    WHERE id = ?
");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("ไม่พบข้อมูลผู้ใช้");
}

/* ===== SAVE PROFILE ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $display_name = trim($_POST['display_name']);
    $email        = trim($_POST['email']);
    $phone        = trim($_POST['phone']);

    $profile_image = $user['profile_image'];

    if (!empty($_FILES['profile_image']['name'])) {
        $dir = "../uploads/profile/";
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $ext = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
        $filename = uniqid("profile_") . "." . $ext;
        $path = $dir . $filename;

        if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $path)) {
            $profile_image = "uploads/profile/" . $filename;
        }
    }

    $stmt = $pdo->prepare("
        UPDATE users
        SET display_name = ?, email = ?, phone = ?, profile_image = ?
        WHERE id = ?
    ");
    $stmt->execute([
        $display_name,
        $email,
        $phone,
        $profile_image,
        $user_id
    ]);

    exit;
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>โปรไฟล์ของฉัน | Laundry Platform</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<link href="../bootstrap/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<style>
body {
    font-family:'Kanit', sans-serif;
    background:#f6f7fb;
}

/* ===== CARD ===== */
.profile-card {
    border-radius: 22px;
    border: none;
    box-shadow: 0 15px 35px rgba(0,0,0,.1);
}

/* ===== PROFILE IMAGE ===== */
.profile-img {
    width:110px;
    height:110px;
    border-radius:50%;
    object-fit:cover;
    border:4px solid #fff;
    box-shadow: 0 8px 20px rgba(0,0,0,.15);
}

/* ===== HEADER ===== */
.profile-header {
    background: linear-gradient(135deg,#1e3c72,#2a5298);
    border-radius: 22px 22px 0 0;
    padding: 30px 20px;
    color:#fff;
    text-align:center;
}

/* ===== FORM ===== */
.form-control {
    border-radius: 12px;
}

.form-control:focus {
    border-color:#2a5298;
    box-shadow:0 0 0 .2rem rgba(42,82,152,.25);
}

/* ===== BUTTON ===== */
.btn-save {
    background:#2a5298;
    color:#fff;
    border-radius:14px;
    font-weight:500;
}

.btn-save:hover {
    background:#1e3c72;
}
</style>
</head>

<body>

<div class="container py-4 ">

<form method="post" enctype="multipart/form-data" id="profileForm">

<div class="card profile-card">

    <!-- HEADER -->
    <div class="profile-header">
        <img src="../<?= $user['profile_image'] ?: 'assets/default-user.png' ?>"
             class="profile-img mb-2"
             id="previewImg">
        <h6 class="fw-semibold mb-0">
            <?= htmlspecialchars($user['display_name']) ?>
        </h6>
        <small class="opacity-75">บัญชีลูกค้า</small>
    </div>

    <!-- BODY -->
    <div class="card-body p-4">

        <div class="mb-3">
            <label class="form-label">รูปโปรไฟล์</label>
            <input type="file"
                   name="profile_image"
                   class="form-control"
                   accept="image/*"
                   onchange="previewImage(event)">
        </div>

        <div class="mb-3">
            <label class="form-label">ชื่อ</label>
            <input type="text"
                   name="display_name"
                   class="form-control"
                   value="<?= htmlspecialchars($user['display_name']) ?>"
                   required>
        </div>

        <div class="mb-3">
            <label class="form-label">อีเมล</label>
            <input type="email"
                   name="email"
                   class="form-control"
                   value="<?= htmlspecialchars($user['email']) ?>"
                   required>
        </div>

        <div class="mb-4">
            <label class="form-label">เบอร์โทร</label>
            <input type="text"
                   name="phone"
                   class="form-control"
                   value="<?= htmlspecialchars($user['phone']) ?>">
        </div>

        <button type="submit" class="btn btn-save w-100 py-2" id="saveBtn">
            💾 บันทึกการเปลี่ยนแปลง
        </button>

    </div>
</div>

</form>

</div>

<script src="../bootstrap/js/bootstrap.bundle.min.js"></script>

<script>
/* ===== PREVIEW IMAGE ===== */
function previewImage(event) {
    const img = document.getElementById('previewImg');
    img.src = URL.createObjectURL(event.target.files[0]);
}

/* ===== LOADING BUTTON ===== */
const form = document.getElementById('profileForm');
const btn = document.getElementById('saveBtn');

form.addEventListener('submit', () => {
    btn.innerHTML = 'กำลังบันทึก...';
    btn.disabled = true;
});
</script>

</body>
</html>
