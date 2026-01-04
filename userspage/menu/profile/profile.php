<?php

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: ../loginpage/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

/* ดึงข้อมูลผู้ใช้ */
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

/* บันทึกข้อมูล */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $display_name = trim($_POST['display_name']);
    $email        = trim($_POST['email']);
    $phone        = trim($_POST['phone']);

    $profile_image = $user['profile_image'];

    /* อัปโหลดรูปใหม่ */
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
<head>


<style>
body {
    font-family:'Kanit', sans-serif;
}
.profile-img {
    width:100px;
    height:100px;
    border-radius:50%;
    object-fit:cover;
}
</style>
</head>

<body>

<div class="container py-4">

    <!-- HEADER -->

    <form method="post" enctype="multipart/form-data">

        <div class="card shadow-sm">
            <div class="card-body">

                <div class="text-center mb-4">
                    <img src="../<?= $user['profile_image'] ?: 'assets/default-user.png' ?>"
                         class="profile-img mb-2">
                    <div>
                        <input type="file" name="profile_image" class="form-control form-control-sm">
                    </div>
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

                <div class="mb-3">
                    <label class="form-label">เบอร์โทร</label>
                    <input type="text"
                           name="phone"
                           class="form-control"
                           value="<?= htmlspecialchars($user['phone']) ?>">
                </div>

                <button type="submit" class="btn btn-primary w-100">
                    💾 บันทึกการเปลี่ยนแปลง
                </button>
            </div>
        </div>

    </form>

</div>

<script src="../bootstrap/js/bootstrap.bundle.min.js"></script>
</body>

