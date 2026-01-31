<?php
session_start();
require_once "../ld_db.php";
include "middleware_subscription.php";

if (!isset($_SESSION['store_id'])) {
    header("Location: create_store.php");
    exit;
}

$store_id = $_SESSION['store_id'];

/* PROMOTIONS */
$sql = "
    SELECT title, image
    FROM promotions
    WHERE status = 'active'
      AND start_date <= CURDATE()
      AND end_date >= CURDATE()
    ORDER BY created_at DESC
";
$promos = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

/* TODAY INCOME */
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

/* MONTH INCOME */
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

/* TOTAL USERS */
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
<meta name="viewport" content="width=device-width, initial-scale=1">

<link rel="icon" href="../image/3.jpg">
<link href="../bootstrap/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<style>
body{
    background:#f5f7fb;
    font-family:'Kanit',sans-serif;
}

/* LOCK MODE */
body.store-locked #app{
    filter:blur(5px);
    pointer-events:none;
}

/* CARDS */
.card{
    border-radius:18px;
    border:none;
    box-shadow:0 12px 30px rgba(0,0,0,.08);
}

/* STAT */
.stat-card{
    display:flex;
    justify-content:space-between;
    align-items:center;
}
.stat-icon{
    width:46px;
    height:46px;
    border-radius:12px;
    background:#1e3c72;
    display:flex;
    align-items:center;
    justify-content:center;
    color:#fff;
    font-size:20px;
}

/* BUTTONS */
.quick-btn{
    border-radius:16px;
    padding:18px;
    font-weight:600;
    background:#fff;
    color:#1e3c72;
    border:1px solid #dee2e6;
    box-shadow:0 10px 20px rgba(0,0,0,.05);
}
.quick-btn:hover{
    background:#1e3c72;
    color:#fff;
}

</style>
</head>

<body class="<?= $STORE_LOCKED ? 'store-locked' : '' ?>">

<?php if ($STORE_LOCKED) { include "menu/subscription/popup_plan.php"; } ?>

<div id="app">
<div class="container py-4">

<!-- HEADER -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-semibold mb-0">Dashboard ร้าน</h4>
        <small class="text-muted">บริหารจัดการธุรกิจซักอบรีด</small>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-warning" onclick="openReportModal()">
            <i class="bi bi-exclamation-triangle"></i>
        </button>
        <a href="index.php?link=profile" class="btn btn-outline-primary">
            <i class="bi bi-person"></i>
        </a>
        <a href="../loginpage/logout.php" class="btn btn-outline-danger">
            <i class="bi bi-box-arrow-right"></i>
        </a>
    </div>
</div>

<!-- PROMO -->
<?php if ($promos): ?>
<div id="promoCarousel" class="carousel slide mb-4" data-bs-ride="carousel">
<div class="carousel-inner rounded-4 shadow-sm">
<?php foreach ($promos as $i=>$p): ?>
<div class="carousel-item <?= $i===0?'active':'' ?>">
<img src="../adminpage/promotion/uploads/<?= htmlspecialchars($p['image']) ?>"
     class="w-100" style="height:300px;object-fit:cover">
</div>
<?php endforeach ?>
</div>
</div>
<?php endif ?>

<!-- STATS -->
<div class="row g-3 mb-4">
<div class="col-md-4">
<div class="card p-3 stat-card">
<div>
<small>รายได้วันนี้</small>
<h4><?= number_format($today_income,2) ?> ฿</h4>
</div>
<div class="stat-icon"><i class="bi bi-cash"></i></div>
</div>
</div>

<div class="col-md-4">
<div class="card p-3 stat-card">
<div>
<small>รายได้เดือนนี้</small>
<h4><?= number_format($month_income,2) ?> ฿</h4>
</div>
<div class="stat-icon"><i class="bi bi-graph-up"></i></div>
</div>
</div>

<div class="col-md-4">
<div class="card p-3 stat-card">
<div>
<small>ลูกค้าทั้งหมด</small>
<h4 style="margin-left: 30px;"> <?= number_format($total_users) ?></h4>
</div>
<div class="stat-icon"><i class="bi bi-people"></i></div>
</div>
</div>
</div>

<!-- QUICK MENU -->
<div class="row g-3 mb-4">
<div class="col-md-2"><a href="index.php?link=orders" class="btn quick-btn w-100">ออเดอร์</a></div>
<div class="col-md-2"><a href="index.php?link=delivery" class="btn quick-btn w-100">จัดส่ง</a></div>
<div class="col-md-2"><a href="index.php?link=revenue" class="btn quick-btn w-100">รายได้</a></div>
<div class="col-md-3"><a href="index.php?link=promotion" class="btn quick-btn w-100">โปรโมชั่น</a></div>
<div class="col-md-3"><a href="index.php?link=management" class="btn quick-btn w-100">พนักงาน</a></div>
</div>

<?php include "body.php"; ?>

</div>
</div>

<!-- REPORT MODAL -->
<div id="reportModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:99999;align-items:center;justify-content:center;">
<div style="background:#fff;width:100%;max-width:480px;border-radius:18px;padding:24px;">
<h5 class="fw-semibold mb-3">🚨 แจ้งปัญหา</h5>
<form method="post" action="report_store_action.php">
<div class="mb-3">
<label class="form-label">หัวข้อ</label>
<input type="text" name="title" class="form-control" required>
</div>
<div class="mb-3">
<label class="form-label">รายละเอียด</label>
<textarea name="message" rows="4" class="form-control" required></textarea>
</div>
<div class="text-end">
<button type="button" class="btn btn-secondary" onclick="closeReportModal()">ยกเลิก</button>
<button class="btn btn-primary">ส่ง</button>
</div>
</form>
</div>
</div>

<script src="../bootstrap/js/bootstrap.bundle.min.js"></script>
<script>
function openReportModal(){ reportModal.style.display='flex'; }
function closeReportModal(){ reportModal.style.display='none'; }
reportModal.addEventListener('click',e=>{
    if(e.target.id==='reportModal') closeReportModal();
});
</script>

</body>
</html>
