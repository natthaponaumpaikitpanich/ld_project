<?php
include_once '../../ld_db.php';
// กำหนดตัวแปร link ไว้ตรงนี้เพื่อใช้เช็ค class active
$link = $_GET['link'] ?? 'Dashboard';
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <link rel="icon" href="../../image/3.jpg">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="../../bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600&display=swap" rel="stylesheet">
    <title>ระบบซักอบรีด - Admin</title>

    <style>
        /* CSS เดิมของคุณทั้งหมด (คงไว้เหมือนเดิม) */
        :root {
            --sidebar-width: 280px;
            --sidebar-collapsed-width: 85px;
            --primary-color: #3b82f6;
            --bg-gradient: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            --transition-speed: 0.4s;
        }

        body {
            margin: 0;
            background: #f8fafc;
            font-family: 'Kanit', sans-serif;
            overflow-x: hidden;
        }

        .sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            background: var(--bg-gradient);
            color: #f1f5f9;
            z-index: 1000;
            transition: all var(--transition-speed) cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 4px 0 25px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
        }

        .sidebar.collapsed {
            width: var(--sidebar-collapsed-width);
        }

        .logo-details {
            height: 70px;
            display: flex;
            align-items: center;
            padding: 0 20px;
            background: rgba(255, 255, 255, 0.03);
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            overflow: hidden;
            white-space: nowrap;
        }

        .logo-details i {
            font-size: 28px;
            color: var(--primary-color);
            min-width: 45px;
            text-align: center;
        }

        .logo-name {
            font-size: 20px;
            font-weight: 600;
            margin-left: 12px;
            background: linear-gradient(to right, #fff, #94a3b8);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            transition: opacity 0.3s;
        }

        .sidebar.collapsed .logo-name {
            opacity: 0;
            pointer-events: none;
        }

        .toggle-btn {
            position: absolute;
            top: 22px;
            right: -12px;
            width: 24px;
            height: 24px;
            background: var(--primary-color);
            color: white;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 12px;
            box-shadow: 0 4px 10px rgba(59, 130, 246, 0.4);
            z-index: 1001;
            transition: transform 0.3s;
        }

        .sidebar.collapsed .toggle-btn {
            transform: rotate(180deg);
        }

        .nav-list {
            margin-top: 15px;
            padding: 0 12px;
            overflow-y: auto;
            flex-grow: 1;
        }

        .nav-list::-webkit-scrollbar {
            display: none;
        }

        .sidebar a {
            display: flex;
            align-items: center;
            text-decoration: none;
            padding: 12px 15px;
            margin-bottom: 4px;
            border-radius: 12px;
            color: #94a3b8;
            transition: all 0.3s ease;
            position: relative;
            white-space: nowrap;
        }

        .sidebar a i {
            font-size: 20px;
            min-width: 40px;
            transition: transform 0.3s ease;
        }

        .sidebar a span {
            font-size: 15px;
            font-weight: 400;
            margin-left: 5px;
            transition: opacity 0.3s;
        }

        .sidebar.collapsed a span {
            opacity: 0;
            display: none;
        }

        .sidebar a:hover {
            background: rgba(255, 255, 255, 0.08);
            color: #fff;
        }

        .sidebar a:hover i {
            transform: scale(1.2);
            color: var(--primary-color);
        }

        .sidebar a.active {
            background: rgba(59, 130, 246, 0.15);
            color: #fff;
        }

        .sidebar a.active i {
            color: var(--primary-color);
        }

        .sidebar hr {
            border-top: 1px solid rgba(255, 255, 255, 0.05);
            margin: 15px 10px;
        }

        .logout-link:hover {
            background: rgba(239, 68, 68, 0.15) !important;
            color: #ef4444 !important;
        }
     .main-content {
    flex: 1;
    min-width: 0;
    min-height: 100vh;  /* ⭐ ทำให้ body.php โผล่ */
    overflow-y: auto;
}



/* Sidebar ไม่เปลี่ยน */

.layout {
    display: flex;
    min-height: 100vh;   /* สำคัญมาก */
}


.sidebar.collapsed {
    width: var(--sidebar-collapsed-width);
}


        .badge-alert {
            font-size: 10px;
            padding: 2px 6px;
            background: #ef4444;
            color: white;
            border-radius: 50px;
            margin-left: auto;
        }
    </style>
</head>

<body class="layout">

    <div id="sidebar" class="sidebar">
        <div class="toggle-btn" onclick="toggleSidebar()">
            <i class="bi bi-chevron-left"></i>
        </div>

        <div class="logo-details">
            <i class="bi bi-water"></i>
            <span class="logo-name">Laundry Admin</span>
        </div>

        <div class="nav-list">
            <a href="sidebar.php?link=Dashboard" class="<?= ($link == 'Dashboard' ? 'active' : '') ?>">
                <i class="bi bi-grid-1x2-fill"></i>
                <span>Dashboard</span>
            </a>

            <a href="sidebar.php?link=promotion" class="<?= ($link == 'promotion' ? 'active' : '') ?>">
                <i class="bi bi-megaphone-fill"></i>
                <span>เพิ่มโปรโมชั่น</span>
            </a>

            <a href="sidebar.php?link=allstore" class="<?= ($link == 'allstore' ? 'active' : '') ?>">
                <i class="bi bi-shop-window"></i>
                <span>ร้านค้าทั้งหมด</span>
            </a>

            <hr>

            <a href="sidebar.php?link=setting" class="<?= ($link == 'setting' ? 'active' : '') ?>">
                <i class="bi bi-gear-wide-connected"></i>
                <span>ตั้งค่ารายเดือน</span>
            </a>

            <a href="sidebar.php?link=payments" class="<?= ($link == 'payments' ? 'active' : '') ?>">
                <i class="bi bi-credit-card-2-back-fill"></i>
                <span>การชำระเงิน</span>
            </a>

            <a href="sidebar.php?link=overdue" class="<?= ($link == 'overdue' ? 'active' : '') ?>">
                <i class="bi bi-exclamation-octagon-fill"></i>
                <span>ร้านที่ค้างจ่าย</span>
                <span class="badge-alert">HOT</span>
            </a>

            <hr>

            <a href="sidebar.php?link=transactions" class="<?= ($link == 'transactions' ? 'active' : '') ?>">
                <i class="bi bi-bar-chart-line-fill"></i>
                <span>รายงานธุรกรรม</span>
            </a>

            <a href="sidebar.php?link=reports" class="<?= ($link == 'reports' ? 'active' : '') ?>">
                <i class="bi bi-chat-left-dots-fill"></i>
                <span>รายงานจากร้าน</span>
            </a>

            <hr style="margin-top: auto;">

            <a href="../../loginpage/logout.php" class="logout-link">
                <i class="bi bi-power"></i>
                <span>ออกจากระบบ</span>
            </a>
        </div>
    </div>

  
        <?php include_once '../body.php'; ?>
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById("sidebar");
            const mainContent = document.getElementById("mainContent");
            sidebar.classList.toggle("collapsed");
            mainContent.classList.toggle("collapsed");

            const isCollapsed = sidebar.classList.contains("collapsed");
            localStorage.setItem("sidebarStatus", isCollapsed ? "closed" : "open");
        }

        // ตรวจสอบสถานะเมื่อโหลดหน้า
        window.addEventListener('DOMContentLoaded', () => {
            if (localStorage.getItem("sidebarStatus") === "closed") {
                document.getElementById("sidebar").classList.add("collapsed");
                document.getElementById("mainContent").classList.add("collapsed");
            }
        });
    </script>
</body>

</html>