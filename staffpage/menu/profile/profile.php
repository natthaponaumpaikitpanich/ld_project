<?php


if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    die("ไม่มีสิทธิ์เข้าถึง");
}

$user_id = $_SESSION['user_id'];

/* ================= UPDATE PROFILE ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {

    $display_name = trim($_POST['display_name']);
    $email        = trim($_POST['email']);
    $phone        = trim($_POST['phone']);

    if (!$display_name || !$email || !$phone) {
        die('ข้อมูลไม่ครบ');
    }

    $new_image = null;

    if (!empty($_FILES['profile_image']['name'])) {

        $allowed = ['image/jpeg','image/png'];
        if (!in_array($_FILES['profile_image']['type'], $allowed)) {
            die('อนุญาตเฉพาะ JPG / PNG');
        }

        $ext = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '.' . $ext;

        $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/ld_project/uploads/profile/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        if (!move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_dir.$filename)) {
            die('อัปโหลดรูปไม่สำเร็จ');
        }

        $new_image = 'uploads/profile/' . $filename;
    }

    if ($new_image) {
        $stmt = $pdo->prepare("
            UPDATE users
            SET display_name=?, email=?, phone=?, profile_image=?
            WHERE id=?
        ");
        $stmt->execute([$display_name,$email,$phone,$new_image,$user_id]);
    } else {
        $stmt = $pdo->prepare("
            UPDATE users
            SET display_name=?, email=?, phone=?
            WHERE id=?
        ");
        $stmt->execute([$display_name,$email,$phone,$user_id]);
    }
    header("Location:index.php?link=Profile");
    exit;

}

/* ================= FETCH PROFILE ================= */
$stmt = $pdo->prepare("
    SELECT 
        u.display_name,
        u.email,
        u.phone,
        u.profile_image,
        s.name AS store_name,
        s.address AS store_address,
        s.phone AS store_phone
    FROM users u
    JOIN store_staff ss ON ss.user_id = u.id
    JOIN stores s ON s.id = ss.store_id
    WHERE u.id = ?
");
$stmt->execute([$user_id]);
$profile = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$profile) {
    die('ไม่พบข้อมูลพนักงาน');
}

/* ================= IMAGE PATH ================= */
if ($profile['profile_image']) {
    $img_path = '/ld_project/' . ltrim($profile['profile_image'], '/');
    if (!file_exists($_SERVER['DOCUMENT_ROOT'] . $img_path)) {
        $img_path = '/ld_project/assets/img/user.png';
    }
} else {
    $img_path = '/ld_project/assets/img/user.png';
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>โปรไฟล์พนักงาน</title>
<link href="../../assets/bootstrap/css/bootstrap.min.css" rel="stylesheet">
<style>
body{background:#f4f6f9}
.profile-wrapper{min-height:100vh;display:flex;align-items:center;justify-content:center}
.profile-card{max-width:900px;width:100%;border-radius:16px;overflow:hidden}
.profile-header{background:linear-gradient(135deg,#0d6efd,#20c997);color:#fff;padding:1.5rem 2rem}
</style>
</head>

<body>

<div class="profile-wrapper">
<div class="card shadow profile-card">

<div class="profile-header d-flex justify-content-between align-items-center">
    <h4 class="mb-0 fw-bold">👤 โปรไฟล์พนักงาน</h4>
    <button onclick="enableEdit()" class="btn btn-light btn-sm fw-semibold">
        ✏️ แก้ไขโปรไฟล์
    </button>
</div>

<div class="card-body p-4">

<!-- ================= VIEW MODE ================= -->
<div id="viewMode">
<div class="row g-4 align-items-center">

<div class="col-md-4 text-center">
    <img src="<?= htmlspecialchars($img_path) ?>"
         class="rounded-circle border border-4 border-white shadow mb-3"
         style="width:160px;height:160px;object-fit:cover;">
    <span class="badge rounded-pill px-3 py-2 bg-<?= $profile['status']==='active'?'success':'secondary' ?>">
        <?= strtoupper($profile['status']) ?>
    </span>
</div>

<div class="col-md-8">
    <h3 class="fw-bold mb-3"><?= htmlspecialchars($profile['display_name']) ?></h3>

    <div class="row g-3 mb-3">
        <div class="col-sm-6">
            <div class="p-3 bg-light rounded">
                <small class="text-muted">📧 อีเมล</small>
                <div class="fw-semibold"><?= htmlspecialchars($profile['email']) ?></div>
            </div>
        </div>
        <div class="col-sm-6">
            <div class="p-3 bg-light rounded">
                <small class="text-muted">📞 เบอร์โทร</small>
                <div class="fw-semibold"><?= htmlspecialchars($profile['phone']) ?></div>
            </div>
        </div>
    </div>

    <div class="p-3 rounded" style="background:#f8f9fa;border-left:4px solid #0d6efd;">
        <h6 class="fw-bold mb-2">🏪 ร้านที่สังกัด</h6>
        <div class="fw-semibold"><?= htmlspecialchars($profile['store_name']) ?></div>
        <div class="text-muted small"><?= htmlspecialchars($profile['store_address']) ?></div>
        <div class="small">☎ <?= htmlspecialchars($profile['store_phone']) ?></div>
    </div>
</div>

</div>
</div>

<!-- ================= EDIT MODE ================= -->
<form id="editMode" method="post" enctype="multipart/form-data" style="display:none">
<input type="hidden" name="update_profile" value="1">

<div class="row g-4 align-items-center">

<div class="col-md-4 text-center">
    <img src="<?= htmlspecialchars($img_path) ?>"
         class="rounded-circle border border-4 border-white shadow mb-3"
         style="width:160px;height:160px;object-fit:cover;">
    <input type="file" name="profile_image" class="form-control form-control-sm">
</div>

<div class="col-md-8">
    <div class="mb-3">
        <label class="form-label small">ชื่อแสดงผล</label>
        <input type="text" name="display_name" class="form-control"
               value="<?= htmlspecialchars($profile['display_name']) ?>" required>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-sm-6">
            <label class="form-label small">อีเมล</label>
            <input type="email" name="email" class="form-control"
                   value="<?= htmlspecialchars($profile['email']) ?>" required>
        </div>
        <div class="col-sm-6">
            <label class="form-label small">เบอร์โทร</label>
            <input type="text" name="phone" class="form-control"
                   value="<?= htmlspecialchars($profile['phone']) ?>" required>
        </div>
    </div>

    <div class="d-flex gap-2">
        <button class="btn btn-primary">💾 บันทึก</button>
        <button type="button" onclick="cancelEdit()" class="btn btn-secondary">ยกเลิก</button>
    </div>
</div>

</div>
</form>

</div>
</div>
</div>

<script>
function enableEdit(){
    viewMode.style.display='none';
    editMode.style.display='block';
}
function cancelEdit(){
    editMode.style.display='none';
    viewMode.style.display='block';
}
</script>

<script src="../../assets/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
