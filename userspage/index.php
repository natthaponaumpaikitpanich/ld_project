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

<style>
body {
    font-family: 'Kanit', sans-serif;
}
.profile-img {
    width:42px;
    height:42px;
    border-radius:50%;
    object-fit:cover;
}
.card-menu {
    border-radius:16px;
    transition:.2s;
}
.card-menu:hover {
    transform: translateY(-3px);
}
</style>
</head>

<body>
<div class="container py-3">

<!-- ===== APP BAR ===== -->
<div class="appbar d-flex justify-content-between align-items-center mb-3">
    <div>
        <div class="fw-semibold"><?= htmlspecialchars($user['display_name']) ?></div>
        <small class="text-muted">ลูกค้า</small>
    </div>

    <div class="d-flex align-items-center gap-2">
        <img src="<?= htmlspecialchars($img) ?>" class="profile-img">
        <a href="../loginpage/logout.php"
           class="btn btn-outline-danger btn-sm">
            ออก
        </a>
    </div>
</div>

<!-- ===== HERO ===== -->
<div class="hero mb-3 p-3 rounded text-white"
     style="background:linear-gradient(135deg,#0d6efd,#20c997)">
    <h5>🧺 ยินดีต้อนรับสู่ระบบซักอบรีด</h5>
    <div class="small opacity-75">
        ส่งผ้า · ติดตามงาน · ชำระเงิน
    </div>
</div>

<!-- ===== PROMOTION ===== -->
<?php if ($promotions): ?>
<div id="promoCarousel" class="carousel slide mb-3" data-bs-ride="carousel">
    <div class="carousel-inner">
        <?php foreach ($promotions as $i => $p): ?>
        <div class="carousel-item <?= $i === 0 ? 'active' : '' ?>">
            <img src="../<?= htmlspecialchars($p['image']) ?>"
                 class="d-block w-100 rounded">
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- ===== QUICK MENU ===== -->
<div class="row g-3">

<div class="col-6">
<a href="menu/orders/create_order.php" class="text-decoration-none text-dark">
<div class="card card-menu text-center p-4">
    <i class="bi bi-basket text-primary fs-3"></i>
    <div class="fw-semibold mt-2">ส่งซักผ้า</div>
</div>
</a>
</div>

<div class="col-6">
<a href="index.php?link=orders" class="text-decoration-none text-dark">
<div class="card card-menu text-center p-4">
    <i class="bi bi-clock-history text-success fs-3"></i>
    <div class="fw-semibold mt-2">ติดตามสถานะ</div>
</div>
</a>
</div>

<div class="col-6">
<a href="payments.php" class="text-decoration-none text-dark">
<div class="card card-menu text-center p-4">
    <i class="bi bi-credit-card text-warning fs-3"></i>
    <div class="fw-semibold mt-2">การชำระเงิน</div>
</div>
</a>
</div>

<div class="col-6">
<a href="index.php?link=profile" class="text-decoration-none text-dark">
<div class="card card-menu text-center p-4">
    <i class="bi bi-person-circle text-info fs-3"></i>
    <div class="fw-semibold mt-2">โปรไฟล์</div>
</div>
</a>
</div>

</div>

<?php include_once "body.php"; ?>

</div>

<script src="../bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
