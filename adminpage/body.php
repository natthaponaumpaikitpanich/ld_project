<?php
include_once '../../ld_db.php';
$link = $_GET['link'] ?? 'Dashboard';
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <style>
        /* CSS ตัวเดิมที่คุณมี (ที่ผมปรับให้ข้างบน) ใส่ตรงนี้ */
        :root {
            --sidebar-width: 280px;
            --sidebar-collapsed-width: 85px;
            --transition-speed: 0.4s;
        }
        body { margin: 0; background: #f8fafc; font-family: 'Kanit', sans-serif; }
        
        .main-content {
            margin-left: var(--sidebar-width); /* เว้นที่ให้ Sidebar */
            padding: 24px;
            transition: all var(--transition-speed) ease;
        }
        .main-content.collapsed {
            margin-left: var(--sidebar-collapsed-width); /* ยุบตาม Sidebar */
        }
    </style>
</head>
<body>

    <?php include_once "sidebar/sidebar.php"; ?>

    <div id="mainContent" class="main-content">
        <?php
        // Logic การเลือกหน้า
        switch($link) {
            case 'promotion':    include "../promotion/index.php"; break;
            case 'allstore':     include "stores/index.php"; break;
            case 'setting':      include "billing/plan.php"; break;
            case 'payments':     include "billing/payments.php"; break;
            case 'overdue':      include "billing/overdue.php"; break;
            case 'transactions': include "system/transactions.php"; break;
            case 'reports':      include "system/store_report.php"; break;
            default:             include "sidebar/Dashboard.php"; break;
        }
        ?>
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById("sidebar");
            const mainContent = document.getElementById("mainContent");
            sidebar.classList.toggle("collapsed");
            mainContent.classList.toggle("collapsed");
            localStorage.setItem("sidebarStatus", sidebar.classList.contains("collapsed") ? "closed" : "open");
        }
        
        // เช็คสถานะตอนโหลดหน้า
        if (localStorage.getItem("sidebarStatus") === "closed") {
            document.getElementById("sidebar").classList.add("collapsed");
            document.getElementById("mainContent").classList.add("collapsed");
        }
    </script>
</body>
</html>