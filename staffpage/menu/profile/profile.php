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

        $allowed = ['image/jpeg', 'image/png'];
        if (!in_array($_FILES['profile_image']['type'], $allowed)) {
            die('อนุญาตเฉพาะ JPG / PNG');
        }

        $ext = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '.' . $ext;

        $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/ld_project/uploads/profile/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        if (!move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_dir . $filename)) {
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
        $stmt->execute([$display_name, $email, $phone, $new_image, $user_id]);
    } else {
        $stmt = $pdo->prepare("
            UPDATE users
            SET display_name=?, email=?, phone=?
            WHERE id=?
        ");
        $stmt->execute([$display_name, $email, $phone, $user_id]);
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
        u.status,
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
$profile_img = ($profile['profile_image']) ? '/ld_project/' . ltrim($profile['profile_image'], '/') : '/ld_project/assets/img/user.png';
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Profile | Smart Delivery</title>
    <link href="../../assets/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        :root {
            --soft-blue: #eef2f7;
            --brand-blue: #007bff;
            --brand-gradient: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
            --glass: rgba(255, 255, 255, 0.95);
        }

        body { background: var(--soft-blue); font-family: 'Kanit', sans-serif; color: #334155; }

        .profile-container { min-height: 100vh; padding: 40px 15px; }

        /* Main Card Design */
        .glass-card {
            background: var(--glass);
            border: none;
            border-radius: 24px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.05);
            overflow: hidden;
            transition: all 0.4s ease;
        }

        /* Header Visual */
        .profile-cover {
            height: 140px;
            background: var(--brand-gradient);
            position: relative;
        }

        .avatar-wrapper {
            position: relative;
            margin-top: -70px;
            display: inline-block;
        }

        .profile-img-big {
            width: 140px; height: 140px;
            object-fit: cover;
            border: 5px solid #fff;
            border-radius: 40px; /* Modern Squircle */
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }

        .status-dot {
            position: absolute;
            bottom: 8px; right: 8px;
            width: 20px; height: 20px;
            background: #22c55e;
            border: 3px solid #fff;
            border-radius: 50%;
        }

        /* Info Style */
        .info-label { font-size: 0.8rem; color: #94a3b8; font-weight: 500; text-transform: uppercase; }
        .info-value { font-size: 1rem; color: #1e293b; font-weight: 600; }

        .store-box {
            background: #f8fafc;
            border-radius: 18px;
            padding: 20px;
            border: 1px dashed #cbd5e1;
        }

        /* Form Styling */
        .form-control {
            border-radius: 12px;
            padding: 12px 15px;
            border: 1px solid #e2e8f0;
            background: #fcfcfc;
        }
        .form-control:focus {
            box-shadow: 0 0 0 4px rgba(37, 117, 252, 0.1);
            border-color: var(--brand-blue);
        }

        /* Animation */
        .fade-in { animation: fadeIn 0.5s ease; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

        /* Custom File Input */
        .file-upload-btn {
            position: absolute;
            bottom: 0; right: -10px;
            background: #fff;
            width: 35px; height: 35px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            cursor: pointer;
            color: var(--brand-blue);
        }
    </style>
</head>

<body>

<div class="container profile-container">
    <div class="row justify-content-center">
        <div class="col-lg-8 col-md-10 mt-5">
            
            <div class="glass-card shadow-lg">
                <div class="profile-cover d-flex align-items-end justify-content-end p-3">
                    <button id="btnEdit" onclick="enableEdit()" class="btn btn-white btn-sm bg-white fw-bold rounded-pill px-3 shadow-sm">
                        <i class="bi bi-pencil-square me-1"></i> แก้ไขข้อมูล
                    </button>
                </div>

                <div class="card-body px-4 pb-5">
                    
                    <div class="text-center mb-4">
                        <div class="avatar-wrapper">
                            <img id="previewImg" src="<?= htmlspecialchars($profile_img) ?>" class="profile-img-big animate__animated animate__zoomIn">
                            <div class="status-dot"></div>
                            <label for="profile_image_input" id="uploadCircle" class="file-upload-btn" style="display:none;">
                                <i class="bi bi-camera-fill"></i>
                            </label>
                        </div>
                        <h2 class="fw-bold mt-3 mb-1"><?= htmlspecialchars($profile['display_name']) ?></h2>
                        <span class="badge bg-soft-primary text-primary rounded-pill px-3">พนักงานจัดส่งมืออาชีพ</span>
                    </div>

                    <hr class="my-4 opacity-25">

                    <div id="viewMode" class="fade-in">
                        <div class="row g-4">
                            <div class="col-md-6 text-start px-4">
                                <div class="mb-4">
                                    <div class="info-label"><i class="bi bi-envelope me-2"></i>อีเมลส่วนตัว</div>
                                    <div class="info-value"><?= htmlspecialchars($profile['email']) ?></div>
                                </div>
                                <div class="mb-4">
                                    <div class="info-label"><i class="bi bi-telephone me-2"></i>เบอร์โทรศัพท์</div>
                                    <div class="info-value"><?= htmlspecialchars($profile['phone']) ?></div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="store-box">
                                    <h6 class="fw-bold text-primary mb-3"><i class="bi bi-shop me-2"></i>สาขาที่สังกัด</h6>
                                    <div class="fw-bold text-dark mb-1"><?= htmlspecialchars($profile['store_name']) ?></div>
                                    <p class="text-muted small mb-2"><?= htmlspecialchars($profile['store_address']) ?></p>
                                    <div class="badge bg-white text-dark border shadow-sm">ID: <?= str_pad($user_id, 5, '0', STR_PAD_LEFT) ?></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <form id="editMode" method="post" enctype="multipart/form-data" style="display:none" class="fade-in">
                        <input type="hidden" name="update_profile" value="1">
                        <input type="file" id="profile_image_input" name="profile_image" hidden onchange="previewFile()">

                        <div class="row g-3">
                            <div class="col-12">
                                <label class="info-label mb-2">ชื่อที่ใช้แสดงผล</label>
                                <input type="text" name="display_name" class="form-control" value="<?= htmlspecialchars($profile['display_name']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="info-label mb-2">อีเมลติดต่อ</label>
                                <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($profile['email']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="info-label mb-2">เบอร์โทรศัพท์</label>
                                <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($profile['phone']) ?>" required>
                            </div>
                        </div>

                        <div class="mt-5 d-flex gap-3 justify-content-center">
                            <button type="submit" class="btn btn-primary px-5 rounded-pill fw-bold shadow-sm">บันทึกการเปลี่ยนแปลง</button>
                            <button type="button" onclick="cancelEdit()" class="btn btn-outline-secondary px-4 rounded-pill">ยกเลิก</button>
                        </div>
                    </form>

                </div>
            </div>

            

        </div>
    </div>
</div>

<script>
    function enableEdit() {
        document.getElementById('viewMode').style.display = 'none';
        document.getElementById('btnEdit').style.display = 'none';
        document.getElementById('editMode').style.display = 'block';
        document.getElementById('uploadCircle').style.display = 'flex';
    }

    function cancelEdit() {
        location.reload(); // วิธีที่ง่ายที่สุดในการล้างค่า preview
    }

    function previewFile() {
        const preview = document.getElementById('previewImg');
        const file = document.querySelector('input[type=file]').files[0];
        const reader = new FileReader();

        reader.onloadend = function () {
            preview.src = reader.result;
        }

        if (file) {
            reader.readAsDataURL(file);
        }
    }
</script>

</body>
</html>