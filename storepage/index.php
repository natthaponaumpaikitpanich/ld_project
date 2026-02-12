<?php
// --- คง Logic PHP เดิมไว้ และเพิ่มการเช็ค Error เล็กน้อย ---
session_start();
require_once "../ld_db.php";
include "middleware_subscription.php";

if (!isset($_SESSION['store_id'])) {
    header("Location: create_store.php");
    exit;
}

$store_id = $_SESSION['store_id'];

/* PROMOTIONS - ปรับ SQL ให้ดึงข้อมูลง่ายขึ้น */
/* --- ส่วน PHP ด้านบน --- */
$sql = "
    SELECT id, title, image, start_date, end_date 
    FROM promotions 
    WHERE status = 'active' 
      -- ตรวจสอบว่าวันนี้อยู่ในช่วงเวลาโปรโมชั่นหรือไม่
      AND (NOW() BETWEEN start_date AND end_date)
    ORDER BY created_at DESC
";
$promos = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);


// ... (Logic รายได้คงเดิม) ...
$stmt = $pdo->prepare("SELECT IFNULL(SUM(p.amount),0) FROM payments p JOIN orders o ON p.order_id = o.id WHERE p.status = 'confirmed' AND o.store_id = ? AND DATE(p.confirmed_at) = CURDATE()");
$stmt->execute([$store_id]);
$today_income = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT IFNULL(SUM(p.amount),0) FROM payments p JOIN orders o ON p.order_id = o.id WHERE p.status = 'confirmed' AND o.store_id = ? AND MONTH(p.confirmed_at) = MONTH(CURDATE()) AND YEAR(p.confirmed_at) = YEAR(CURDATE())");
$stmt->execute([$store_id]);
$month_income = $stmt->fetchColumn();

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
        :root {
            --primary-blue: #0061ff;
            --soft-blue: #e3edff;
            --deep-blue: #1e3c72;
            --glass-bg: rgba(255, 255, 255, 0.85);
        }

        body {
            background: linear-gradient(135deg, #f5f7fb 0%, #e8f0fe 100%);
            font-family: 'Kanit', sans-serif;
            min-height: 100vh;
        }

        /* LOCK MODE */
        body.store-locked #app {
            filter: blur(10px);
            pointer-events: none;
            user-select: none;
        }

        /* GLASS HEADER */
        .glass-header {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 1.5rem;
            border: 1px solid rgba(255, 255, 255, 0.4);
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.07);
        }

        /* STAT CARDS */
        .stat-card {
            background: white;
            border-radius: 24px;
            border: none;
            padding: 1.5rem;
            position: relative;
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.03);
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
            box-shadow: 0 8px 16px rgba(0, 97, 255, 0.2);
        }

        /* QUICK BUTTONS */
        .quick-btn {
            border-radius: 20px;
            padding: 20px 10px;
            font-weight: 600;
            background: white;
            color: var(--deep-blue);
            border: 1px solid rgba(255, 255, 255, 1);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.04);
            transition: all 0.3s ease;
            text-decoration: none;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
        }

        .quick-btn i {
            font-size: 1.5rem;
        }

        .quick-btn:hover {
            background: var(--primary-blue);
            color: white;
            transform: scale(1.05);
        }

        /* CAROUSEL */
        .carousel-inner {
            border-radius: 30px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        }

        /* CUSTOM MODAL */
        #reportModal {
            backdrop-filter: blur(8px);
            transition: opacity 0.3s ease;
        }

        .modal-content {
            border-radius: 28px;
            border: none;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.2);
            animation: slideUp 0.4s ease;
        }

        @keyframes slideUp {
            from {
                transform: translateY(50px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .badge-income {
            background: var(--soft-blue);
            color: var(--primary-blue);
            padding: 5px 12px;
            border-radius: 10px;
            font-size: 0.8rem;
        }
    </style>
</head>

<body class="<?= ($STORE_LOCKED ?? false) ? 'store-locked' : '' ?>">

    <?php if ($STORE_LOCKED ?? false) {
        include "menu/subscription/popup_plan.php";
    } ?>

    <div id="app">
        <div class="container py-4">

            <div class="glass-header d-flex justify-content-between align-items-center mb-5 mt-2">
                <div>
                    <h3 class="fw-bold text-dark mb-0">🏠 Laundry Hub</h3>
                    <span class="text-muted small">ยินดีต้อนรับกลับ, บริหารจัดการร้านของคุณได้เลย</span>
                </div>
                <div class="d-flex gap-3">
                    <button class="btn btn-light rounded-pill p-2 px-3 shadow-sm border" onclick="openReportModal()">
                        <i class="bi bi-chat-dots text-warning"></i> <span class="d-none d-md-inline ms-1">แจ้งปัญหา</span>
                    </button>
                    <div class="vr mx-1"></div>
                    <a href="index.php?link=profile" class="btn btn-primary rounded-circle shadow" style="width:45px;height:45px;display:flex;align-items:center;justify-content:center;">
                        <i class="bi bi-person-fill"></i>
                    </a>
                    <a href="../loginpage/logout.php" class="btn btn-outline-danger rounded-pill px-3 shadow-sm">
                        <i class="bi bi-power"></i>
                    </a>
                </div>
            </div>

           <?php if ($promos): ?>
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex align-items-center mb-3">
                <h5 class="fw-bold mb-0 text-primary"><i class="bi bi-star-fill me-2"></i> โปรโมชั่นและกิจกรรมล่าสุด</h5>
                <span class="badge bg-primary ms-2 rounded-pill"><?= count($promos) ?> รายการ</span>
            </div>
            
            <div id="promoCarousel" class="carousel slide shadow-lg" data-bs-ride="carousel" data-bs-pause="hover" style="border-radius: 30px; overflow: hidden;">
                
                <div class="carousel-indicators">
                    <?php foreach ($promos as $i => $p): ?>
                        <button type="button" data-bs-target="#promoCarousel" data-bs-slide-to="<?= $i ?>" class="<?= $i === 0 ? 'active' : '' ?>" aria-current="<?= $i === 0 ? 'true' : 'false' ?>"></button>
                    <?php endforeach ?>
                </div>
                
                <div class="carousel-inner">
                    <?php foreach ($promos as $i => $p): ?>
                        <div class="carousel-item <?= $i === 0 ? 'active' : '' ?>" data-bs-interval="5000">
                            <div class="promo-banner" style="position:relative; height: 380px;">
                                <?php 
                                    $imagePath = $p['image'];
                                    $finalPath = "../" . $imagePath; 
                                ?>
                                <img src="<?= htmlspecialchars($finalPath) ?>" 
                                     class="d-block w-100 h-100" 
                                     style="object-fit: cover; filter: brightness(0.65);"
                                     onerror="this.src='https://via.placeholder.com/800x400?text=No+Image';">
                                
                                <div class="carousel-caption d-block text-start" style="left: 8%; bottom: 15%; z-index: 10;">
                                    <span class="badge bg-warning text-dark mb-2 px-3 py-2">Promotion</span>
                                    <h1 class="display-6 fw-bold text-white mb-2" style="text-shadow: 2px 2px 10px rgba(0,0,0,0.5);">
                                        <?= htmlspecialchars($p['title']) ?>
                                    </h1>
                                    <p class="mb-0 text-white-50">
                                        <i class="bi bi-calendar-event me-1"></i> 
                                        ระยะเวลา: <?= date('d/m/Y', strtotime($p['start_date'])) ?> - <?= date('d/m/Y', strtotime($p['end_date'])) ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach ?>
                </div>

                <button class="carousel-control-prev" type="button" data-bs-target="#promoCarousel" data-bs-slide="prev">
                    <span class="carousel-control-prev-icon p-3 bg-dark bg-opacity-25 rounded-circle" aria-hidden="true"></span>
                    <span class="visually-hidden">Previous</span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#promoCarousel" data-bs-slide="next">
                    <span class="carousel-control-next-icon p-3 bg-dark bg-opacity-25 rounded-circle" aria-hidden="true"></span>
                    <span class="visually-hidden">Next</span>
                </button>
            </div>
        </div>
    </div>
<?php else: ?>
    <?php endif ?>

            <div class="row g-4 mb-5">
                <div class="col-lg-4 col-md-6">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div class="stat-icon"><i class="bi bi-wallet2"></i></div>
                            <span class="badge-income">Daily</span>
                        </div>
                        <small class="text-muted d-block">รายได้วันนี้</small>
                        <h2 class="fw-bold mb-0 mt-1"><?= number_format($today_income, 2) ?> <span class="fs-6 fw-normal">฿</span></h2>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6">
                    <div class="stat-card" style="background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); color: white;">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div class="stat-icon" style="background:rgba(255,255,255,0.2)"><i class="bi bi-graph-up-arrow"></i></div>
                            <span class="badge rounded-pill bg-light text-primary">Monthly</span>
                        </div>
                        <small class="text-white-50 d-block">รายได้เดือนนี้</small>
                        <h2 class="fw-bold mb-0 mt-1 text-white"><?= number_format($month_income, 2) ?> <span class="fs-6 fw-normal">฿</span></h2>
                    </div>
                </div>

                <div class="col-lg-4 col-md-12">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div class="stat-icon" style="background: linear-gradient(135deg, #ff9966 0%, #ff5e62 100%);"><i class="bi bi-people"></i></div>
                        </div>
                        <small class="text-muted d-block">ฐานลูกค้าของคุณ</small>
                        <h2 class="fw-bold mb-0 mt-1"><?= number_format($total_users) ?> <span class="fs-6 fw-normal">คน</span></h2>
                    </div>
                </div>
            </div>

            <h5 class="fw-bold mb-4 px-2">เมนูการจัดการ</h5>
            <div class="row g-3 mb-5 text-center">
                <div class="col-6 col-md-2">
                    <a href="index.php?link=orders" class="quick-btn">
                        <i class="bi bi-bag-check text-primary"></i> <span>ออเดอร์</span>
                    </a>
                </div>
                <div class="col-6 col-md-2">
                    <a href="index.php?link=delivery" class="quick-btn">
                        <i class="bi bi-truck text-success"></i> <span>จัดส่ง</span>
                    </a>
                </div>
                <div class="col-6 col-md-2">
                    <a href="index.php?link=revenue" class="quick-btn">
                        <i class="bi bi-pie-chart text-info"></i> <span>รายได้</span>
                    </a>
                </div>
                <div class="col-6 col-md-3">
                    <a href="index.php?link=promotion" class="quick-btn">
                        <i class="bi bi-megaphone text-danger"></i> <span>โปรโมชั่น</span>
                    </a>
                </div>
                <div class="col-12 col-md-3">
                    <a href="index.php?link=management" class="quick-btn">
                        <i class="bi bi-person-gear text-secondary"></i> <span>พนักงาน</span>
                    </a>
                </div>
            </div>

            <div class="bg-white p-4 rounded-4 shadow-sm">
    <?php include "body.php"; ?>
</div>

<div id="main-content" class="bg-white p-4 rounded-4 shadow-sm">
    <?php include "body.php"; ?>
</div>

        </div>
    </div>

    <div id="reportModal" style="display:none;position:fixed;inset:0;background:rgba(30,60,114,0.4);z-index:99999;align-items:center;justify-content:center;">
        <div class="modal-content mx-3 p-0 overflow-hidden" style="max-width:480px; width:100%;">
            <div class="p-4 bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0"><i class="bi bi-headset me-2"></i> ศูนย์แจ้งปัญหา</h5>
                <button class="btn-close btn-close-white" onclick="closeReportModal()"></button>
            </div>
            <form method="post" action="report_store_action.php" class="p-4 bg-white">
                <div class="mb-3">
                    <label class="form-label fw-bold">ระบุหัวข้อปัญหา</label>
                    <input type="text" name="title" class="form-control rounded-3 border-light bg-light" placeholder="เช่น ระบบขัดข้อง, ปัญหาการชำระเงิน" required>
                </div>
                <div class="mb-4">
                    <label class="form-label fw-bold">อธิบายรายละเอียด</label>
                    <textarea name="message" rows="4" class="form-control rounded-3 border-light bg-light" placeholder="ระบุสิ่งที่เกิดขึ้น..." required></textarea>
                </div>
                <div class="d-grid gap-2">
                    <button class="btn btn-primary btn-lg rounded-pill fw-bold py-3 shadow">ส่งรายงานทันที</button>
                    <button type="button" class="btn btn-link text-muted" onclick="closeReportModal()">ไว้วันหลัง</button>
                </div>
            </form>
        </div>
    </div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        window.addEventListener('DOMContentLoaded', (event) => {
        // ตรวจสอบว่าใน URL มี parameter "link" หรือไม่
        const urlParams = new URLSearchParams(window.location.search);
        
        if (urlParams.has('link')) {
            // ค้นหา element ที่เราตั้ง id ไว้
            const contentDiv = document.getElementById('main-content');
            
            if (contentDiv) {
                // สั่งให้วิ่งลงไปแบบนุ่มนวล (smooth)
                // offset เล็กน้อยเพื่อให้ไม่ติดขอบบนเกินไป
                const yOffset = -20; 
                const y = contentDiv.getBoundingClientRect().top + window.pageYOffset + yOffset;

                window.scrollTo({top: y, behavior: 'smooth'});
            }
        }
    });
        function openReportModal() {
            reportModal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function closeReportModal() {
            reportModal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }
        reportModal.addEventListener('click', e => {
            if (e.target.id === 'reportModal') closeReportModal();
        });
    </script>

</body>

</html>