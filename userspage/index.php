<?php
session_start();
require_once "../ld_db.php";

/* ===== AUTH ===== */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: ../loginpage/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

include_once "assets/head.php";

/* ===== FORCE PAYMENT CHECK ===== */
$stmt = $pdo->prepare("
    SELECT id
    FROM orders
    WHERE customer_id = ?
      AND status = 'ready'
      AND payment_status != 'paid'
    ORDER BY created_at DESC
    LIMIT 1
");
$stmt->execute([$user_id]);
$unpaidOrder = $stmt->fetch(PDO::FETCH_ASSOC);

if ($unpaidOrder) {
    header("Location: payments.php?force=1");
    exit;
}

/* =========================
   PROMOTIONS
   ========================= */
$sqlPromotion = "
    SELECT 
        p.*,
        s.name AS store_name
    FROM promotions p
    JOIN stores s ON p.store_id = s.id
    WHERE p.status = 'active'
      AND p.store_id IS NOT NULL
      AND p.audience IN ('all','customers','store_specific')
      AND p.start_date <= NOW()
      AND p.end_date >= NOW()
    ORDER BY p.created_at DESC
    LIMIT 10
";
$stmtPromotion = $pdo->prepare($sqlPromotion);
$stmtPromotion->execute();
$promotions = $stmtPromotion->fetchAll(PDO::FETCH_ASSOC);

/* =========================
   USER INFO
   ========================= */
$stmtUser = $pdo->prepare("
    SELECT display_name, email, phone, profile_image
    FROM users
    WHERE id = ?
");
$stmtUser->execute([$user_id]);
$user = $stmtUser->fetch(PDO::FETCH_ASSOC);

/* ===== กัน error ถ้า user ไม่เจอ ===== */
if (!$user) {
    session_destroy();
    header("Location: ../loginpage/login.php");
    exit;
}

/* =========================
   PROFILE IMAGE HANDLE
   ========================= */
$img = $user['profile_image'] ?? '';

if (!$img) {
    $img = '../assets/default-user.png';
} elseif (str_starts_with($img, 'http')) {
    // Google profile image → ใช้ URL ตรง
} else {
    // รูปที่อัปโหลดเอง
    $img = '../' . ltrim($img, '/');
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>หน้าหลักลูกค้า</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;600&display=swap" rel="stylesheet">
<link href="assets/style.css" rel="stylesheet">
</head>
<style></style>
<body>  
<div class="container py-3">

<!-- ===== APP BAR ===== -->
<div class="appbar d-flex justify-content-between align-items-center md-3">
    <div style="margin-left:10px;">
        <div class="fw-semibold md-3"><?= htmlspecialchars($user['display_name']) ?></div>
        <small class="text-muted">ลูกค้า</small>
    </div>

    <div class="d-flex align-items-center gap-2"style="margin-right:10px;">
        <img src="<?= htmlspecialchars($img) ?>" class="profile-img">
        <a href="../loginpage/logout.php" class="btn btn-outline-danger btn-sm">
            ออก
        </a>
    </div>
</div>

<!-- ===== HERO ===== -->
<div class="hero text-white p-4 mb-4">
    <h5>🧺 สวัสดี <?= htmlspecialchars($user['display_name']) ?></h5>
    <small class="opacity-75">
        ส่งผ้า · ติดตามสถานะ · ชำระเงิน ได้ในที่เดียว
    </small>
</div>

<!-- ===== PROMOTIONS ===== -->
<?php if ($promotions): ?>
<div id="promoCarousel" class="carousel slide mb-4" data-bs-ride="carousel">
    <div class="carousel-inner">
        <?php foreach ($promotions as $i => $p): ?>
        <div class="carousel-item <?= $i === 0 ? 'active' : '' ?>">
            <div class="position-relative">
                <img src="../<?= htmlspecialchars($p['image']) ?>" class="w-100">
                <div class="promo-overlay">
                    <div class="fw-semibold"><?= htmlspecialchars($p['title']) ?></div>
                    <small><?= htmlspecialchars($p['store_name']) ?></small>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- ===== QUICK MENU ===== -->
<div class="row g-3">

<div class="col-6">
<a href="menu/orders/create_order.php" class="text-decoration-none text-dark">
<div class="card-menu">
    <i class="bi bi-basket-fill text-primary"></i>
    <div class="fw-semibold mt-2">ส่งซักผ้า</div>
    <small class="text-muted">เริ่มออเดอร์ใหม่</small>
</div>
</a>
</div>

<div class="col-6">
<a href="index.php?link=orders" class="text-decoration-none text-dark">
<div class="card-menu">
    <i class="bi bi-clock-history text-success"></i>
    <div class="fw-semibold mt-2">ติดตามสถานะ</div>
    <small class="text-muted">ดูผ้าที่กำลังซัก</small>
</div>
</a>
</div>

<div class="col-6">
<a href="menu/contact/contact_store.php" class="text-decoration-none text-dark">
<div class="card-menu">
    <i class="bi bi-chat-dots-fill text-info"></i>
    <div class="fw-semibold mt-2">ติดต่อร้าน</div>
    <small class="text-muted">สอบถาม / แจ้งปัญหา</small>
</div>
</a>
</div>

<div class="col-6">
<a href="index.php?link=profile" class="text-decoration-none text-dark">
<div class="card-menu">
    <i class="bi bi-person-circle text-secondary"></i>
    <div class="fw-semibold mt-2">โปรไฟล์</div>
    <small class="text-muted">ข้อมูลส่วนตัว</small>
</div>
</a>
</div>

</div>

<?php include_once "body.php"; ?>

</div>

<script src="../bootstrap/js/bootstrap.bundle.min.js"></script>
<script>
document.querySelectorAll('.card-menu').forEach(card => {
    card.addEventListener('click', () => {
        card.style.transform = 'scale(.96)';
        setTimeout(() => card.style.transform = '', 120);
    });
});
</script>

</body>
</html>
