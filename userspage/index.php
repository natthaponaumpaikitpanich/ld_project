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
/* --- เพิ่มลูกเล่นพื้นหลังและแสงเงา --- */
body {
    position: relative;
    overflow-x: hidden;
}

/* วงกลมแสงฟุ้งๆ ด้านหลัง (ทำให้หน้าดูไม่จืด) */
body::before {
    content: "";
    position: fixed;
    top: -10%;
    right: -10%;
    width: 400px;
    height: 400px;
    background: radial-gradient(circle, rgba(0, 97, 255, 0.05) 0%, rgba(255, 255, 255, 0) 70%);
    z-index: -1;
}

/* --- Card Hover Effect แบบนุ่มนวล --- */
.stat-card {
    transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1) !important;
    border: 1px solid rgba(255, 255, 255, 0.5) !important;
}

.stat-card:hover {
    transform: translateY(-8px) scale(1.02) !important;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.1) !important;
    border-color: var(--primary-blue) !important;
}

/* --- ลิฟต์เมนู (Quick Buttons) ให้ดูมีมิติ --- */
.quick-btn {
    position: relative;
    overflow: hidden;
}

.quick-btn::after {
    content: "";
    position: absolute;
    top: 50%;
    left: 50%;
    width: 0;
    height: 0;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 50%;
    transform: translate(-50%, -50%);
    transition: width 0.6s ease, height 0.6s ease;
}

.quick-btn:active::after {
    width: 200%;
    height: 200%;
}

/* --- Animation เมื่อโหลดหน้าครั้งแรก --- */
@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(30px); }
    to { opacity: 1; transform: translateY(0); }
}

.stat-card, .quick-btn, .glass-header, .carousel {
    animation: fadeInUp 0.8s cubic-bezier(0.2, 0.8, 0.2, 1) both;
}

/* ให้ลำดับการแสดงผลต่างกันนิดนึง (Stagger) */
.col-lg-4:nth-child(1) .stat-card { animation-delay: 0.1s; }
.col-lg-4:nth-child(2) .stat-card { animation-delay: 0.2s; }
.col-lg-4:nth-child(3) .stat-card { animation-delay: 0.3s; }

/* --- ตกแต่ง Carousel ให้ดูพรีเมียม --- */
.carousel-item img {
    transition: transform 10s linear;
}
.carousel-item.active img {
    transform: scale(1.15);
}
/* --- Dark Mode Variables --- */
body.dark-mode {
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%) !important;
}

/* ส่วนหัวและ Header */
body.dark-mode .glass-header {
    background: rgba(30, 41, 59, 0.8) !important;
    border: 1px solid rgba(255, 255, 255, 0.1) !important;
}

body.dark-mode .glass-header h3, 
body.dark-mode .glass-header .text-muted {
    color: #ffffff !important;
}

/* สถิติด้านบน (Stat Cards) ให้มืดลงได้เพราะเป็นข้อมูลโชว์ */
body.dark-mode .stat-card:not([style*="linear-gradient"]) {
    background: rgba(30, 41, 59, 0.8) !important;
    border: 1px solid rgba(255, 255, 255, 0.1) !important;
    color: #ffffff !important;
}

/* --- จุดสำคัญ: ส่วน body.php --- */
/* เราจะให้พื้นหลังของกรอบ body มืด แต่ข้างในคงเดิม */
body.dark-mode #main-content {
    background: #1e293b !important; /* พื้นหลังกรอบใหญ่สีเข้ม */
    border: 1px solid rgba(255, 255, 255, 0.1) !important;
}

/* ยกเว้น (Reset) ทุกอย่างข้างใน #main-content ให้ใช้สีเดิมเหมือนโหมดสว่าง */
/* สิ่งนี้จะทำให้พวก Input, Table, Textbox ที่ถูก include มา ไม่โดนสีมืดกลืน */
body.dark-mode #main-content * {
    background-color: transparent; /* ให้โปร่งแสงเห็นพื้นหลังการ์ด */
}

/* ถ้าคุณอยากให้พวกช่อง Input ยังเป็นสีขาวเหมือนเดิมเป๊ะๆ ให้ล็อคไว้แบบนี้ */
body.dark-mode #main-content input, 
body.dark-mode #main-content select, 
body.dark-mode #main-content textarea,
body.dark-mode #main-content .table,
body.dark-mode #main-content .card {
    background-color: #ffffff !important;
    color: #212529 !important; /* ตัวหนังสือสีเข้มเหมือนเดิม */
}

/* ปุ่มเมนู Quick Buttons ให้คงสีขาวไว้จะได้เด่น */
body.dark-mode .quick-btn {
    background: rgba(255, 255, 255, 0.9) !important;
    color: var(--deep-blue) !important;
}

/* ปรับหัวข้อเมนูให้สว่างขึ้น */
body.dark-mode h5.fw-bold {
    color: #ffffff !important;}
/* ปุ่มสลับโหมด (ลอย) */
.dark-mode-toggle {
    position: fixed;
    bottom: 30px;
    right: 30px;
    width: 55px;
    height: 55px;
    border-radius: 50%;
    background: var(--primary-blue);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    cursor: pointer;
    box-shadow: 0 10px 25px rgba(0, 97, 255, 0.3);
    z-index: 100000;
    transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

.dark-mode-toggle:hover {
    transform: scale(1.1) rotate(15deg);
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
        <small style="color: var(--primary-blue); cursor: pointer;">ดูทั้งหมด</small>
    </div>
    
    <div id="promoCarousel" class="carousel slide mb-4" data-bs-ride="carousel">
        <div class="carousel-inner">
            <?php foreach ($promotions as $i => $p): ?>
            <div class="carousel-item <?= $i === 0 ? 'active' : '' ?>">
                <div class="position-relative">
                    <img src="../<?= htmlspecialchars($p['image']) ?>" class="d-block w-100">
                    
                    <div class="promo-overlay position-absolute bottom-0 start-0 end-0 text-white">
                        <div class="fw-bold" style="font-size: 1.1rem;"><?= htmlspecialchars($p['title']) ?></div>
                        <small style="font-size: 0.8rem; opacity: 0.9;">
                            <i class="bi bi-shop me-1"></i> <?= htmlspecialchars($p['store_name']) ?>
                        </small>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <button class="carousel-control-prev" type="button" data-bs-target="#promoCarousel" data-bs-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#promoCarousel" data-bs-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
        </button>
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
<div id="main-content" class="bg-white p-4 rounded-4 shadow-sm mt-4">
   <div class="bg-white p-4 rounded-4 shadow-sm mt-4">
    <?php include "body.php"; ?>
 </div>
</div>


</div>
<div class="dark-mode-toggle" onclick="toggleDarkMode()" title="สลับโหมดกลางคืน">
    <i class="bi bi-moon-stars-fill" id="dark-icon"></i>
</div>
<script>
    // 1. ฟังก์ชันทำให้ตัวเลขวิ่ง (Counter Up)
    function animateValue(obj, start, end, duration) {
        let startTimestamp = null;
        const step = (timestamp) => {
            if (!startTimestamp) startTimestamp = timestamp;
            const progress = Math.min((timestamp - startTimestamp) / duration, 1);
            const value = Math.floor(progress * (end - start) + start);
            obj.innerHTML = value.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
            if (progress < 1) {
                window.requestAnimationFrame(step);
            }
        };
        window.requestAnimationFrame(step);
    }

    window.addEventListener('DOMContentLoaded', () => {
        // --- ส่วนที่ 1: วิ่งลงไปที่เนื้อหา (ตามที่คุณต้องการ) ---
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('link')) {
            const contentDiv = document.getElementById('main-content'); // อย่าลืมใส่ id="main-content" ที่ div ครอบ body.php
            if (contentDiv) {
                setTimeout(() => {
                    contentDiv.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }, 500);
            }
        }

        // --- ส่วนที่ 2: เลขวิ่ง (Counter) ---
        // ค้นหาตัวเลขรายได้ (ต้องเป็น class หรือ id ที่ระบุเจาะจง)
        // เพื่อความแม่นยำ ผมแนะนำให้เติม id="today-income-display" ใน h2 ของรายได้ครับ
        const todayIncomeEl = document.getElementById('today-income-display');
        const monthIncomeEl = document.getElementById('month-income-display');
        
        if(todayIncomeEl) {
            const val = parseFloat(todayIncomeEl.innerText.replace(/,/g, ''));
            animateValue(todayIncomeEl, 0, val, 1500);
        }
        if(monthIncomeEl) {
            const val = parseFloat(monthIncomeEl.innerText.replace(/,/g, ''));
            animateValue(monthIncomeEl, 0, val, 2000);
        }
    });

    // 2. ฟังก์ชัน Modal เดิมของคุณ
    function openReportModal() {
        reportModal.style.display = 'flex';
        reportModal.style.opacity = '0';
        setTimeout(() => { reportModal.style.opacity = '1'; }, 10);
        document.body.style.overflow = 'hidden';
    }

    function closeReportModal() {
        reportModal.style.opacity = '0';
        setTimeout(() => { 
            reportModal.style.display = 'none'; 
            document.body.style.overflow = 'auto';
        }, 300);
    }
    // ตรวจสอบโหมดที่บันทึกไว้เมื่อโหลดหน้าจอ
    const currentMode = localStorage.getItem('theme');
    if (currentMode === 'dark') {
        document.body.classList.add('dark-mode');
        updateIcon(true);
    }

    function toggleDarkMode() {
        const isDark = document.body.classList.toggle('dark-mode');
        
        // บันทึกค่าลง LocalStorage
        if (isDark) {
            localStorage.setItem('theme', 'dark');
            updateIcon(true);
        } else {
            localStorage.setItem('theme', 'light');
            updateIcon(false);
        }
    }

    function updateIcon(isDark) {
        const icon = document.getElementById('dark-icon');
        if (isDark) {
            icon.classList.replace('bi-moon-stars-fill', 'bi-sun-fill');
            icon.style.color = '#ffcc00';
        } else {
            icon.classList.replace('bi-sun-fill', 'bi-moon-stars-fill');
            icon.style.color = '#ffffff';
        }
    }
</script>