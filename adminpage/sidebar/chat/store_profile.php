<?php
require_once "../../../ld_db.php"; // ปรับ path ตามจริง

$store_id = $_GET['id'] ?? '';

// ดึงข้อมูลร้านค้าจากตาราง users
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'store_owner'");
$stmt->execute([$store_id]);
$store = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$store) {
    die("ไม่พบข้อมูลร้านค้า");
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>โปรไฟล์ร้านค้า - <?= htmlspecialchars($store['display_name']) ?></title>
    <link rel="stylesheet" href="bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { background-color: #f4f7f6; font-family: 'Kanit', sans-serif; }
        .profile-card { border: none; border-radius: 15px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
        .profile-img { width: 120px; height: 120px; object-fit: cover; border: 4px solid white; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
    </style>
</head>
<body>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card profile-card">
                <div class="card-body text-center p-5">
                    <img src="<?= $store['profile_image'] ?: 'https://ui-avatars.com/api/?name='.$store['display_name'] ?>" 
                         class="profile-img rounded-circle mb-3">
                    
                    <h2 class="fw-bold"><?= htmlspecialchars($store['display_name']) ?></h2>
                    <span class="badge bg-primary mb-4">Store Owner</span>

                    <div class="row text-start mt-4">
                        <div class="col-sm-6 mb-3">
                            <label class="text-muted small d-block">อีเมล</label>
                            <span class="fw-bold"><?= htmlspecialchars($store['email']) ?></span>
                        </div>
                        <div class="col-sm-6 mb-3">
                            <label class="text-muted small d-block">เบอร์โทรศัพท์</label>
                            <span class="fw-bold"><?= htmlspecialchars($store['phone'] ?: '-') ?></span>
                        </div>
                        <div class="col-sm-6 mb-3">
                            <label class="text-muted small d-block">สถานะบัญชี</label>
                            <span class="badge <?= $store['status'] == 'active' ? 'bg-success' : 'bg-danger' ?>">
                                <?= $store['status'] ?>
                            </span>
                        </div>
                        <div class="col-sm-6 mb-3">
                            <label class="text-muted small d-block">เป็นสมาชิกเมื่อ</label>
                            <span class="fw-bold"><?= date('d M Y', strtotime($store['created_at'])) ?></span>
                        </div>
                    </div>

                    <hr>
                    <div class="d-flex justify-content-center gap-2 mt-4">
                        <button class="btn btn-outline-secondary px-4 rounded-pill" onclick="history.back()">
                            <i class="bi bi-arrow-left me-1"></i> ย้อนกลับ
                        </button>
                        <a href="mailto:<?= $store['email'] ?>" class="btn btn-primary px-4 rounded-pill">
                            <i class="bi bi-envelope me-1"></i> ส่งอีเมลหาลูกค้า
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>