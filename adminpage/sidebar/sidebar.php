  <?php  include_once '../../ld_db.php';
    ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <link rel="icon" href="../../image/3.jpg">
    </link>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="../../bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="../../bootstrap/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/style.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Kanit:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <title>ซักอบรีด</title>
</head>
<style>
    /* ===== GLOBAL ===== */
body {
    margin: 0;
    background: #f7f7f7;
    font-family: 'Segoe UI', Tahoma, sans-serif;
}

/* ===== SIDEBAR ===== */
.sidebar {
    width: 260px;
    height: 100vh;
    position: fixed;
    top: 0;
    left: 0;
    background: linear-gradient(180deg, #0f172a, #020617);
    color: #e5e7eb;
    z-index: 1000;
    transition: width .3s ease;
    overflow-x: hidden;
}

/* collapsed */
.sidebar.collapsed {
    width: 72px;
}

.sidebar.collapsed span {
    display: none;
}

/* ===== LOGO ===== */
.sidebar .logo {
    padding: 22px 20px;
    font-size: 18px;
    font-weight: 600;
    text-align: center;
    letter-spacing: .5px;
    border-bottom: 1px solid rgba(255,255,255,.08);
}

/* ===== TOGGLE ===== */
.toggle-btn {
    position: absolute;
    top: 22px;
    right: -14px;
    width: 28px;
    height: 28px;
    background: #1e293b;
    border-radius: 50%;
    border: 2px solid #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: .2s;
}

.toggle-btn:hover {
    background: #334155;
}

/* ===== MENU LINKS ===== */
.sidebar a {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 13px 22px;
    color: #cbd5e1;
    text-decoration: none;
    font-size: 14.5px;
    transition: all .2s ease;
    position: relative;
}

/* icon */
.sidebar a i {
    font-size: 18px;
    min-width: 20px;
}

/* hover */
.sidebar a:hover {
    background: rgba(255,255,255,.06);
    color: #ffffff;
}

/* active (รองรับอนาคต ถ้าอยากใส่ class active) */
.sidebar a.active {
    background: rgba(59,130,246,.15);
    color: #ffffff;
}

.sidebar a.active::before {
    content: "";
    position: absolute;
    left: 0;
    top: 0;
    width: 4px;
    height: 100%;
    background: #3b82f6;
}

/* ===== SECTION DIVIDER ===== */
.sidebar hr {
    border: none;
    height: 1px;
    background: rgba(255,255,255,.08);
    margin: 12px 16px;
}
.main-content {
    margin-left: 260px;
    padding: 24px 0 24px 0; /* บน ขวา ล่าง ซ้าย */
    min-height: 100vh;
    transition: margin-left .3s ease;
}

.main-content.collapsed {
    margin-left: 72px;
}

</style>
<?php
include_once '../body.php';
$report_id = $_GET['id'] ?? null;
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>Admin Panel</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Bootstrap OFFLINE -->
    <link href="../bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.css" rel="stylesheet">
</head>
<!-- ===== SIDEBAR ===== -->
<div id="sidebar" class="sidebar">

    <div class="logo">
        Admin Panel
    </div>

    <div class="toggle-btn" onclick="toggleSidebar()">
        <i class="bi bi-list"></i>
    </div>

    <a href="sidebar.php?link=Dashboard">
        <i class="bi bi-speedometer2"></i>
        <span>Dashboard</span>
    </a>

    <a href="../promotion/index.php">
        <i class="bi bi-bell-fill"></i>
        <span>เพิ่มโปรโมชั่น</span>
    </a>

    <a href="sidebar.php?link=allstore">
        <i class="bi bi-shop"></i>
        <span>ร้านค้าทั้งหมด</span>
    </a>

    <hr>

    <a href="sidebar.php?link=setting">
        <i class="bi bi-gear"></i>
        <span>ตั้งค่าคิดเงินรายเดือน</span>
    </a>

    <a href="sidebar.php?link=payments">
        <i class="bi bi-wallet2"></i>
        <span>ดูการชำระเงินทั้งหมด</span>
    </a>

    <a href="sidebar.php?link=overdue">
        <i class="bi bi-exclamation-triangle"></i>
        <span>ร้านค้าที่ค้างจ่าย</span>
    </a>

    <hr>

    <a href="sidebar.php?link=transactions">
        <i class="bi bi-bar-chart"></i>
        <span>รายงานธุรกรรม</span>
    </a>

    <a href="sidebar.php?link=reports">
        <i class="bi bi-flag-fill"></i>
        <span>รายงานจากร้าน</span>
    </a>

    <hr>

    <a href="../../loginpage/logout.php">
        <i class="bi bi-box-arrow-left"></i>
        <span>ออกจากระบบ</span>
    </a>

</div>


<script>
function toggleSidebar() {
    document.getElementById("sidebar").classList.toggle("collapsed");
    document.getElementById("mainContent").classList.toggle("collapsed");
}
</script>

</body>

</html>