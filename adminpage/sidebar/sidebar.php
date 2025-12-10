<style>
/* --- Sidebar Layout --- */
body .sidebar {
    width: 260px;
    height: 100vh;
    position: fixed;
    background: #14253fff;/* เทาเข้ม */
    color: white;
    transition: 0.3s;
    overflow: hidden;
}

.sidebar.collapsed {
    width: 70px;
}

/* Logo section */
.sidebar .logo {
    padding: 20px;
    font-size: 20px;
    font-weight: bold;
    text-align: center;
    border-bottom: 1px solid #FFFFFF ;
}

/* Menu */
.sidebar a {
    display: flex;
    align-items: center;
    padding: 14px 20px;
    color: #e5e7eb;
    text-decoration: none;
    gap: 12px;
    transition: 0.2s;
}

.sidebar a:hover {
    background: #111827;
    color: #FFFFFF ;

}

.sidebar.collapsed a span {
    display: none;
}

.sidebar i {
    font-size: 20px;
}

/* Toggle Button */
.toggle-btn {
    position: absolute;
    top: 20px;
    right: -15px;
    width: 30px;
    height: 30px;
    background: #1f2937;    
    color: white;
    border-radius: 50%;
    border: 2px solid white;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
}
</style>
<?php
include_once '../index.php';
include_once '../body.php';
?>
<!-- ===== SIDEBAR ===== -->
<div id="sidebar" class="sidebar">

    <div class="logo">
        Admin Panel
    </div>

    <div class="toggle-btn" onclick="toggleSidebar()">
        <i class="bi bi-list"></i>
    </div>

    <!-- MAIN -->
    <a href="sidebar.php?link=Dashboard"><i class="bi bi-speedometer2"></i> <span>Dashboard</span></a>
    <a href="sidebar.php?link=allstore"><i class="bi bi-shop"></i> <span>ร้านค้าทั้งหมด</span></a>
    <a href="sidebar.php?link=create"><i class="bi bi-plus-circle"></i> <span>สมัครร้านค้าใหม่</span></a>

    <!-- BILLING -->
    <hr style="border-color:#FFFFFF ;">
    <a href="sidebar.php?link=setting"><i class="bi bi-gear"></i> <span>ตั้งค่าคิดเงินรายเดือน</span></a>
    <a href="billing/payments.php"><i class="bi bi-wallet2"></i> <span>ดูการชำระเงินทั้งหมด</span></a>
    <a href="billing/overdue.php"><i class="bi bi-exclamation-triangle"></i> <span>ร้านค้าที่ค้างจ่าย</span></a>

    <!-- SYSTEM -->
    <hr style="border-color:#FFFFFF ;">
    <a href="system/stats.php"><i class="bi bi-bar-chart"></i> <span>สถิติรวมทั้งระบบ</span></a>
    <a href="system/transactions.php"><i class="bi bi-reception-4"></i> <span>รายงานธุรกรรม</span></a>
    <a href="system/daily.php"><i class="bi bi-calendar-event"></i> <span>การใช้งานระบบรายวัน</span></a>

    <!-- LOGOUT -->
    <hr style="border-color:#FFFFFF ;">
    <a href="../logout.php"><i class="bi bi-box-arrow-left"></i> <span>ออกจากระบบ</span></a>

</div>

<script>
function toggleSidebar() {
    document.getElementById("sidebar").classList.toggle("collapsed");
}
</script>

<!-- Bootstrap Icons -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
