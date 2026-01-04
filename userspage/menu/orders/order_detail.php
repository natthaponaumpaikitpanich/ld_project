<?php
session_start();
require_once "../../../ld_db.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: ../loginpage/login.php");
    exit;
}

$customer_id = $_SESSION['user_id'];
$order_id = $_GET['id'] ?? null;

if (!$order_id) {
    header("Location: my_orders.php");
    exit;
}

/* ---------- ดึง order ---------- */
$stmt = $pdo->prepare("
    SELECT o.*, s.name AS store_name, s.address AS store_address
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

/* ---------- ดึง timeline ---------- */
$stmt = $pdo->prepare("
    SELECT status, created_at
    FROM order_status_logs
    WHERE order_id = :order_id
    ORDER BY created_at ASC
");
$stmt->execute([':order_id' => $order_id]);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ---------- payment ---------- */
$stmt = $pdo->prepare("
    SELECT *
    FROM payments
    WHERE order_id = :order_id
    ORDER BY created_at DESC
    LIMIT 1
");
$stmt->execute([':order_id' => $order_id]);
$payment = $stmt->fetch(PDO::FETCH_ASSOC);

/* ---------- helper ---------- */
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
 <link href="../../../bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="icon" href="../../../image/3.jpg">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

<style>
.hero {
    background: linear-gradient(135deg,#0d6efd,#6ea8fe);
    color:#fff;
    border-radius:20px;
}
.timeline {
    position:relative;
    margin-left:20px;
}
.timeline::before {
    content:'';
    position:absolute;
    left:8px;
    top:0;
    bottom:0;
    width:2px;
    background:#dee2e6;
}
.timeline-item {
    position:relative;
    padding-left:40px;
    margin-bottom:18px;
}
.timeline-dot {
    position:absolute;
    left:0;
    top:0;
    width:18px;
    height:18px;
    border-radius:50%;
    background:#0d6efd;
}
.inactive .timeline-dot {
    background:#ced4da;
}
.card {
    border-radius:20px;
}
</style>
</head>

<body class="bg-light">

<div class="container py-4">

    <!-- HERO -->
    <div class="hero p-4 mb-4">
        <h4 class="fw-bold mb-1"><?= status_text($order['status']) ?></h4>
        <div class="small opacity-75">
            เลขออเดอร์ <?= $order['order_number'] ?>
        </div>
    </div>

    <!-- STORE INFO -->
    <div class="card mb-4">
        <div class="card-body">
            <h6 class="fw-bold mb-1">🏪 ร้านที่ดูแล</h6>
            <div><?= htmlspecialchars($order['store_name']) ?></div>
            <div class="text-muted small"><?= htmlspecialchars($order['store_address']) ?></div>
        </div>
    </div>

    <!-- TIMELINE -->
    <div class="card mb-4">
        <div class="card-body">
            <h6 class="fw-bold mb-3">🧺 สถานะการซัก</h6>

            <div class="timeline">
                <?php
                $done = array_column($logs,'status');
                $steps = ['created','picked_up','in_process','ready','out_for_delivery','completed'];
                ?>
                <?php foreach ($steps as $step): ?>
                    <div class="timeline-item <?= in_array($step,$done)?'':'inactive' ?>">
                        <div class="timeline-dot"></div>
                        <i class="bi <?= status_icon($step) ?>"></i>
                        <?= status_text($step) ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- PAYMENT -->
    <div class="card mb-4">
        <div class="card-body">
            <h6 class="fw-bold mb-2">💳 การชำระเงิน</h6>

            <?php if ($order['payment_status'] === 'paid'): ?>
                <span class="badge bg-success">ชำระเงินแล้ว</span>
            <?php elseif ($order['payment_status'] === 'pending'): ?>
                <span class="badge bg-warning text-dark">รอชำระเงิน</span>
            <?php else: ?>
                <span class="badge bg-secondary">ยังไม่พร้อมชำระ</span>
            <?php endif; ?>

            <?php if ($order['total_amount'] > 0): ?>
                <div class="mt-2">
                    ยอดรวม:
                    <strong><?= number_format($order['total_amount'],2) ?> บาท</strong>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <a href="../../index.php" class="btn btn-outline-secondary rounded-pill">
        ← กลับไปหน้ารายการ
    </a>

</div>

</body>
</html>
