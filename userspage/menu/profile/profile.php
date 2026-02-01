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
    :root {
        --primary-sky: #0ea5e9;
        --dark-sky: #1e40af;
        --soft-bg: #f8fafc;
    }

    body {
        background: var(--soft-bg);
        font-family: 'Kanit', sans-serif;
    }

    /* Profile Header & Image */
    .profile-header-gradient {
        background: linear-gradient(135deg, #0ea5e9 0%, #2563eb 100%);
        height: 160px;
        border-radius: 0 0 40px 40px;
        position: relative;
    }

    .avatar-wrapper {
        position: relative;
        width: 120px;
        height: 120px;
        margin: -60px auto 15px;
    }

    .profile-img-main {
        width: 120px;
        height: 120px;
        border-radius: 35px;
        object-fit: cover;
        border: 5px solid #fff;
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        background: #fff;
    }

    .upload-btn {
        position: absolute;
        bottom: -5px;
        right: -5px;
        background: #fff;
        width: 38px;
        height: 38px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        color: var(--primary-sky);
        transition: 0.3s;
    }

    .upload-btn:hover {
        background: var(--primary-sky);
        color: #fff;
        transform: scale(1.1);
    }

    /* Form Styling */
    .custom-card {
        border: none;
        border-radius: 30px;
        background: rgba(255, 255, 255, 0.9);
        backdrop-filter: blur(10px);
        box-shadow: 0 20px 40px rgba(0,0,0,0.03);
    }

    .input-group-custom {
        background: #f1f5f9;
        border-radius: 15px;
        padding: 12px 18px;
        margin-bottom: 18px;
        border: 2px solid transparent;
        transition: 0.3s;
    }

    .input-group-custom:focus-within {
        border-color: var(--primary-sky);
        background: #fff;
        box-shadow: 0 10px 20px rgba(14, 165, 233, 0.05);
    }

    .input-group-custom input {
        background: transparent;
        border: none;
        outline: none;
        width: 100%;
        font-weight: 500;
        color: #334155;
    }

    .input-label {
        font-size: 0.8rem;
        color: #64748b;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 5px;
        display: block;
    }

    /* Save Button */
    .btn-save-glow {
        background: linear-gradient(135deg, #0ea5e9 0%, #2563eb 100%);
        color: white;
        border: none;
        border-radius: 18px;
        padding: 15px;
        font-weight: 600;
        box-shadow: 0 10px 20px rgba(37, 99, 235, 0.2);
        transition: 0.3s;
    }

    .btn-save-glow:hover {
        transform: translateY(-3px);
        box-shadow: 0 15px 30px rgba(37, 99, 235, 0.3);
        color: white;
    }

    /* Back Button */
    .btn-back {
        color: #94a3b8;
        text-decoration: none;
        font-size: 0.9rem;
        transition: 0.3s;
    }
    .btn-back:hover { color: var(--dark-sky); }
</style>

<body>

<div class="profile-header-gradient">
    <div class="container text-center pt-4">
        <a href="../../index.php" class="btn-back float-start text-white opacity-75">
            <i class="bi bi-chevron-left"></i> กลับ
        </a>
        <h4 class="text-white fw-bold">ข้อมูลส่วนตัว</h4>
    </div>
</div>

<div class="container" style="margin-top: -20px; padding-bottom: 50px;">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            
            <form action="" method="POST" enctype="multipart/form-data" id="profileForm">
                
                <div class="avatar-wrapper">
                    <img src="../<?= $user['profile_image'] ?: 'assets/default-user.png' ?>" 
                         class="profile-img-main" id="previewImg">
                    <label for="file-upload" class="upload-btn">
                        <i class="bi bi-camera-fill"></i>
                    </label>
                    <input id="file-upload" type="file" name="profile_image" hidden accept="image/*" onchange="previewImage(event)">
                </div>

                <div class="text-center mb-4">
                    <h5 class="fw-bold mb-0"><?= htmlspecialchars($user['display_name']) ?></h5>
                    <p class="text-muted small">ID: #<?= str_pad($user_id, 5, '0', STR_PAD_LEFT) ?></p>
                </div>

                <div class="card custom-card">
                    <div class="card-body p-4">
                        
                        <span class="input-label">ชื่อที่แสดง</span>
                        <div class="input-group-custom d-flex align-items-center">
                            <i class="bi bi-person me-3 text-primary"></i>
                            <input type="text" name="display_name" value="<?= htmlspecialchars($user['display_name']) ?>" required>
                        </div>

                        <span class="input-label">อีเมลติดต่อ</span>
                        <div class="input-group-custom d-flex align-items-center">
                            <i class="bi bi-envelope me-3 text-primary"></i>
                            <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                        </div>

                        <span class="input-label">เบอร์โทรศัพท์</span>
                        <div class="input-group-custom d-flex align-items-center">
                            <i class="bi bi-phone me-3 text-primary"></i>
                            <input type="text" name="phone" value="<?= htmlspecialchars($user['phone']) ?>">
                        </div>

                        <button type="submit" class="btn btn-save-glow w-100 mt-3" id="saveBtn">
                            <span id="btnText">บันทึกข้อมูลทั้งหมด</span>
                        </button>

                    </div>
                </div>
            </form>

        </div>
    </div>
</div>

<div class="toast-container position-fixed bottom-0 start-50 translate-middle-x p-3">
    <div id="successToast" class="toast align-items-center text-white bg-success border-0 rounded-pill" role="alert">
        <div class="d-flex">
            <div class="toast-body">
                <i class="bi bi-check-circle-fill me-2"></i> บันทึกข้อมูลสำเร็จแล้ว!
            </div>
        </div>
    </div>
</div>

<script src="../bootstrap/js/bootstrap.bundle.min.js"></script>

<script>
function previewImage(event) {
    const reader = new FileReader();
    reader.onload = function() {
        const output = document.getElementById('previewImg');
        output.src = reader.result;
    }
    reader.readAsDataURL(event.target.files[0]);
}

// ระบบบันทึกแบบ AJAX (ลูกเล่นเจ๋งๆ)
const form = document.getElementById('profileForm');
const btn = document.getElementById('saveBtn');
const btnText = document.getElementById('btnText');
const toast = new bootstrap.Toast(document.getElementById('successToast'));

form.onsubmit = async (e) => {
    e.preventDefault();
    
    btn.disabled = true;
    btnText.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> กำลังบันทึก...';

    const formData = new FormData(form);
    const response = await fetch(window.location.href, {
        method: 'POST',
        body: formData
    });

    if (response.ok) {
        toast.show();
        btnText.innerHTML = 'บันทึกสำเร็จ!';
        setTimeout(() => {
            btn.disabled = false;
            btnText.innerHTML = 'บันทึกข้อมูลทั้งหมด';
        }, 2000);
    }
};
</script>

</body>
</html>
