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

<style>
:root {
    --primary-blue: #54a0ff;       /* ฟ้าโทนสบายตา */
    --soft-blue: #f0f7ff;          /* ฟ้าอ่อนมากสำหรับพื้นหลัง */
    --dark-text: #2f3640;          /* เทาเข้มเกือบดำ (อ่านง่ายกว่าดำสนิท) */
    --muted-text: #7f8c8d;
    --white: #ffffff;
    --card-radius: 24px;
    --inner-radius: 18px;
    --soft-shadow: 0 10px 25px rgba(84, 160, 255, 0.1);
}

body {
    background-color: var(--soft-blue);
    font-family: 'Kanit', sans-serif;
    color: var(--dark-text);
    padding-bottom: 40px;
}

/* App Bar ดีไซน์แบบลอย */
.appbar {
    background: rgba(255, 255, 255, 0.85);
    backdrop-filter: blur(10px);
    border-radius: var(--inner-radius);
    padding: 15px 20px;
    border: 1px solid rgba(255, 255, 255, 0.6);
    box-shadow: var(--soft-shadow);
    margin-bottom: 25px;
}

.profile-img {
    width: 48px;
    height: 48px;
    border-radius: 15px;
    object-fit: cover;
    border: 3px solid var(--white);
    box-shadow: 0 4px 10px rgba(0,0,0,0.05);
}

/* Hero Section: ใช้ Gradient นวลๆ ไม่ฉูดฉาด */
.hero {
    background: linear-gradient(135deg, #54a0ff 0%, #00d2ff 100%);
    border-radius: var(--card-radius);
    padding: 35px 25px !important;
    position: relative;
    overflow: hidden;
    box-shadow: 0 15px 30px rgba(84, 160, 255, 0.3);
    border: none;
}

.hero h5 { font-weight: 600; letter-spacing: 0.5px; }

/* Promotion Carousel */
.carousel {
    border-radius: var(--card-radius);
    overflow: hidden;
    box-shadow: var(--soft-shadow);
    border: 4px solid var(--white);
}

.promo-overlay {
    background: linear-gradient(to top, rgba(0,0,0,0.5), transparent);
    padding: 15px;
    border-bottom-left-radius: var(--card-radius);
    border-bottom-right-radius: var(--card-radius);
}

/* Menu Cards: ดีไซน์ Minimal */
.card-menu {
    background: var(--white);
    border: 1px solid rgba(84, 160, 255, 0.1);
    border-radius: var(--card-radius);
    padding: 25px 15px;
    text-align: center;
    transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    height: 100%;
    box-shadow: var(--soft-shadow);
}

.card-menu:active {
    transform: scale(0.92);
}

.card-menu i {
    font-size: 2.5rem;
    display: inline-block;
    margin-bottom: 10px;
    background: linear-gradient(135deg, var(--primary-blue), #00d2ff);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

.card-menu .fw-semibold {
    font-size: 1rem;
    color: var(--dark-text);
}

.card-menu small {
    color: var(--muted-text);
    font-size: 0.75rem;
}

/* ปุ่ม Logout แบบกลมมน */
.btn-logout {
    background: #ff7675;
    color: white;
    border-radius: 12px;
    padding: 8px 15px;
    border: none;
    font-size: 0.8rem;
    transition: 0.3s;
}

.btn-logout:hover {
    background: #d63031;
    color: white;
}
</style>

<div class="container py-3">

    <div class="appbar d-flex justify-content-between align-items-center animate__animated animate__fadeInDown">
        <div class="d-flex align-items-center gap-3">
            <img src="<?= htmlspecialchars($img) ?>" class="profile-img">
            <div>
                <div class="fw-bold" style="font-size: 1rem; line-height: 1.2;"><?= htmlspecialchars($user['display_name']) ?></div>
                <span class="badge rounded-pill" style="background: #e1f0ff; color: #54a0ff; font-weight: 400; font-size: 0.7rem;">Member</span>
            </div>
        </div>
        <a href="../loginpage/logout.php" class="btn-logout text-decoration-none">
            <i class="bi bi-power"></i> ออก
        </a>
    </div>

    <div class="hero text-white mb-4">
        <div class="position-relative" style="z-index: 2;">
            <h5 class="mb-1">สวัสดีครับ คุณ<?= htmlspecialchars($user['display_name']) ?> 👋</h5>
            <p class="opacity-75 small mb-0">วันนี้มีเสื้อผ้าให้เราดูแลกี่ชิ้นดีครับ?</p>
        </div>
        <div style="position:absolute; right: -20px; top: -10px; font-size: 100px; opacity: 0.1;">👕</div>
    </div>

    <?php if ($promotions): ?>
    <div class="d-flex justify-content-between align-items-center mb-2 px-2">
        <h6 class="fw-bold m-0" style="color: var(--dark-text);">โปรโมชั่นสำหรับคุณ</h6>
        <small style="color: var(--primary-blue);">ดูทั้งหมด</small>
    </div>
    <div id="promoCarousel" class="carousel slide mb-4" data-bs-ride="carousel">
        <div class="carousel-inner">
            <?php foreach ($promotions as $i => $p): ?>
            <div class="carousel-item <?= $i === 0 ? 'active' : '' ?>">
                <div class="position-relative">
                    <img src="../<?= htmlspecialchars($p['image']) ?>" class="d-block w-100" style="height: 160px; object-fit: cover;">
                    <div class="promo-overlay position-absolute bottom-0 start-0 end-0 text-white">
                        <div class="fw-bold small"><?= htmlspecialchars($p['title']) ?></div>
                        <small style="font-size: 0.7rem; opacity: 0.8;"><?= htmlspecialchars($p['store_name']) ?></small>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="row g-3 px-1">
        <div class="col-6">
            <a href="menu/orders/create_order.php" class="text-decoration-none">
                <div class="card-menu">
                    <i class="bi bi-basket-fill"></i>
                    <div class="fw-semibold">สั่งซักผ้า</div>
                    <small>จองคิวซักด่วน</small>
                </div>
            </a>
        </div>
        <div class="col-6">
            <a href="index.php?link=orders" class="text-decoration-none">
                <div class="card-menu">
                    <i class="bi bi-search-heart"></i>
                    <div class="fw-semibold">สถานะผ้า</div>
                    <small>เช็คความคืบหน้า</small>
                </div>
            </a>
        </div>
        <div class="col-6">
            <a href="menu/contact/contact_store.php" class="text-decoration-none">
                <div class="card-menu">
                    <i class="bi bi-chat-left-text-fill"></i>
                    <div class="fw-semibold">แจ้งปัญหา</div>
                    <small>คุยกับแอดมิน</small>
                </div>
            </a>
        </div>
        <div class="col-6">
            <a href="index.php?link=profile" class="text-decoration-none">
                <div class="card-menu">
                    <i class="bi bi-gear-wide-connected"></i>
                    <div class="fw-semibold">ตั้งค่า</div>
                    <small>ข้อมูลสมาชิก</small>
                </div>
            </a>
        </div>
    </div>

    <div class="mt-4">
        <?php include_once "body.php"; ?>
    </div>
</div>