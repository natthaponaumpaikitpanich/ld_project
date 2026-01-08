<?php
session_start();
require_once "../ld_db.php";

/* ================= MIDDLEWARE SUBSCRIPTION ================= */
include "middleware_subscription.php";

/* ================= BASIC AUTH ================= */
if (!isset($_SESSION['store_id'])) {
    header("Location: create_store.php");
    exit;
}

/* ================= PROMOTIONS (จากแอดมิน) ================= */
$sql = "
    SELECT title, image
    FROM promotions
    WHERE status = 'active'
      AND start_date <= CURDATE()
      AND end_date >= CURDATE()
    ORDER BY created_at DESC
";
$promos = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

/* ================= TODAY INCOME ================= */
$stmt = $pdo->prepare("
    SELECT IFNULL(SUM(p.amount),0)
    FROM payments p
    JOIN orders o ON p.order_id = o.id
    WHERE p.status = 'confirmed'
      AND o.store_id = ?
      AND DATE(p.confirmed_at) = CURDATE()
");
$stmt->execute([$store_id]);
$today_income = $stmt->fetchColumn();

/* ================= MONTH INCOME ================= */
$stmt = $pdo->prepare("
    SELECT IFNULL(SUM(p.amount),0)
    FROM payments p
    JOIN orders o ON p.order_id = o.id
    WHERE p.status = 'confirmed'
      AND o.store_id = ?
      AND MONTH(p.confirmed_at) = MONTH(CURDATE())
      AND YEAR(p.confirmed_at) = YEAR(CURDATE())
");
$stmt->execute([$store_id]);
$month_income = $stmt->fetchColumn();

/* ================= TOTAL USERS (ลูกค้า ไม่รวม admin / owner) ================= */
$userStmt = $pdo->query("
    SELECT COUNT(*)
    FROM users
    WHERE role NOT IN ('platform_admin','store_owner')
");
$total_users = (int)$userStmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>Dashboard ร้านซักอบรีด</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link href="../bootstrap/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.css" rel="stylesheet">

<style>
body {
    background:#f5f7fb;
    font-family:'Kanit',sans-serif;
}

/* ===== LOCK MODE ===== */
body.store-locked #app {
    filter: blur(6px);
    pointer-events: none;
    user-select: none;
}

/* ===== POPUP OVERLAY ===== */
#subscription-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,.6);
    z-index: 99999;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* ===== UI ===== */
.card { border-radius:14px;border:none }
.stat-card{display:flex;justify-content:space-between;align-items:center}
.stat-icon{
    width:48px;height:48px;border-radius:12px;
    display:flex;align-items:center;justify-content:center;
    font-size:22px;color:#fff
}
.quick-btn{
    border-radius:16px;color:#fff;
    padding:22px;font-weight:600;
    box-shadow:0 10px 20px rgba(0,0,0,.08)
}
.bg-grad-orders{background:linear-gradient(135deg,#2097ff,#52ebf3,#00ffa2)}
.bg-grad-delivery{background:linear-gradient(135deg,#4facfe,#00f2fe)}
.bg-grad-revenue{background:linear-gradient(135deg,#43e97b,#38f9d7)}
.bg-grad-staff{background:linear-gradient(135deg,#ac5ff9,#ff009d)}
.bg-grad-promotion{background:linear-gradient(135deg,#ff06d5,#00d9ff)}
</style>
</head>

<body class="<?= $STORE_LOCKED ? 'store-locked' : '' ?>">

<?php
/* ===== POPUP เลือกแพ็กเกจ ===== */
if ($STORE_LOCKED) {
    include "menu/subscription/popup_plan.php";
}
?>

<div id="app">
<div class="container py-4">

<!-- ===== HEADER ===== -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0">Dashboard ของเจ้าของร้าน</h4>
        <small class="text-muted">บริหารจัดการธุรกิจซักอบรีด</small>
    </div>
    <div class="d-flex gap-2">
        <a href="../loginpage/logout.php" class="btn btn-danger bi bi-box-arrow-left"> ออกจากระบบ</a>
        <a href="index.php?link=profile" class="btn btn-primary bi bi-person"> โปรไฟล์</a>
    </div>
</div>

<!-- ===== PROMOTION ===== -->
<?php if ($promos): ?>
<div id="promoCarousel" class="carousel slide mb-4" data-bs-ride="carousel">
<div class="carousel-inner rounded shadow">
<?php foreach ($promos as $i=>$p): ?>
<div class="carousel-item <?= $i==0?'active':'' ?>">
<img src="../adminpage/promotion/uploads/<?= htmlspecialchars($p['image']) ?>"
     class="d-block w-100" style="height:380px;object-fit:cover">
</div>
<?php endforeach ?>
</div>
</div>
<?php endif ?>

<!-- ===== SUMMARY ===== -->
<div class="row g-3 mb-4">
<div class="col-md-4">
<div class="card p-3 stat-card">
<div>
<small>รายได้วันนี้</small>
<h4><?= number_format($today_income,2) ?> ฿</h4>
</div>
<div class="stat-icon bg-primary"><i class="bi bi-currency-dollar"></i></div>
</div>
</div>

<div class="col-md-4">
<div class="card p-3 stat-card">
<div>
<small>รายได้เดือนนี้</small>
<h4><?= number_format($month_income,2) ?> ฿</h4>
</div>
<div class="stat-icon bg-info"><i class="bi bi-graph-up"></i></div>
</div>
</div>

<div class="col-md-4">
<div class="card p-3 stat-card">
<div>
<small>ผู้ใช้ทั้งหมด</small>
<h4><?= number_format($total_users) ?> คน</h4>
</div>
<div class="stat-icon bg-success"><i class="bi bi-person"></i></div>
</div>
</div>
</div>

<!-- ===== QUICK MENU ===== -->
<div class="row g-2 mb-4">
<div class="col-md-2"><a href="index.php?link=orders" class="btn quick-btn bg-grad-orders w-100">ออเดอร์</a></div>
<div class="col-md-2"><a href="index.php?link=delivery" class="btn quick-btn bg-grad-delivery w-100">จัดส่ง</a></div>
<div class="col-md-2"><a href="index.php?link=revenue" class="btn quick-btn bg-grad-revenue w-100">รายได้</a></div>
<div class="col-md-3"><a href="index.php?link=promotion" class="btn quick-btn bg-grad-promotion w-100">โปรโมชั่น</a></div>
<div class="col-md-3"><a href="index.php?link=management" class="btn quick-btn bg-grad-staff w-100">พนักงาน</a></div>
</div>

<?php include "body.php"; ?>

</div>
</div>

<script src="../bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
