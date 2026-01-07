<?php
$user_id = $_SESSION['user_id'];
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'store_owner') {
    die("ไม่มีสิทธิ์เข้าถึง");
}

/* ================= UPDATE PROFILE + QR ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {

    $display_name = trim($_POST['display_name']);
    $email        = trim($_POST['email']);
    $phone        = trim($_POST['phone']);

    if (!$display_name || !$email || !$phone) {
        die('ข้อมูลไม่ครบ');
    }

    /* ---------- upload profile image ---------- */
    $new_profile_image = null;
    if (!empty($_FILES['profile_image']['name'])) {
        $allowed = ['image/jpeg','image/png'];
        if (!in_array($_FILES['profile_image']['type'], $allowed)) {
            die('รูปโปรไฟล์ต้องเป็น JPG / PNG');
        }

        $ext = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
        $filename = uniqid().'_profile.'.$ext;

        $dir = $_SERVER['DOCUMENT_ROOT'].'/ld_project/uploads/profile/';
        if (!is_dir($dir)) mkdir($dir,0777,true);

        move_uploaded_file($_FILES['profile_image']['tmp_name'], $dir.$filename);
        $new_profile_image = 'uploads/profile/'.$filename;
    }

    if ($new_profile_image) {
        $stmt = $pdo->prepare("
            UPDATE users
            SET display_name=?, email=?, phone=?, profile_image=?
            WHERE id=?
        ");
        $stmt->execute([$display_name,$email,$phone,$new_profile_image,$user_id]);
    } else {
        $stmt = $pdo->prepare("
            UPDATE users
            SET display_name=?, email=?, phone=?
            WHERE id=?
        ");
        $stmt->execute([$display_name,$email,$phone,$user_id]);
    }

    exit;
}
/* ================= FETCH PROFILE + STORE ================= */
$stmt = $pdo->prepare("
    SELECT 
        u.display_name,
        u.email,
        u.phone,
        u.profile_image,
        s.name AS store_name,
        s.address AS store_address,
        s.phone AS store_phone,
        s.promptpay_qr
    FROM users u
    JOIN store_staff ss ON ss.user_id = u.id
    JOIN stores s ON s.id = ss.store_id
    WHERE u.id = ?
");
$stmt->execute([$user_id]);
$profile = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$profile) die('ไม่พบข้อมูล');

/* ---------- image paths ---------- */
function img($path,$fallback){
    if ($path) {
        $full = '/ld_project/'.ltrim($path,'/');
        if (file_exists($_SERVER['DOCUMENT_ROOT'].$full)) return $full;
    }
    return $fallback;
}

$profile_img = img($profile['profile_image'],'/ld_project/assets/img/user.png');
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>โปรไฟล์เจ้าของร้าน</title>
<link href="../../assets/bootstrap/css/bootstrap.min.css" rel="stylesheet">
<style>
body{background:#f4f6f9}
.profile-wrapper{display:flex;justify-content:center}
.profile-card{max-width:800px;width:100%;border-radius:16px}
.profile-header{background:linear-gradient(135deg,#0d6efd,#20c997);color:#fff;padding:1.5rem 2rem}
</style>
</head>

<body>
<div class="mt-4 profile-wrapper">
<div class="card shadow profile-card">

<div class="profile-header d-flex justify-content-between align-items-center">
    <h4 class="mb-0 fw-bold">👤 โปรไฟล์เจ้าของร้าน</h4>
    <button onclick="enableEdit()" class="btn btn-light btn-sm">✏️ แก้ไข</button>
</div>

<div class="card-body p-4">

<!-- VIEW -->
<div id="viewMode">
<div class="row g-4">
<div class="col-md-4 text-center">
    <img src="<?= $profile_img ?>" class="rounded-circle shadow mb-3"
         style="width:160px;height:160px;object-fit:cover">
</div>
<div class="col-md-8">
    <h3 class="fw-bold"><?= htmlspecialchars($profile['display_name']) ?></h3>
    <p>📧 <?= htmlspecialchars($profile['email']) ?></p>
    <p>📞 <?= htmlspecialchars($profile['phone']) ?></p>

    <hr>
    <h6 class="fw-bold">🏪 ร้าน</h6>
    <p><?= htmlspecialchars($profile['store_name']) ?></p>
</div>
</div>
</div>

<!-- EDIT -->
<form id="editMode" method="post" action="menu/profile/profile_action.php" enctype="multipart/form-data" style="display:none">
<input type="hidden" name="update_profile" value="1">

<div class="row g-4">
<div class="col-md-4 text-center">
    <img src="<?= $profile_img ?>" class="rounded-circle mb-2"
         style="width:160px;height:160px;object-fit:cover">
    <input type="file" name="profile_image" class="form-control form-control-sm">
</div>

<div class="col-md-8">
    <input name="display_name" class="form-control mb-2"
           value="<?= htmlspecialchars($profile['display_name']) ?>" required>
    <input name="email" type="email" class="form-control mb-2"
           value="<?= htmlspecialchars($profile['email']) ?>" required>
    <input name="phone" class="form-control mb-3"
           value="<?= htmlspecialchars($profile['phone']) ?>" required>

    <button class="btn btn-primary">💾 บันทึก</button>
    <button type="button" onclick="cancelEdit()" class="btn btn-secondary">ยกเลิก</button>
</div>
</div>
</form>

</div>
</div>
</div>

<script>
function enableEdit(){viewMode.style.display='none';editMode.style.display='block'}
function cancelEdit(){editMode.style.display='none';viewMode.style.display='block'}
</script>
</body>
</html>
