<?php
session_start();
require_once "../../../ld_db.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: ../../loginpage/login.php");
    exit;
}

$customer_id = $_SESSION['user_id'];
$order_id = $_GET['id'] ?? null;

if (!$order_id) {
    header("Location: my_orders.php");
    exit;
}

/* ---------- ORDER ---------- */
$stmt = $pdo->prepare("
    SELECT 
        o.*,
        s.name AS store_name,
        s.address AS store_address
    FROM orders o
    JOIN stores s ON o.store_id = s.id
    WHERE o.id = :order_id
      AND o.customer_id = :customer_id
");
$stmt->execute([
    ':order_id' => $order_id,
    ':customer_id' => $customer_id
]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    header("Location: my_orders.php");
    exit;
}

/* ---------- TIMELINE ---------- */
$stmt = $pdo->prepare("
    SELECT status, created_at
    FROM order_status_logs
    WHERE order_id = :order_id
    ORDER BY created_at ASC
");
$stmt->execute([':order_id' => $order_id]);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ---------- HELPERS ---------- */
function status_text($s) {
    return match($s) {
        'created' => 'รอร้านรับงาน',
        'picked_up' => 'รับผ้าแล้ว',
        'in_process' => 'กำลังซัก',
        'ready' => 'ซักเสร็จ',
        'out_for_delivery' => 'กำลังนำส่ง',
        'completed' => 'ส่งคืนแล้ว',
        default => $s
    };
}
function status_icon($s) {
    return match($s) {
        'created' => 'bi-receipt',
        'picked_up' => 'bi-box-seam',
        'in_process' => 'bi-arrow-repeat',
        'ready' => 'bi-check-circle',
        'out_for_delivery' => 'bi-truck',
        'completed' => 'bi-house-check',
        default => 'bi-clock'
    };
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>ติดตามคำสั่งซัก</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<link href="../../../bootstrap/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<style>
body{
    font-family:'Kanit',sans-serif;
    background:#f6f7fb;
}

/* HERO */
.hero{
    background:linear-gradient(135deg,#1e3c72,#2a5298);
    color:#fff;
    border-radius:22px;
}

/* CARD */
.card{
    border-radius:22px;
    border:none;
    box-shadow:0 12px 30px rgba(0,0,0,.08);
}

/* TIMELINE */
.timeline{
    position:relative;
    padding-left:40px;
}
.timeline::before{
    content:'';
    position:absolute;
    left:18px;
    top:0;
    bottom:0;
    width:3px;
    background:#dee2e6;
    border-radius:2px;
}
.timeline-item{
    position:relative;
    margin-bottom:22px;
    display:flex;
    align-items:flex-start;
    gap:14px;
}
.timeline-item:last-child{
    margin-bottom:0;
}
.timeline-dot{
    position:absolute;
    left:10px;
    top:4px;
    width:18px;
    height:18px;
    border-radius:50%;
    background:#adb5bd;
}
.timeline-item.done .timeline-dot{
    background:#2a5298;
}
.timeline-icon{
    font-size:20px;
    margin-top:2px;
}
.timeline-item.done .timeline-icon{
    color:#2a5298;
}
.timeline-text{
    line-height:1.4;
}
.timeline-item.inactive{
    opacity:.5;
}
</style>
</head>

<body>

<div class="container py-4">

<!-- HERO -->
<div class="hero p-4 mb-4">
    <h5 class="fw-semibold mb-1"><?= status_text($order['status']) ?></h5>
    <small class="opacity-75">
        เลขออเดอร์ <?= htmlspecialchars($order['order_number']) ?>
    </small>
</div>

<!-- STORE -->
<div class="card mb-4">
    <div class="card-body">
        <h6 class="fw-semibold mb-1">🏪 ร้านที่ดูแล</h6>
        <div><?= htmlspecialchars($order['store_name']) ?></div>
        <small class="text-muted"><?= htmlspecialchars($order['store_address']) ?></small>
    </div>
</div>

<!-- TIMELINE -->
<div class="card mb-4">
    <div class="card-body">
        <h6 class="fw-semibold mb-3">🧺 สถานะการซัก</h6>

        <div class="timeline">
        <?php
        $done = array_column($logs,'status');
        $steps = ['created','picked_up','in_process','ready','out_for_delivery','completed'];
        ?>
        <?php foreach ($steps as $step): 
            $isDone = in_array($step,$done);
        ?>
            <div class="timeline-item <?= $isDone?'done':'inactive' ?>">
                <span class="timeline-dot"></span>
                <i class="bi <?= status_icon($step) ?> timeline-icon"></i>
                <div class="timeline-text">
                    <?= status_text($step) ?>
                </div>
            </div>
        <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- PAYMENT -->
<div class="card mb-4">
    <div class="card-body">
        <h6 class="fw-semibold mb-2">💳 การชำระเงิน</h6>

        <?php if ($order['payment_status'] === 'paid'): ?>
            <span class="badge bg-success rounded-pill">ชำระเงินแล้ว</span>
        <?php elseif ($order['payment_status'] === 'pending'): ?>
            <span class="badge bg-warning text-dark rounded-pill">รอตรวจสอบการชำระเงิน</span>
        <?php else: ?>
            <span class="badge bg-secondary rounded-pill">ยังไม่ถึงขั้นชำระเงิน</span>
        <?php endif; ?>

        <?php if ($order['total_amount'] > 0): ?>
            <div class="mt-2">
                ยอดรวม:
                <strong><?= number_format($order['total_amount'],2) ?> บาท</strong>
            </div>
        <?php endif; ?>
    </div>
</div>

<a href="../../index.php" class="btn btn-outline-secondary rounded-pill w-100">
← กลับไปหน้ารายการ
</a>

</div>

<!-- PAYMENT MODAL -->
<div class="modal fade" id="paymentModal" tabindex="-1">
<div class="modal-dialog modal-dialog-centered">
<div class="modal-content rounded-4">

<div class="modal-header bg-danger text-white">
<h5 class="modal-title">⚠️ ต้องชำระเงิน</h5>
</div>

<div class="modal-body text-center">
<p>
งานซักของคุณเสร็จเรียบร้อยแล้ว<br>
กรุณาชำระเงินก่อนรับผ้า
</p>
<a href="../payment_promptpay.php?order_id=<?= $order['id'] ?>"
   class="btn btn-primary w-100">
💳 ไปหน้าชำระเงิน
</a>
</div>

</div>
</div>
</div>

<?php if ($order['status']==='ready' && $order['payment_status']!=='paid'): ?>
<script>
document.addEventListener("DOMContentLoaded",()=>{
    const modal=new bootstrap.Modal(
        document.getElementById('paymentModal'),
        {backdrop:'static',keyboard:false}
    );
    modal.show();
});
</script>
<?php endif; ?>

<script src="../../../bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
