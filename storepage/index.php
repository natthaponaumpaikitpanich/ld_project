<?php
session_start();
require_once "../ld_db.php";

/* ================= STORE ================= */
$store_id = $_SESSION['store_id'] ?? null;
if (!$store_id) {
    die("ไม่พบข้อมูลร้าน");
}

/* ================= SUBSCRIPTION STATUS ================= */
$sub_status = 'pending_payment';

$stmt = $pdo->prepare("
    SELECT status
    FROM store_subscriptions
    WHERE store_id = ?
    ORDER BY created_at DESC
    LIMIT 1
");
$stmt->execute([$store_id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if ($row) {
    $sub_status = $row['status'];
}

$STORE_LOCKED = ($sub_status !== 'active');



include 'assets/style.php';
include 'assets/sum.php';

/* ================= PROMOTIONS ================= */
$promos = $pdo->query("
    SELECT title, image
    FROM promotions
    WHERE status = 'active'
      AND start_date <= CURDATE()
      AND end_date >= CURDATE()
    ORDER BY created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

/* ================= INCOME ================= */
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

/* ================= TOTAL USERS ================= */
$total_users = (int)$pdo->query("
    SELECT COUNT(*) 
    FROM users
    WHERE role NOT IN ('platform_admin','store_owner')
")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>ร้านซักอบรีด</title>
<link href="../bootstrap/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.css" rel="stylesheet">

<style>
body.store-locked #app {
    filter: blur(6px);
    pointer-events: none;
    user-select: none;
}

#payment-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,.6);
    z-index: 99999;
    display: flex;
    align-items: center;
    justify-content: center;
}

#payment-box {
    background:#fff;
    border-radius:14px;
    width:420px;
    padding:24px;
}
</style>
</head>

<body class="<?= $STORE_LOCKED ? 'store-locked' : '' ?>">

<!-- ===== APP ===== -->
<div id="app" class="container py-4">

    <!-- HEADER -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4>Dashboard ของเจ้าของร้าน</h4>
            <small class="text-muted">บริหารจัดการธุรกิจซักรีด</small>
        </div>
        <div>
            <a href="../loginpage/logout.php" class="btn btn-danger">ออกจากระบบ</a>
        </div>
    </div>

    <!-- SUMMARY -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card p-3">
                <small>รายได้วันนี้</small>
                <h4><?= number_format($today_income,2) ?> ฿</h4>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card p-3">
                <small>รายได้เดือนนี้</small>
                <h4><?= number_format($month_income,2) ?> ฿</h4>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card p-3">
                <small>ผู้ใช้ทั้งหมด</small>
                <h4><?= number_format($total_users) ?> คน</h4>
            </div>
        </div>
    </div>

    <?php include 'body.php'; ?>
</div>

<!-- ===== PAYMENT OVERLAY ===== -->
<?php if ($STORE_LOCKED): ?>
<div id="payment-overlay">
    <div id="payment-box">
        <h5 class="fw-bold mb-2">🔒 ร้านยังไม่เปิดใช้งาน</h5>
        <p class="text-muted mb-3">
            กรุณาชำระค่าบริการเพื่อเริ่มใช้งานระบบ
        </p>

        <img src="../assets/qr_admin.png" class="img-fluid mb-3">

        <form id="slipForm" enctype="multipart/form-data">
            <input type="file" name="slip" class="form-control mb-2" required>
            <button class="btn btn-primary w-100">ส่งสลิป</button>
        </form>

        <div id="waitMsg" class="text-center text-muted mt-3 d-none">
            ⏳ รอแอดมินตรวจสอบ...
        </div>
    </div>
</div>
<?php endif; ?>

<script src="../bootstrap/js/bootstrap.bundle.min.js"></script>

<script>
<?php if ($STORE_LOCKED): ?>
document.getElementById('slipForm').onsubmit = async e => {
    e.preventDefault();
    document.getElementById('waitMsg').classList.remove('d-none');
};

/* realtime check */
setInterval(async () => {
    const res = await fetch('ajax/check_subscription.php');
    const json = await res.json();
    if (json.status === 'active') location.reload();
}, 5000);
<?php endif; ?>
</script>

</body>
</html>
