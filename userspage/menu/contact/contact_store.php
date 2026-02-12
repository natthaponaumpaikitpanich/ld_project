<?php
session_start();
require_once "../../../ld_db.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: ../../../loginpage/login.php");
    exit;
}

$customer_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT DISTINCT
        s.id,
        s.name,
        s.phone,
        s.address
    FROM orders o
    JOIN stores s ON o.store_id = s.id
    WHERE o.customer_id = ?
    ORDER BY s.name
");
$stmt->execute([$customer_id]);
$stores = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>ติดต่อร้าน | Laundry Platform</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<link href="../../../bootstrap/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="icon" href="../../../image/3.jpg">



<style>
    :root {
        --sky-blue: #0ea5e9;
        --deep-blue: #1e40af;
        --bg-gradient: linear-gradient(180deg, #f0f9ff 0%, #e0f2fe 100%);
    }

    body {
        font-family: 'Kanit', sans-serif;
        background: var(--bg-gradient);
        min-height: 100vh;
        color: #334155;
    }

    /* หัวข้อหน้า */
    .contact-header {
        padding: 40px 0 20px;
        text-align: center;
    }
    .contact-header i {
        font-size: 2.5rem;
        color: var(--sky-blue);
        margin-bottom: 10px;
    }

    /* การ์ดร้านค้า */
    .store-card {
        background: rgba(255, 255, 255, 0.9);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.5);
        border-radius: 24px;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        overflow: hidden;
    }

    .store-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.05), 0 10px 10px -5px rgba(0, 0, 0, 0.02);
        background: #ffffff;
    }

    /* รูป Icon ร้าน */
    .store-avatar {
        width: 60px;
        height: 60px;
        border-radius: 18px;
        background: linear-gradient(135deg, #0ea5e9 0%, #2563eb 100%);
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        box-shadow: 0 8px 15px rgba(14, 165, 233, 0.3);
    }

    /* ปุ่มติดต่อ */
    .btn-contact {
        padding: 8px 20px;
        font-weight: 500;
        transition: all 0.2s;
        border: none;
    }
    .btn-call {
        background-color: #f0fdf4;
        color: #15803d;
    }
    .btn-call:hover {
        background-color: #15803d;
        color: white;
    }
    .btn-map {
        background-color: #eff6ff;
        color: #1d4ed8;
    }
    .btn-map:hover {
        background-color: #1d4ed8;
        color: white;
    }

    /* แถบแจ้งปัญหา (ลูกเล่นใหม่) */
    .report-badge {
        font-size: 0.75rem;
        padding: 4px 12px;
        border-radius: 10px;
        background: #fff1f2;
        color: #e11d48;
        display: inline-block;
        margin-bottom: 8px;
    }
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
/* --- Dark Mode ปรับปรุงใหม่ (มืดเฉพาะโครงสร้าง) --- */
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
    color: #ffffff !important;
}
</style>

<body>

<div class="container py-3">
    
    <div class="contact-header">
        <i class="bi bi-chat-dots-fill"></i>
        <h4 class="fw-bold">ศูนย์ช่วยเหลือลูกค้า</h4>
        <p class="text-muted">เลือกทางเลือกที่คุณต้องการติดต่อร้านค้า</p>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            
            <?php if (!$stores): ?>
            <div class="card store-card p-5 text-center">
                <div class="mb-3">
                    <i class="bi bi-info-circle fs-1 text-muted"></i>
                </div>
                <h5>ไม่พบรายการร้านค้า</h5>
                <p class="text-muted">เมื่อคุณเริ่มใช้บริการร้านซัก รายชื่อจะปรากฏที่นี่ครับ</p>
            </div>
            <?php endif; ?>

            <?php foreach ($stores as $s): ?>
            <div class="card store-card mb-4 shadow-sm">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center gap-4">
                        <div class="store-avatar flex-shrink-0">
                            <i class="bi bi-shop"></i>
                        </div>
                        <div class="flex-grow-1">
                            <span class="report-badge fw-bold"><i class="bi bi-exclamation-circle me-1"></i> มีปัญหาเรื่องผ้า?</span>
                            <h5 class="fw-bold mb-1 text-dark"><?= htmlspecialchars($s['name']) ?></h5>
                            <p class="text-muted small mb-0">
                                <i class="bi bi-geo-alt-fill me-1"></i> <?= htmlspecialchars($s['address']) ?>
                            </p>
                        </div>
                    </div>

                    <hr class="my-3 opacity-50">

                    <div class="d-flex gap-2">
                        <?php if ($s['phone']): ?>
                        <a href="tel:<?= $s['phone'] ?>" class="btn btn-contact btn-call flex-fill rounded-pill">
                            <i class="bi bi-telephone-fill me-2"></i> โทรหาเรา
                        </a>
                        <?php endif; ?>

                        <a href="https://www.google.com/maps/search/?api=1&query=<?= urlencode($s['address'] . ' ' . $s['name']) ?>"
                           target="_blank" class="btn btn-contact btn-map flex-fill rounded-pill text-center">
                            <i class="bi bi-map-fill me-2"></i> นำทาง
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>

            <a href="../../index.php" class="btn btn-link text-decoration-none text-muted w-100 mt-2 py-3">
                <i class="bi bi-arrow-left me-2"></i> กลับหน้าหลัก
            </a>

        </div>
    </div>
</div>
<div class="dark-mode-toggle" onclick="toggleDarkMode()" title="สลับโหมดกลางคืน">
    <i class="bi bi-moon-stars-fill" id="dark-icon"></i>
</div>
<script src="../../../bootstrap/js/bootstrap.bundle.min.js"></script>

<script>
    // ลูกเล่นเพิ่มเสียงคลิกเบาๆ หรือ Feedback เมื่อกด
    document.querySelectorAll('.store-card').forEach(card => {
        card.addEventListener('mousedown', () => {
            card.style.transform = 'scale(0.97)';
        });
        card.addEventListener('mouseup', () => {
            card.style.transform = 'translateY(-8px) scale(1)';
        });
    });
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

</body>
</body>
</html>
