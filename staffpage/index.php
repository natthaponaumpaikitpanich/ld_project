<?php
session_start();
require_once "../ld_db.php"; // PDO
include_once "assets/boostap.php";

// --------------------
// ตรวจสิทธิ์
// --------------------
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    die('ไม่มีสิทธิ์เข้าถึงหน้านี้');
}
$user_id = $_SESSION['user_id'];

// ดึงจำนวนงานที่ค้างอยู่ (สำหรับแสดง Badge บนเมนู)
$stmtCount = $pdo->prepare("
    SELECT COUNT(o.id) 
    FROM orders o 
    JOIN store_staff ss ON ss.store_id = o.store_id 
    WHERE ss.user_id = ? AND o.status != 'completed'
");
$stmtCount->execute([$user_id]);
$taskCount = $stmtCount->fetchColumn();

// จัดการเรื่อง Active Link
$current_page = $_GET['link'] ?? 'Home';
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard - งานบริการ</title>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <style>
        :root {
            --staff-blue: #007bff;
            --staff-soft-blue: #e7f1ff;
            --staff-dark: #2c3e50;
        }

        body {
            font-family: 'Kanit', sans-serif;
            background: #f0f5fa; /* พื้นหลังฟ้าอ่อนมากๆ */
            padding-bottom: 80px; /* กันโดน Bottom Nav ทับ */
        }

        /* Top Bar */
        .top-header {
            background: #fff;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .user-profile-section {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .logout-pill {
            background: #fff0f0;
            color: #ff4d4d;
            border: 1px solid #ffebeb;
            padding: 5px 12px;
            border-radius: 50px;
            text-decoration: none;
            font-size: 13px;
            transition: 0.3s;
        }
        .logout-pill:hover { background: #ff4d4d; color: #fff; }

        /* Bottom Navigation */
        .staff-bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: 70px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-top: 1px solid rgba(0,0,0,0.05);
            display: flex;
            justify-content: space-around;
            align-items: center;
            z-index: 999;
            padding-bottom: env(safe-area-inset-bottom);
            box-shadow: 0 -5px 20px rgba(0,0,0,0.03);
        }

        .nav-item {
            text-decoration: none;
            color: #94a3b8;
            font-size: 11px;
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
            transition: 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            flex: 1;
        }

        .nav-item i {
            font-size: 22px;
            margin-bottom: 2px;
            transition: 0.3s;
        }

        .nav-item.active {
            color: var(--staff-blue);
        }

        .nav-item.active i {
            transform: translateY(-5px);
            color: var(--staff-blue);
        }

        /* Indicator จุดใต้เมนู */
        .nav-item.active::after {
            content: '';
            position: absolute;
            bottom: -5px;
            width: 4px;
            height: 4px;
            background: var(--staff-blue);
            border-radius: 50%;
        }

        /* Badge งานค้าง */
        .badge-count {
            position: absolute;
            top: -2px;
            right: 25%;
            background: #ff4d4d;
            color: white;
            font-size: 10px;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            border: 2px solid #fff;
            font-weight: bold;
        }

        /* เนื้อหาภายใน */
        .content-area {
            padding: 20px;
            animation: fadeIn 0.4s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>

    <header class="top-header">
        <div class="user-profile-section">
            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 35px; height: 35px;">
                <i class="bi bi-person-badge"></i>
            </div>
            <div>
                <small class="text-muted d-block" style="font-size: 10px; line-height: 1;">พนักงาน</small>
                <span class="fw-bold" style="font-size: 14px;"><?= $_SESSION['display_name'] ?? 'Staff' ?></span>
            </div>
        </div>
        <a href="../loginpage/logout.php" class="logout-pill shadow-sm" onclick="return confirm('ยืนยันการออกจากระบบ?')">
            <i class="bi bi-power"></i> ออก
        </a>
    </header>

    <main class="content-area">
        <?php 
            // สามารถเขียน Logic ตรงนี้เพื่อแยกไฟล์เนื้อหาได้
            include_once "body.php"; 
        ?>
    </main>

    <nav class="staff-bottom-nav">
        <a href="index.php?link=Home" class="nav-item <?= $current_page == 'Home' ? 'active' : '' ?>">
            <i class="bi bi-house-door-fill"></i>
            <span>หน้าหลัก</span>
        </a>
        
        <a href="index.php?link=Tasks" class="nav-item <?= $current_page == 'Tasks' ? 'active' : '' ?>">
            <i class="bi bi-clipboard2-check-fill"></i>
            <?php if($taskCount > 0): ?>
                <span class="badge-count"><?= $taskCount ?></span>
            <?php endif; ?>
            <span>งานของฉัน</span>
        </a>

        <a href="index.php?link=Map" class="nav-item <?= $current_page == 'Map' ? 'active' : '' ?>">
            <i class="bi bi-geo-alt-fill"></i>
            <span>แผนที่</span>
        </a>

        <a href="index.php?link=Profile" class="nav-item <?= $current_page == 'Profile' ? 'active' : '' ?>">
            <i class="bi bi-person-circle"></i>
            <span>โปรไฟล์</span>
        </a>
    </nav>

    <script src="../bootstrap/js/bootstrap.bundle.min.js"></script>
    <script>
        // ลูกเล่นเพิ่ม: สั่นสะเทือนเบาๆ เวลาเปลี่ยนเมนู (Haptic Touch จำลอง)
        document.querySelectorAll('.nav-item').forEach(item => {
            item.addEventListener('click', () => {
                if (window.navigator.vibrate) {
                    window.navigator.vibrate(10); 
                }
            });
        });
    </script>
</body>
</html>