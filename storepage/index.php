<?php
session_start();
require_once "../ld_db.php";
include "middleware_subscription.php";

if (!isset($_SESSION['store_id'])) {
    header("Location: create_store.php");
    exit;
}

// --- ส่วนที่เพิ่ม: ดึงข้อมูลโปรไฟล์ผู้ใช้งาน ---
$user_info = null;
if (isset($_SESSION['user_id'])) {
    $stmtUser = $pdo->prepare("SELECT display_name, email, profile_image FROM users WHERE id = ?");
    $stmtUser->execute([$_SESSION['user_id']]);
    $user_info = $stmtUser->fetch(PDO::FETCH_ASSOC);
}
// ---------------------------------------

$store_id = $_SESSION['store_id'];
$now = date('Y-m-d H:i:s');

/* ============================================================
   1. LOGIC สำหรับการสมัครสมาชิก & แผนการใช้งาน (คงเดิม)
   ============================================================ */
$sub = null;
try {
    $stmt = $pdo->prepare("SELECT status, slip_image, plan FROM store_subscriptions WHERE store_id = ? ORDER BY created_at DESC LIMIT 1");
    $stmt->execute([$store_id]);
    $sub = $stmt->fetch(PDO::FETCH_ASSOC);

    $planStmt = $pdo->query("SELECT id, name, price, amount, qr_image FROM billing_plans WHERE status = 'active' ORDER BY price ASC");
    $plans = $planStmt->fetchAll(PDO::FETCH_ASSOC);

    $promoSubStmt = $pdo->prepare("
        SELECT id, title, discount, discount_type, is_flash_sale, usage_limit, used_count 
        FROM promotions 
        WHERE status = 'active' AND (audience = 'stores' OR audience = 'all' OR store_id = ?)
        AND start_date <= ? AND end_date >= ?
    ");
    /* ดึงแพ็กเกจทั้งหมด */
    $planStmt = $pdo->query("SELECT * FROM billing_plans WHERE status = 'active' ORDER BY price ASC");
    $plans = $planStmt->fetchAll(PDO::FETCH_ASSOC);

    /* ดึงโปรโมชั่นที่ร้านค้าใช้ได้ */
    $promoSubStmt = $pdo->prepare("
    SELECT id, title, discount, discount_type 
    FROM promotions 
    WHERE status = 'active' AND (audience = 'stores' OR audience = 'all')
    AND start_date <= NOW() AND end_date >= NOW()
");
    $promoSubStmt->execute();
    $available_promotions = $promoSubStmt->fetchAll(PDO::FETCH_ASSOC);
    $promoSubStmt->execute([$store_id, $now, $now]);
    $available_promotions = $promoSubStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log($e->getMessage());
}

/* ============================================================
   2. LOGIC หน้า DASHBOARD (รายได้ และ โปรโมชั่น Carousel) (คงเดิม)
   ============================================================ */
$sql = "SELECT id, title, image, start_date, end_date FROM promotions WHERE status = 'active' AND (NOW() BETWEEN start_date AND end_date) ORDER BY created_at DESC";
$promos = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT IFNULL(SUM(p.amount),0) FROM payments p JOIN orders o ON p.order_id = o.id WHERE p.status = 'confirmed' AND o.store_id = ? AND DATE(p.confirmed_at) = CURDATE()");
$stmt->execute([$store_id]);
$today_income = $stmt->fetchColumn();


$stmt = $pdo->prepare("SELECT IFNULL(SUM(p.amount),0) FROM payments p JOIN orders o ON p.order_id = o.id WHERE p.status = 'confirmed' AND o.store_id = ? AND MONTH(p.confirmed_at) = MONTH(CURDATE()) AND YEAR(p.confirmed_at) = YEAR(CURDATE())");
$stmt->execute([$store_id]);
$month_income = $stmt->fetchColumn();

/* 1. ดึงข้อมูลการสมัครล่าสุด */
$stmt = $pdo->prepare("SELECT status, slip_image, plan FROM store_subscriptions WHERE store_id = ? ORDER BY created_at DESC LIMIT 1");
$stmt->execute([$store_id]);
$sub = $stmt->fetch(PDO::FETCH_ASSOC);

/* --- แก้ไข: ส่วน Logic สำหรับปุ่มสมัครใหม่ (Retry) --- */
// เราจะยอมให้ $sub เป็น null (เพื่อแสดงหน้าเลือกแพ็กเกจ) 
// เฉพาะตอนที่กดปุ่มสมัครใหม่ "และ" สถานะเดิมต้องเป็น 'rejected' เท่านั้น
if (isset($_GET['action']) && $_GET['action'] === 'retry_subscription') {
    if ($sub && $sub['status'] === 'rejected') {
        $sub = null; 
    } else {
        // ถ้าสถานะเป็น waiting_approve หรืออื่นๆ อยู่ ห้ามเคลียร์ค่า $sub 
        // และให้เอา action ออกจาก URL เพื่อป้องกันหน้าจอเพี้ยน
        header("Location: index.php");
        exit;
    }
}
/* -------------------------------------------------- */

$userStmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role NOT IN ('platform_admin','store_owner')");
$total_users = (int)$userStmt->fetchColumn();
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>Dashboard ร้านซักอบรีด</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="../image/3.jpg">
    <link href="../bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        /* --- 1. ตัวแปรหลักและสไตล์พื้นฐาน (คงเดิม) --- */
       :root {
            --primary-blue: #0061ff;
            --soft-blue: #e3edff;
            --deep-blue: #1e3c72;
            --glass-bg: rgba(255, 255, 255, 0.85);
            --dark-bar: #1a1d21; /* สีสำหรับ Header เล็ก */
        }
        body {
            background: linear-gradient(135deg, #f5f7fb 0%, #e8f0fe 100%);
            font-family: 'Kanit', sans-serif;
            min-height: 100vh;
        }

        /* --- 2. ส่วนแพ็กเกจสมาชิก (แก้ไขใหม่: เพิ่มการเลื่อนและจำกัดกรอบ) --- */

        /* สร้าง Container สำหรับเลื่อนแนวนอน */
        .plans-scroll-container {
            display: flex;
            overflow-x: auto;
            gap: 15px;
            padding: 10px 5px 20px 5px;
            scroll-behavior: smooth;
            -webkit-overflow-scrolling: touch;
            /* เลื่อนลื่นๆ บนมือถือ */
        }

        /* ปรับแต่งแถบเลื่อน (Scrollbar) ให้สวยงาม */
        .plans-scroll-container::-webkit-scrollbar {
            height: 6px;
        }

        .plans-scroll-container::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        .plans-scroll-container::-webkit-scrollbar-thumb {
            background: var(--primary-blue);
            border-radius: 10px;
        }

        /* บังคับความกว้างแต่ละแพ็กเกจให้คงที่ ไม่บีบกัน */
        .plan-item-wrapper {
            flex: 0 0 260px;
            /* ปรับขนาดความกว้างตามใจชอบ */
        }

        .plan-item {
            cursor: pointer;
            border: 2px solid #eee;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            background: white;
            height: 100%;
            /* ให้สูงเท่ากันหมด */
        }

        .plan-item:hover {
            border-color: var(--primary-blue);
            background: #f8faff;
        }

        .plan-item input[type="radio"] {
            display: none;
        }

        .plan-item input[type="radio"]:checked+.plan-content {
            background: #eef4ff;
        }

        .plan-item input[type="radio"]:checked~.check-icon {
            display: block;
        }

        .plan-item .check-icon {
            display: none;
            position: absolute;
            top: 10px;
            right: 10px;
            color: var(--primary-blue);
        }

        .plan-price-tag {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--primary-blue);
        }

        /* --- 3. การจัดการรูปภาพ (ป้องกันการดันกรอบ) --- */

        /* คุมรูปภาพสลิปตอนรออนุมัติ */
        .slip-image-container {
            max-height: 250px;
            width: 100%;
            overflow: hidden;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8f9fa;
            border: 1px solid #eee;
        }

        .slip-image-container img {
            max-height: 250px;
            max-width: 100%;
            object-fit: contain;
        }

        /* --- 4. Dashboard & UI Components (คงเดิม) --- */
        .profile-section {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 5px 15px;
            background: white;
            border-radius: 50px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            border: 1px solid #eee;
        }

        .profile-img {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--primary-blue);
        }

        body.store-locked #app {
            filter: blur(12px);
            pointer-events: none;
            user-select: none;
        }

        #subscription-overlay {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.85);
            backdrop-filter: blur(8px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999999;
            padding: 20px;
        }

        .sub-card-compact {
            background: #fff;
            width: 100%;
            max-width: 450px;
            /* เพิ่มความกว้างเล็กน้อยเพื่อให้เหมาะกับการเลื่อน */
            border-radius: 30px;
            padding: 30px;
            text-align: center;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            animation: cardAppear 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        .glass-header {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 1.2rem 1.5rem;
            border: 1px solid rgba(255, 255, 255, 0.4);
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.07);
        }

        .stat-card {
            background: white;
            border-radius: 24px;
            padding: 1.5rem;
            transition: all 0.4s ease;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.03);
            border: none;
        }

        .stat-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0, 97, 255, 0.1);
        }

        .stat-icon {
            width: 54px;
            height: 54px;
            border-radius: 16px;
            background: linear-gradient(135deg, #0061ff 0%, #60efff 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 24px;
        }

        .quick-btn {
            border-radius: 20px;
            padding: 20px 10px;
            font-weight: 600;
            background: white;
            color: var(--deep-blue);
            border: 1px solid #fff;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.04);
            transition: 0.3s;
            text-decoration: none;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
        }

        .quick-btn:hover {
            background: var(--primary-blue);
            color: white;
            transform: scale(1.05);
        }

        /* --- 5. Chat & Modal (คงเดิม) --- */
        #chatModal {
            z-index: 10000000 !important;
        }

        .msg-me {
            align-self: flex-end;
            background: #0084ff;
            color: white;
            padding: 8px 15px;
            border-radius: 18px 18px 2px 18px;
            max-width: 80%;
        }

        .msg-admin {
            align-self: flex-start;
            background: #e4e6eb;
            color: black;
            padding: 8px 15px;
            border-radius: 18px 18px 18px 2px;
            max-width: 80%;
        }

        @keyframes cardAppear {
            from {
                opacity: 0;
                transform: scale(0.9) translateY(20px);
            }

            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }

        .slip-image-container {
            max-height: 250px;
            width: 100%;
            max-width: 200px;
            /* จำกัดความกว้างรูปสลิปไม่ให้ใหญ่เกินไป */
            overflow: hidden;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8f9fa;
            border: 1px solid #eee;
            margin: 0 auto;
            /* จัดกึ่งกลางแนวนอน */
        }
        .top-info-bar {
            background: var(--dark-bar);
            color: white;
            font-size: 0.85rem;
            padding: 8px 0;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .top-info-bar a {
            color: white;
            text-decoration: none;
            transition: 0.3s;
            opacity: 0.9;
        }
        .top-info-bar a:hover {
            color: var(--soft-blue);
            opacity: 1;
        }

        .info-divider {
            width: 1px;
            height: 12px;
            background: rgba(255,255,255,0.2);
            margin: 0 10px;
        }

        /* --- [NEW] 7. Minimal Footer Styles --- */
        .site-footer {
            background: white;
            padding: 25px 0;
            border-top: 1px solid #eee;
            margin-top: 50px;
        }

        .footer-label {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #999;
            margin-bottom: 5px;
            display: block;
        }

        .footer-contact-link {
            color: var(--deep-blue);
            font-weight: 600;
            text-decoration: none;
        }

        .security-tag {
            background: var(--soft-blue);
            color: var(--primary-blue);
            padding: 6px 15px;
            border-radius: 50px;
            font-size: 13px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        @media (max-width: 768px) {
            .top-info-bar .d-flex {
                justify-content: center !important;
                gap: 15px;
            }
            .info-divider { display: none; }
        }

        .slip-image-container img {
            max-height: 250px;
            width: auto;
            max-width: 100%;
            object-fit: contain;
        }

        /* บังคับให้ Card หลักจัดเนื้อหาตรงกลางเสมอ */
        .sub-card-compact {
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        /* Hero Section Styles */
.hero-section {
    padding: 100px 0;
    background: radial-gradient(circle at top right, rgba(0, 97, 255, 0.05), transparent);
    overflow: hidden;
}

.bg-soft-blue {
    background-color: var(--soft-blue);
    font-weight: 600;
    letter-spacing: 0.5px;
}

.hero-img-wrapper {
    position: relative;
    padding: 20px;
}

.hero-main-icon {
    width: 300px;
    height: 300px;
    object-fit: cover;
    border-radius: 60px;
    box-shadow: 0 30px 60px rgba(0,0,0,0.1);
    transform: rotate(-5deg);
    transition: 0.5s ease;
}

.hero-main-icon:hover {
    transform: rotate(0deg) scale(1.05);
}

.floating-card {
    position: absolute;
    background: var(--primary-blue);
    color: white;
    padding: 15px 25px;
    border-radius: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
    box-shadow: 0 15px 30px rgba(0, 97, 255, 0.3);
    bottom: 20px;
    right: 20px;
    animation: float 3s ease-in-out infinite;
}

@keyframes float {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-15px); }
}

/* Responsive */
@media (max-width: 991px) {
    .hero-section { padding: 60px 0; text-align: center; }
    .hero-section .text-start { text-align: center !important; }
}
    </style>
    
</head>

<body class="<?= ($STORE_LOCKED ?? false) ? 'store-locked' : '' ?>">

    <?php if ($STORE_LOCKED ?? false): ?>
        <div id="subscription-overlay">
            <div class="sub-card-compact" style="max-width: 500px;">

                <?php if ($sub && $sub['status'] === 'waiting_approve'): ?>
                    <div class="animate__animated animate__fadeIn text-center">
                        <div class="mb-4">
                            <div class="bg-warning bg-opacity-10 p-4 rounded-circle d-inline-block mb-3">
                                <i class="bi bi-clock-history text-warning" style="font-size: 3rem;"></i>
                            </div>
                            <h4 class="fw-bold text-dark">รอเจ้าของระบบตรวจสอบ</h4>
                            <p class="text-muted small">เราได้รับข้อมูลการชำระเงินของคุณแล้ว<br>กรุณารอการตรวจสอบเพื่อเปิดใช้งานระบบ</p>
                        </div>
                        <?php if (!empty($sub['slip_image'])): ?>
                            <div class="mb-4">
                                <div class="slip-image-container border shadow-sm mx-auto">
                                    <img src="../<?= htmlspecialchars($sub['slip_image']) ?>" class="img-fluid">
                                </div>
                            </div>
                        <?php endif; ?>
                        <div class="d-grid gap-3">
                            <button onclick="location.reload()" class="btn btn-primary rounded-pill py-3 fw-bold shadow">
                                <i class="bi bi-arrow-clockwise me-2"></i> รีเฟรชหน้าจอ
                            </button>
                            <div class="row g-2">
                                <div class="col-6">
                                    <button type="button" class="btn btn-outline-primary w-100 rounded-pill py-2 small" data-bs-toggle="modal" data-bs-target="#chatModal">
                                        <i class="bi bi-chat-dots me-1"></i> แชทสอบถาม
                                    </button>
                                </div>
                                <div class="col-6">
                                    <a href="../loginpage/logout.php" class="btn btn-outline-danger w-100 rounded-pill py-2 small">ออกจากระบบ</a>
                                </div>
                            </div>
                        </div>
                    </div>

                <?php elseif ($sub && $sub['status'] === 'rejected'): ?>
    <div class="animate__animated animate__shakeX text-center">
        <div class="mb-4">
            <div class="bg-danger bg-opacity-10 p-4 rounded-circle d-inline-block mb-3">
                <i class="bi bi-x-circle text-danger" style="font-size: 3rem;"></i>
            </div>
            <h4 class="fw-bold text-danger">การชำระเงินถูกปฏิเสธ</h4>
            <p class="text-muted small">ขออภัย ข้อมูลการโอนเงินของคุณไม่ถูกต้องหรือไม่ครบถ้วน<br>
            <span class="text-danger fw-bold">กรุณาส่งเลขบัญชีธนาคารผ่านแชทเพื่อขอรับเงินคืน</span></p>
        </div>

        <div class="alert alert-danger small border-0 shadow-sm mb-4" style="border-radius: 15px;">
            <i class="bi bi-info-circle-fill me-2"></i> หากต้องการเปลี่ยนแพ็กเกจหรือส่งสลิปใหม่ ท่านสามารถกดสมัครใหม่ได้ทันที
        </div>

        <div class="d-grid gap-3">
            <button type="button" class="btn btn-danger rounded-pill py-3 fw-bold shadow" data-bs-toggle="modal" data-bs-target="#chatModal">
                <i class="bi bi-chat-dots-fill me-2"></i> แชทส่งเลขบัญชีเพื่อคืนเงิน
            </button>
            
            <div class="row g-2">
                <div class="col-12">
                    <a href="index.php?action=retry_subscription" class="btn btn-primary w-100 rounded-pill py-3 fw-bold shadow">
                        <i class="bi bi-plus-circle-fill me-2"></i> สมัครใหม่อีกครั้ง
                    </a>
                </div>
                <div class="col-6 mt-2">
                    <button onclick="location.reload()" class="btn btn-outline-secondary w-100 rounded-pill py-2 small">
                        <i class="bi bi-arrow-clockwise"></i> รีเฟรช
                    </button>
                </div>
                <div class="col-6 mt-2">
                    <a href="../loginpage/logout.php" class="btn btn-outline-danger w-100 rounded-pill py-2 small">ออกจากระบบ</a>
                </div>
            </div>
        </div>
    </div>

                <?php else: ?>
                    <h4 class="fw-bold text-dark mb-1">เลือกแพ็กเกจสมาชิก</h4>
                    <p class="text-muted small mb-3">เลื่อนดูแพ็กเกจที่เหมาะกับคุณ</p>

                    <form method="post" action="menu/subscription/subscribe_action.php" enctype="multipart/form-data" id="subForm">
                        <div class="plans-scroll-container">
                            <?php foreach ($plans as $plan): ?>
                                <div class="plan-item-wrapper">
                                    <label class="plan-item d-block p-3 rounded-4 h-100">
                                        <input type="radio" name="plan_id" value="<?= $plan['id'] ?>"
                                            data-price="<?= $plan['price'] ?>"
                                            data-qr="<?= htmlspecialchars($plan['qr_image']) ?>" required>
                                        <div class="plan-content">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <h6 class="fw-bold mb-0 text-truncate" style="max-width: 150px;"><?= htmlspecialchars($plan['name']) ?></h6>
                                                <span class="plan-price-tag" style="font-size: 1.2rem;">฿<?= number_format($plan['price'], 0) ?></span>
                                            </div>
                                            <div class="text-start">
                                                <small class="text-muted d-block"><i class="bi bi-calendar-check me-1"></i> อายุ <?= $plan['duration'] ?> วัน</small>
                                                <small class="text-muted d-block"><i class="bi bi-coin me-1"></i> เครดิต <?= number_format($plan['amount'], 0) ?></small>
                                            </div>
                                        </div>
                                        <i class="bi bi-check-circle-fill check-icon"></i>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="mb-4 text-start">
                            <label class="small fw-bold text-muted mb-2">โค้ดส่วนลด / โปรโมชั่น</label>
                            <select name="promo_id" id="promoSelect" class="form-select rounded-pill">
                                <option value="" data-discount="0">ไม่ใช้ส่วนลด</option>
                                <?php foreach ($available_promotions as $promo): ?>
                                    <option value="<?= $promo['id'] ?>" data-discount="<?= $promo['discount'] ?>" data-type="<?= $promo['discount_type'] ?>">
                                        <?= htmlspecialchars($promo['title']) ?> (ลด <?= $promo['discount_type'] == 'percentage' ? $promo['discount'] . '%' : $promo['discount'] . '฿' ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>


                        <div id="payment-area" class="d-none bg-light p-3 rounded-4 mb-4 animate__animated animate__fadeIn">
                            <div class="d-flex justify-content-between mb-2">
                                <span>ยอดที่ต้องชำระ:</span>
                                <span class="fw-bold text-primary fs-5">฿<span id="final-price">0</span></span>
                            </div>
                            <div class="text-center mb-3">
                                <p class="small text-muted mb-2">สแกนชำระเงินผ่าน QR Code</p>
                                <img id="qr-display" src="" class="img-fluid rounded-3 shadow-sm" style="max-height: 200px;">
                            </div>
                            <div class="text-start">
                                <label class="small fw-bold mb-1">แนบหลักฐานการโอน (สลิป)</label>
                                <input type="file" name="slip_image" class="form-control rounded-pill" required>
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary rounded-pill py-3 fw-bold shadow">
                                ยืนยันการสมัครสมาชิก
                            </button>
                            <a href="../loginpage/logout.php" class="btn btn-link btn-sm text-muted">ออกจากระบบ</a>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <div id="app">
        <div class="container py-4">
            <div class="glass-header d-flex justify-content-between align-items-center mb-5 mt-2">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-primary p-2 rounded-4 shadow-sm d-none d-md-block">
                        <i class="bi bi-shop text-white fs-4"></i>
                    </div>
                    <div>
                        <h4 class="fw-bold text-dark mb-0">Laundry Hub</h4>
                        <span class="text-muted small">ระบบจัดการหลังบ้าน</span>
                    </div>
                </div>

                <div class="d-flex align-items-center gap-2">
                    <div class="profile-section d-none d-sm-flex" onclick="openEditStoreModal()" style="cursor: pointer;">
                        <div class="text-end me-1">
                            <div class="fw-bold small text-dark" style="line-height: 1.2;" id="display-store-name">
                                <?= htmlspecialchars($user_info['display_name'] ?? 'เจ้าของร้าน') ?>
                            </div>
                            <div class="text-muted" style="font-size: 10px;">จัดการข้อมูลร้านค้า</div>
                        </div>
                        <img src="../<?= !empty($user_info['profile_image']) ? htmlspecialchars($user_info['profile_image']) : 'image/default-avatar.png' ?>" class="profile-img">
                    </div>

                    <button class="btn btn-light rounded-pill p-2 px-3 shadow-sm border ms-2" onclick="openReportModal()">
                        <i class="bi bi-chat-dots text-warning"></i> <span class="d-none d-md-inline small fw-bold">แจ้งปัญหา</span>
                    </button>

                    <a href="../loginpage/logout.php" class="btn btn-outline-danger rounded-pill px-3"><i class="bi bi-power"></i></a>
                </div>
            </div>
            <?php if ($promos): ?>
                <div id="promoCarousel" class="carousel slide shadow-lg mb-5" data-bs-ride="carousel" style="border-radius: 30px; overflow: hidden;">
                    <div class="carousel-indicators">
                        <?php foreach ($promos as $i => $p): ?>
                            <button type="button" data-bs-target="#promoCarousel" data-bs-slide-to="<?= $i ?>" class="<?= $i === 0 ? 'active' : '' ?>"></button>
                        <?php endforeach; ?>
                    </div>
                    <div class="carousel-inner">
                        <?php foreach ($promos as $i => $p): ?>
                            <div class="carousel-item <?= $i === 0 ? 'active' : '' ?>">
                                <div style="height: 380px; position:relative;">
                                    <img src="../<?= htmlspecialchars($p['image']) ?>" class="d-block w-100 h-100" style="object-fit: cover; filter: brightness(0.65);">
                                    <div class="carousel-caption text-start" style="left: 8%; bottom: 15%;">
                                        <h1 class="display-6 fw-bold text-white"><?= htmlspecialchars($p['title']) ?></h1>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach ?>
                    </div>
                    <button class="carousel-control-prev" type="button" data-bs-target="#promoCarousel" data-bs-slide="prev">
                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                    </button>
                    <button class="carousel-control-next" type="button" data-bs-target="#promoCarousel" data-bs-slide="next">
                        <span class="carousel-control-next-icon" aria-hidden="true"></span>
                    </button>
                </div>
            <?php endif ?>

            <div class="row g-4 mb-5">
                <div class="col-lg-4 col-md-6">
                    <div class="stat-card">
                        <div class="stat-icon mb-3"><i class="bi bi-wallet2"></i></div>
                        <small class="text-muted">รายได้วันนี้</small>
                        <h2 class="fw-bold mb-0"><?= number_format($today_income, 2) ?> ฿</h2>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="stat-card" style="background: var(--deep-blue); color: white;">
                        <div class="stat-icon mb-3" style="background:rgba(255,255,255,0.2)"><i class="bi bi-graph-up-arrow"></i></div>
                        <small class="text-white-50">รายได้เดือนนี้</small>
                        <h2 class="fw-bold mb-0 text-white"><?= number_format($month_income, 2) ?> ฿</h2>
                    </div>
                </div>
                <div class="col-lg-4 col-md-12">
                    <div class="stat-card">
                        <div class="stat-icon mb-3" style="background: linear-gradient(135deg, #ff9966, #ff5e62);"><i class="bi bi-people"></i></div>
                        <small class="text-muted">ฐานลูกค้า</small>
                        <h2 class="fw-bold mb-0"><?= number_format($total_users) ?> คน</h2>
                    </div>
                </div>
            </div>

            <h5 class="fw-bold mb-4 px-2">เมนูการจัดการ</h5>
            <div class="row g-3 mb-5 text-center">
                <div class="col-6 col-md-2"><a href="index.php?link=orders" class="quick-btn"><i class="bi bi-bag-check text-primary"></i> ออเดอร์</a></div>
                <div class="col-6 col-md-2"><a href="index.php?link=delivery" class="quick-btn"><i class="bi bi-truck text-success"></i> จัดส่ง</a></div>
                <div class="col-6 col-md-2"><a href="index.php?link=revenue" class="quick-btn"><i class="bi bi-pie-chart text-info"></i> รายได้</a></div>
                <div class="col-6 col-md-3"><a href="index.php?link=promotion" class="quick-btn"><i class="bi bi-megaphone text-danger"></i> โปรโมชั่น</a></div>
                <div class="col-6 col-md-3"><a href="index.php?link=management" class="quick-btn"><i class="bi bi-person-gear text-secondary"></i> พนักงาน</a></div>
            </div>

            <div id="main-content" class="bg-white p-4 rounded-4 shadow-sm">
                <?php include "body.php"; ?>
            </div>
        </div>
    </div>
<footer class="mt-5 pt-5 pb-4 bg-white border-top shadow-sm" style="border-radius: 40px 40px 0 0;">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-4">
                <h5 class="fw-bold text-primary mb-3">Laundry Hub</h5>
                <p class="text-muted mb-2">
                    <i class="bi bi-geo-alt-fill me-2"></i> 226 หมู่13 ตำบลบ้านต๋อมbr>อำเภอเมืองพะเยา จังหวัดพะเยา 56000
                </p>
                
            </div>

            <div class="col-lg-4">
                <h5 class="fw-bold mb-3">ติดต่อสอบถาม</h5>
                <div class="d-flex flex-column gap-2">
                    <a href="tel:0643076672" class="text-decoration-none text-muted">
                        <i class="bi bi-telephone-fill text-primary me-2"></i> 064-307-6672
                    </a>
                    <a href="mailto:support@laundryhub.com" class="text-decoration-none text-muted">
                        <i class="bi bi-envelope-at-fill text-primary me-2"></i> laundryNattaphon@gmail.com
                    </a>
                </div>
            </div>

            <div class="col-lg-4 text-center text-lg-end">
                <h5 class="fw-bold mb-3">ติดตามเรา</h5>
                <div class="d-flex justify-content-center justify-content-lg-end gap-3">
                    <a href="#" class="btn btn-soft-blue rounded-circle p-2 px-3 text-primary"><i class="bi bi-facebook"></i></a>
                    <a href="#" class="btn btn-soft-blue rounded-circle p-2 px-3 text-primary"><i class="bi bi-line"></i></a>
                    <a href="#" class="btn btn-soft-blue rounded-circle p-2 px-3 text-primary"><i class="bi bi-instagram"></i></a>
                </div>
            </div>
        </div>
        <hr class="my-4 text-muted">
        <div class="text-center text-muted small">
            © 2024 Laundry Hub System - All Rights Reserved. บริหารจัดการร้านซักอบรีดอย่างมืออาชีพ
        </div>
    </div>
</footer>
    <div id="reportModal" style="display:none;position:fixed;inset:0;background:rgba(30,60,114,0.4);z-index:99999;align-items:center;justify-content:center;">
        <div class="modal-content mx-3 p-0" style="max-width:480px; width:100%; border-radius: 28px; overflow: hidden;">
            <div class="p-4 bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0">ศูนย์แจ้งปัญหา</h5>
                <button class="btn-close btn-close-white" onclick="closeReportModal()"></button>
            </div>
            <form method="post" action="report_store_action.php" class="p-4 bg-white">
                <input type="text" name="title" class="form-control mb-3" placeholder="ระบุหัวข้อปัญหา" required>
                <textarea name="message" rows="4" class="form-control mb-4" placeholder="ระบุสิ่งที่เกิดขึ้น..." required></textarea>
                <button class="btn btn-primary w-100 rounded-pill fw-bold py-3">ส่งรายงาน</button>
            </form>
        </div>
    </div>

    <div id="promoAlert" class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 999999;">
        <div id="liveToast" class="toast hide shadow-lg" role="alert" style="border-radius: 20px;">
            <div class="toast-header bg-primary text-white">
                <strong class="me-auto">โปรโมชั่นใหม่!</strong>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
            </div>
            <div class="toast-body p-3 d-flex align-items-center" onclick="showPromoModal()" style="cursor:pointer;">
                <div id="promoAlertImg" class="me-3" style="width:50px; height:50px;"><img src="" class="w-100 h-100 rounded"></div>
                <div id="promoAlertTitle" class="fw-bold">...</div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="promoFlashModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 30px; overflow: hidden;">
                <div id="promoModalImgContainer" style="height: 250px;"><img id="promoModalImg" src="" class="w-100 h-100" style="object-fit: cover;"></div>
                <div class="p-4 text-center">
                    <h4 class="fw-bold" id="promoModalTitle">...</h4>
                    <div class="d-flex justify-content-center gap-2 mb-4">
                        <div class="countdown-item"><span id="days" class="fs-4 fw-bold text-primary">00</span><br><small>วัน</small></div>:
                        <div class="countdown-item"><span id="hours" class="fs-4 fw-bold text-primary">00</span><br><small>ชม.</small></div>:
                        <div class="countdown-item"><span id="minutes" class="fs-4 fw-bold text-primary">00</span><br><small>นาที</small></div>:
                        <div class="countdown-item"><span id="seconds" class="fs-4 fw-bold text-primary">00</span><br><small>วิ</small></div>
                    </div>
                    <button class="btn btn-primary w-100 rounded-pill py-3 fw-bold shadow" onclick="location.reload()">รับสิทธิ์เลย</button>
                </div>
            </div>
        </div>
    </div>
    <div id="editStoreModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:999999;align-items:center;justify-content:center;backdrop-filter: blur(4px);">
        <div class="modal-content mx-3 shadow-lg" style="max-width:500px; width:100%; border-radius: 25px; overflow: hidden; border:none;">
            <div class="p-4 bg-white">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="fw-bold mb-0"><i class="bi bi-pencil-square text-primary me-2"></i>แก้ไขข้อมูลร้านค้า</h5>
                    <button type="button" class="btn-close" onclick="closeEditStoreModal()"></button>
                </div>

                <?php
                // ดึงข้อมูลร้านปัจจุบัน รวมถึง promptpay_qr
                $stmtStore = $pdo->prepare("SELECT name, phone, address, promptpay_qr FROM stores WHERE id = ?");
                $stmtStore->execute([$store_id]);
                $store_data = $stmtStore->fetch(PDO::FETCH_ASSOC);
                ?>

                <form id="editStoreForm" enctype="multipart/form-data">
                    <div class="mb-3 text-center">
                        <label class="small fw-bold text-muted mb-2 d-block">QR Code สำหรับรับเงิน (PromptPay)</label>
                        <div class="mb-2">
                            <img id="qr_preview_current"
                                src="menu/uploads/qr_codes/<?= !empty($store_data['promptpay_qr']) ? basename($store_data['promptpay_qr']) : 'image/default_qr.png' ?>"
                                style="width: 120px; height: 120px; object-fit: contain; border: 1px dashed #ccc; padding: 5px; border-radius: 10px;">
                        </div>
                        <input type="file" name="promptpay_qr" class="form-control form-control-sm rounded-3" accept="image/*" onchange="previewImage(this)">
                    </div>
                    <div class="mb-3">
                        <label class="small fw-bold text-muted mb-1">ชื่อร้านค้า</label>
                        <input type="text" name="store_name" class="form-control rounded-3" value="<?= htmlspecialchars($store_data['name']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="small fw-bold text-muted mb-1">เบอร์โทรศัพท์</label>
                        <input type="text" name="store_phone" class="form-control rounded-3" value="<?= htmlspecialchars($store_data['phone']) ?>" required>
                    </div>
                    <div class="mb-4">
                        <label class="small fw-bold text-muted mb-1">ที่อยู่ร้าน</label>
                        <textarea name="store_address" rows="3" class="form-control rounded-3"><?= htmlspecialchars($store_data['address']) ?></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 rounded-pill py-2 fw-bold">บันทึกการเปลี่ยนแปลง</button>
                </form>
            </div>
        </div>
    </div>
    <button type="button" class="btn btn-primary rounded-circle shadow-lg"
        style="position: fixed; bottom: 30px; right: 30px; width: 60px; height: 60px; z-index: 1000;"
        data-bs-toggle="modal" data-bs-target="#chatModal">
        <i class="bi bi-chat-dots-fill fs-3"></i>
    </button>

    <div class="modal fade" id="chatModal" tabindex="-1" aria-labelledby="chatModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content" style="border-radius: 20px; overflow: hidden; border: none;">
                <div class="modal-header bg-primary text-white border-0">
                    <h5 class="modal-title" id="chatModalLabel text-white">
                        <i class="bi bi-headset me-2"></i> ฝ่ายสนับสนุนระบบ
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body bg-light" id="storeChatBody" style="height: 400px; overflow-y: auto; display: flex; flex-direction: column; gap: 10px; padding: 20px;">
                    <div class="text-center text-muted mt-5">
                        <div class="spinner-border spinner-border-sm" role="status"></div>
                        <p class="small mt-2">กำลังโหลดการสนทนา...</p>
                    </div>
                </div>

                <div class="modal-footer border-0 bg-white" style="position: relative; padding: 15px;">

                    <div id="imagePreviewContainer" style="display: none; position: absolute; bottom: 70px; left: 50%; transform: translateX(-50%); z-index: 1050;">
                        <div style="position: relative; background: white; padding: 6px; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.2); border: 2px solid #0084ff;">
                            <img id="imagePreview" src="" style="max-width: 130px; max-height: 130px; border-radius: 10px; display: block;">

                            <button type="button" class="btn-close" onclick="clearPreview()"
                                style="position: absolute; top: -10px; right: -10px; background-color: #ff3b30; border-radius: 50%; width: 26px; height: 26px; padding: 0; filter: invert(1); box-shadow: 0 2px 8px rgba(0,0,0,0.3); border: 2px solid white; opacity: 1;">
                            </button>
                        </div>
                    </div>

                    <form id="storeChatForm" class="w-100 d-flex gap-2 align-items-center" enctype="multipart/form-data">
                        <label for="chatFile" class="btn btn-light rounded-circle shadow-sm">
                            <i class="bi bi-image"></i>
                            <input type="file" id="chatFile" name="attachment" accept="image/*" style="display: none;" onchange="previewChatImage(this)">
                        </label>

                        <input type="text" name="message" id="storeMsgInput" class="form-control rounded-pill bg-light border-0" placeholder="พิมพ์ข้อความ..." style="padding: 10px 20px;">
                        <button type="submit" class="btn btn-primary rounded-circle shadow-sm"><i class="bi bi-send-fill"></i></button>
                    </form>
                </div>

            </div>
        </div>
    </div>

    <audio id="popSound" src="https://assets.mixkit.co/active_storage/sfx/2358/2358-preview.mp3"></audio>

    <audio id="notifSound" src="../assets/sounds/ting.mp3" preload="auto"></audio>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // -- ส่วนของ Promotion และ UI ทั่วไป (คงเดิม) --
        document.addEventListener('DOMContentLoaded', function() {
            var myCarouselEl = document.querySelector('#promoCarousel');
            if (myCarouselEl) {
                new bootstrap.Carousel(myCarouselEl, {
                    interval: 4000,
                    ride: 'carousel',
                    pause: 'hover'
                });
            }
        });

        const pSel = document.getElementById('planSelect'),
            prSel = document.getElementById('promoSelect'),
            area = document.getElementById('planDetailArea');

        function updatePrice() {
            if (!pSel || !pSel.value) {
                area.classList.add('d-none');
                return;
            }
            const pOpt = pSel.selectedOptions[0],
                prOpt = prSel.selectedOptions[0];
            let price = parseFloat(pOpt.dataset.amount),
                disc = parseFloat(prOpt.dataset.discount),
                type = prOpt.dataset.type;
            let final = (type === 'percentage') ? (price - (price * disc / 100)) : (price - disc);
            document.getElementById('finalAmt').innerText = Math.max(0, final).toLocaleString();
            document.getElementById('qrPreview').src = '../adminpage/sidebar/' + pOpt.dataset.qr;
            area.classList.remove('d-none');
        }
        if (pSel) {
            pSel.onchange = updatePrice;
            prSel.onchange = updatePrice;
        }

        function openReportModal() {
            document.getElementById('reportModal').style.display = 'flex';
        }

        function closeReportModal() {
            document.getElementById('reportModal').style.display = 'none';
        }

        let lastPromoId = localStorage.getItem('last_seen_promo');

        function checkNewPromotion() {
            fetch('check_new_promo.php').then(r => r.json()).then(data => {
                if (data && data.id !== lastPromoId) {
                    document.querySelector('#promoAlertImg img').src = '../' + data.image;
                    document.getElementById('promoAlertTitle').innerText = data.title;
                    document.getElementById('promoModalImg').src = '../' + data.image;
                    document.getElementById('promoModalTitle').innerText = data.title;
                    window.promoEndDate = data.end_date;
                    new bootstrap.Toast(document.getElementById('liveToast')).show();
                    document.getElementById('notifSound').play().catch(e => {});
                    localStorage.setItem('last_seen_promo', data.id);
                    lastPromoId = data.id;
                }
            });
        }

        function showPromoModal() {
            new bootstrap.Modal(document.getElementById('promoFlashModal')).show();
            startCountdown(window.promoEndDate);
        }

        function startCountdown(endTime) {
            const target = new Date(endTime).getTime();
            const timer = setInterval(() => {
                const now = new Date().getTime(),
                    diff = target - now;
                if (diff <= 0) {
                    clearInterval(timer);
                    return;
                }
                document.getElementById('days').innerText = Math.floor(diff / (1000 * 60 * 60 * 24)).toString().padStart(2, '0');
                document.getElementById('hours').innerText = Math.floor((diff % (864e5)) / 36e5).toString().padStart(2, '0');
                document.getElementById('minutes').innerText = Math.floor((diff % 36e5) / 6e4).toString().padStart(2, '0');
                document.getElementById('seconds').innerText = Math.floor((diff % 6e4) / 1000).toString().padStart(2, '0');
            }, 1000);
        }
        setInterval(checkNewPromotion, 10000);
        checkNewPromotion();

        if (new URLSearchParams(window.location.search).has('link')) {
            const content = document.getElementById('main-content');
            if (content) window.scrollTo({
                top: content.getBoundingClientRect().top + window.pageYOffset - 20,
                behavior: 'smooth'
            });
        }

        // -- ฟังก์ชันสำหรับ Modal แก้ไขข้อมูลร้านค้า (คงเดิม) --
        function openEditStoreModal() {
            document.getElementById('editStoreModal').style.display = 'flex';
        }

        function closeEditStoreModal() {
            document.getElementById('editStoreModal').style.display = 'none';
        }

        function previewImage(input) { // สำหรับแก้ไขร้านค้า
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('qr_preview_current').src = e.target.result;
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        document.getElementById('editStoreForm')?.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            fetch('menu/profile/edit.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('อัปเดตข้อมูลสำเร็จ');
                        location.reload();
                    } else {
                        alert('เกิดข้อผิดพลาด: ' + data.message);
                    }
                })
                .catch(error => {
                    location.reload();
                });
        });

        // ==========================================
        // -- ส่วนของระบบแชท (ปรับปรุงใหม่ให้ส่งรูปได้) --
        // ==========================================
        const storeChatBody = document.getElementById('storeChatBody');
        const storeChatForm = document.getElementById('storeChatForm');
        const popSound = document.getElementById('popSound');

        // 1. ดึงข้อความแชท
        function fetchMessages() {
            if (!storeChatBody) return;
            fetch('fetch_messages.php')
                .then(response => response.text())
                .then(html => {
                    const isAtBottom = storeChatBody.scrollHeight - storeChatBody.scrollTop <= storeChatBody.clientHeight + 150;
                    storeChatBody.innerHTML = html;
                    if (isAtBottom) {
                        storeChatBody.scrollTop = storeChatBody.scrollHeight;
                    }
                });
        }

        // 2. พรีวิวรูปภาพในแชทก่อนส่ง
        function previewChatImage(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('imagePreview').src = e.target.result;
                    document.getElementById('imagePreviewContainer').style.display = 'block';
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        // 3. ล้างรูปภาพที่เลือกไว้
        function clearPreview() {
            document.getElementById('chatFile').value = "";
            document.getElementById('imagePreviewContainer').style.display = 'none';
        }

        // 4. ส่งข้อความและรูปภาพ
        storeChatForm?.addEventListener('submit', function(e) {
            e.preventDefault();
            const input = document.getElementById('storeMsgInput');
            const fileInput = document.getElementById('chatFile');

            // ตรวจสอบว่ามีข้อมูลส่งไปไหม (ต้องมีข้อความหรือมีรูป)
            if (!input.value.trim() && !fileInput.files.length) return;

            const formData = new FormData(this);

            fetch('save_message_store.php', {
                method: 'POST',
                body: formData
            }).then(() => {
                input.value = ''; // ล้างช่องพิมพ์
                clearPreview(); // ล้างรูปพรีวิวและช่องเลือกไฟล์
                fetchMessages(); // โหลดแชทใหม่ทันที
                if (popSound) popSound.play().catch(e => {}); // เล่นเสียง
            });
        });

        // 5. ตั้งเวลา Real-time
        setInterval(fetchMessages, 3000);

        // ดึงข้อมูลครั้งแรกเมื่อเปิด Modal แชท
        document.getElementById('chatModal')?.addEventListener('shown.bs.modal', function() {
            fetchMessages();
            // เลื่อนลงล่างสุดเมื่อเปิด
            setTimeout(() => {
                storeChatBody.scrollTop = storeChatBody.scrollHeight;
            }, 300);
        });
        // ฟังก์ชันดึงข้อความแชท
        function fetchStoreChat() {
            const chatBody = document.getElementById('storeChatBody');

            // ดึงข้อมูลจากไฟล์ get_messages.php (คุณต้องมีไฟล์นี้ฝั่ง Server)
            fetch('menu/chat/get_messages.php')
                .then(response => response.json())
                .then(messages => {
                    chatBody.innerHTML = ''; // ล้างค่าเดิม

                    if (messages.length === 0) {
                        chatBody.innerHTML = '<div class="text-center text-muted mt-5 small">ไม่มีประวัติการสนทนา เริ่มพิมพ์เพื่อสอบถามแอดมิน</div>';
                        return;
                    }

                    messages.forEach(msg => {
                        const div = document.createElement('div');
                        // เช็คว่าเป็นเราส่ง (me) หรือแอดมินส่ง (admin)
                        const isMe = msg.sender_role !== 'admin';
                        div.className = isMe ? 'msg-me shadow-sm' : 'msg-admin shadow-sm';

                        let content = '';
                        if (msg.attachment) {
                            content += `<img src="../${msg.attachment}" class="img-fluid rounded mb-2 d-block" style="max-width:200px;">`;
                        }
                        if (msg.message) {
                            content += `<span>${msg.message}</span>`;
                        }

                        div.innerHTML = content + `<small class="d-block text-end mt-1" style="font-size:10px; opacity:0.7;">${msg.time}</small>`;
                        chatBody.appendChild(div);
                    });

                    // เลื่อนลงล่างสุด
                    chatBody.scrollTop = chatBody.scrollHeight;
                });
        }

        // เมื่อกดเปิด Modal ให้เริ่มดึงข้อมูล
        document.getElementById('chatModal').addEventListener('show.bs.modal', function() {
            fetchStoreChat();
            // ทำงานทุก 5 วินาทีเพื่อเช็คข้อความใหม่จากแอดมิน
            window.chatInterval = setInterval(fetchStoreChat, 5000);
        });

        // เมื่อปิด Modal ให้หยุดดึงข้อมูลเพื่อประหยัดทรัพยากร
        document.getElementById('chatModal').addEventListener('hide.bs.modal', function() {
            clearInterval(window.chatInterval);
        });

        // ระบบส่งข้อความ (AJAX)
        document.getElementById('storeChatForm').onsubmit = function(e) {
            e.preventDefault();
            const formData = new FormData(this);

            fetch('menu/chat/save_message_store.php', { // ไฟล์บันทึกฝั่งร้านค้า
                    method: 'POST',
                    body: formData
                })
                .then(r => r.json())
                .then(data => {
                    if (data.status === 'success') {
                        document.getElementById('popSound').play();
                        this.reset();
                        clearPreview(); // ล้างรูปพรีวิวหลังส่ง
                        fetchStoreChat(); // โหลดแชทใหม่ทันที
                    }
                });
        };
        document.addEventListener('change', function(e) {
            if (e.target.name === 'plan_id' || e.target.id === 'promoSelect') {
                calculateTotal();
            }
        });

        function calculateTotal() {
            const selectedPlan = document.querySelector('input[name="plan_id"]:checked');
            const promoSelect = document.getElementById('promoSelect');
            const paymentArea = document.getElementById('payment-area');

            if (!selectedPlan) return;

            let price = parseFloat(selectedPlan.dataset.price);
            let qrPath = selectedPlan.dataset.qr;

            // คำนวณส่วนลด
            const promoOption = promoSelect.selectedOptions[0];
            const discount = parseFloat(promoOption.dataset.discount);
            const discountType = promoOption.dataset.type;

            let finalPrice = price;
            if (discountType === 'percentage') {
                finalPrice = price - (price * (discount / 100));
            } else {
                finalPrice = price - discount;
            }

            finalPrice = Math.max(0, finalPrice); // ไม่ให้ติดลบ

            // แสดงผล
            document.getElementById('final-price').innerText = finalPrice.toLocaleString();
            document.getElementById('qr-display').src = '../adminpage/sidebar/' + qrPath;
            paymentArea.classList.remove('d-none');
        }
    </script>
    <script>
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            window.scrollTo({
                top: target.offsetTop - 80, // เผื่อระยะหัวด้านบน
                behavior: 'smooth'
            });
        }
    });
});

// ตรวจจับการ Scroll เพื่อเพิ่ม Animation ในอนาคต
window.addEventListener('scroll', function() {
    const header = document.querySelector('.glass-header');
    if (window.scrollY > 50) {
        header.style.background = 'rgba(255, 255, 255, 0.95)';
        header.style.boxShadow = '0 10px 30px rgba(0,0,0,0.1)';
    } else {
        header.style.background = 'var(--glass-bg)';
        header.style.boxShadow = 'none';
    }
});
</script>
</body>

</html>