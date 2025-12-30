<?php
session_start();
require_once "ld_db.php";

/* --- ตรวจ QR --- */
$machine_id = $_GET['machine_id'] ?? null;
if (!$machine_id) {
    die("QR ไม่ถูกต้อง");
}

/* --- ดึงเครื่อง --- */
$stmt = $pdo->prepare("
    SELECT m.*, s.name AS store_name
    FROM machines m
    JOIN stores s ON m.store_id = s.id
    WHERE m.id = ?
");
$stmt->execute([$machine_id]);
$machine = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$machine) {
    die("ไม่พบเครื่อง");
}

/* --- ดึง order ที่ active --- */
$stmt = $pdo->prepare("
    SELECT mo.order_id, o.order_number, o.status
    FROM machine_orders mo
    JOIN orders o ON mo.order_id = o.id
    WHERE mo.machine_id = ?
      AND mo.active = 1
    LIMIT 1
");
$stmt->execute([$machine_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

$role = $_SESSION['role'] ?? 'guest';
$staff_id = $_SESSION['user_id'] ?? null;

/* --- mapping สถานะ --- */
$status_flow = [
    'picked_up' => 'washing',
    'washing'   => 'drying',
    'drying'    => 'folding',
    'folding'   => 'completed'
];

$status_label = [
    'picked_up' => 'รับผ้า',
    'washing'   => 'ซัก',
    'drying'    => 'อบ',
    'folding'   => 'พับ',
    'completed' => 'ส่งสำเร็จ'
];
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>Scan Machine</title>
<link href="/assets/bootstrap/css/bootstrap.min.css" rel="stylesheet">
<style>
body {
    background:#f5f6fa;
}
.card-scan {
    border-radius:16px;
}
.status-pill {
    padding:8px 14px;
    border-radius:20px;
    font-size:14px;
    background:#e9ecef;
    display:inline-block;
}
.action-btn {
    font-size:18px;
    padding:14px;
    border-radius:12px;
}
</style>
</head>

<body>
<div class="container mt-4">

<div class="card card-scan shadow-sm">
<div class="card-body">

<h5 class="fw-bold mb-1">🏪 <?= htmlspecialchars($machine['store_name']) ?></h5>
<div class="text-muted mb-3">
    🧺 เครื่องที่ <?= $machine['machine_no'] ?>
</div>

<?php if (!$order): ?>

    <div class="alert alert-success text-center">
        เครื่องว่าง พร้อมใช้งาน
    </div>

<?php else: ?>

    <?php if ($role === 'staff'): ?>

        <!-- STAFF VIEW -->
        <div class="mb-3">
            <div class="fw-bold">Order</div>
            <div class="fs-5"><?= $order['order_number'] ?></div>
        </div>

        <div class="mb-3">
            <span class="status-pill">
                สถานะปัจจุบัน: <?= $status_label[$order['status']] ?? $order['status'] ?>
            </span>
        </div>

        <?php if ($order['status'] !== 'completed'): ?>

            <?php $next = $status_flow[$order['status']] ?? null; ?>

            <?php if ($next): ?>
            <form method="post" action="staff_scan_action.php">
                <input type="hidden" name="machine_id" value="<?= $machine_id ?>">
                <input type="hidden" name="order_id" value="<?= $order['order_id'] ?>">
                <input type="hidden" name="new_status" value="<?= $next ?>">

                <button class="btn btn-success w-100 action-btn">
                    ➜ เปลี่ยนเป็น “<?= $status_label[$next] ?>”
                </button>
            </form>
            <?php endif; ?>

        <?php else: ?>
            <div class="alert alert-info text-center">
                งานเสร็จสมบูรณ์แล้ว
            </div>
        <?php endif; ?>

    <?php else: ?>

        <!-- CUSTOMER VIEW -->
        <div class="text-center">
            <h5 class="fw-bold mb-2">ออเดอร์ของคุณ</h5>
            <div class="fs-5 mb-2"><?= $order['order_number'] ?></div>
            <div class="status-pill">
                <?= $status_label[$order['status']] ?? $order['status'] ?>
            </div>

            <?php if ($order['status'] === 'completed'): ?>
                <div class="alert alert-success mt-3">
                    ผ้าของคุณเสร็จเรียบร้อยแล้ว 🎉
                </div>
            <?php endif; ?>
        </div>

    <?php endif; ?>

<?php endif; ?>

</div>
</div>

</div>
</body>
</html>
