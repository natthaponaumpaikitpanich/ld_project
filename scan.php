<?php
session_start();
require_once "ld_db.php";

/* =======================
   1) รับค่าจาก QR
======================= */
$raw = $_GET['machine_id'] ?? $_GET['code'] ?? null;

if (!$raw) {
    die("QR ไม่ถูกต้อง");
}

/* QR ฝัง URL */
if (filter_var($raw, FILTER_VALIDATE_URL)) {
    $parts = parse_url($raw);
    parse_str($parts['query'] ?? '', $query);
    $machine_id = $query['id'] ?? $query['machine_id'] ?? null;
} else {
    $machine_id = $raw;
}

if (!$machine_id) {
    die("QR ไม่ถูกต้อง (ไม่พบ machine id)");
}

/* =======================
   2) ดึงเครื่อง
======================= */
$stmt = $pdo->prepare("
    SELECT m.*, s.name AS store_name
    FROM machines m
    JOIN stores s ON m.store_id = s.id
    WHERE m.id = ?
");
$stmt->execute([$machine_id]);
$machine = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$machine) {
    die("ไม่พบเครื่องในระบบ");
}

/* =======================
   3) ดึง order + pickup
======================= */
$staff_id = $_SESSION['user_id'] ?? null;

$stmt = $pdo->prepare("
    SELECT 
        mo.order_id,
        o.order_number,
        o.status AS order_status,
        p.id AS pickup_id
    FROM machine_orders mo
    JOIN orders o ON mo.order_id = o.id
    LEFT JOIN pickups p 
        ON p.order_id = o.id
        AND p.assigned_to = ?
    WHERE mo.machine_id = ?
      AND mo.active = 1
    LIMIT 1
");
$stmt->execute([$staff_id, $machine_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

/* =======================
   4) role
======================= */
$role = $_SESSION['role'] ?? 'guest';

/* =======================
   5) AUTO REDIRECT
======================= */
if ($role === 'staff' && !$order) {
    header("Location: staffpage/menu/scan/staff_bind_machine.php?machine_id=".$machine_id);
    exit;
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>Scan QR</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<link href="bootstrap/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

<style>
body {
    background: #f4f6f9;
    font-family: 'Kanit', sans-serif;
}
.machine-card {
    border-radius: 16px;
}
.status-badge {
    font-size: 1rem;
}
</style>
</head>
<body>

<div class="container mt-4">

    <!-- เครื่อง -->
    <div class="card machine-card shadow-sm mb-3">
        <div class="card-body">
            <h5 class="mb-1">🏪 <?= htmlspecialchars($machine['store_name']) ?></h5>
            <div class="text-muted">
                เครื่องหมายเลข <b><?= htmlspecialchars($machine['machine_no']) ?></b>
            </div>
        </div>
    </div>

    <!-- ถ้าเครื่องว่าง -->
    <?php if (!$order): ?>
        <div class="alert alert-success text-center">
            <h5 class="mb-1">✅ เครื่องว่าง</h5>
            <div>พร้อมใช้งาน</div>
        </div>

        <?php if ($role !== 'staff'): ?>
            <div class="alert alert-info text-center">
                กรุณาติดต่อพนักงานเพื่อเริ่มใช้งาน
            </div>
        <?php endif; ?>

    <?php else: ?>

        <!-- มี order -->
        <div class="card shadow-sm">
            <div class="card-body">

                <h5 class="mb-2">🧾 Order <?= htmlspecialchars($order['order_number']) ?></h5>

                <div class="mb-3">
                    สถานะปัจจุบัน:
                    <span class="badge bg-info status-badge">
                        <?= htmlspecialchars($order['order_status']) ?>
                    </span>
                </div>

                <?php if ($role === 'staff' && $order): ?>
<form method="post" action="staffpage/menu/task/task_update_status.php">

    <input type="hidden" name="order_id" value="<?= $order['order_id'] ?>">
    <input type="hidden" name="pickup_id" value="<?= $order['pickup_id'] ?>">
    <input type="hidden" name="machine_id" value="<?= $machine_id ?>">

    <label class="form-label">อัปเดตสถานะ</label>
    <select name="next_status" class="form-select mb-3" required>
        <option value="">-- เลือก --</option>
        <option value="picked_up">รับผ้า</option>
        <option value="in_process">ซัก</option>
        <option value="ready">อบ / พับ</option>
        <option value="out_for_delivery">กำลังส่ง</option>
        <option value="completed">ส่งสำเร็จ</option>
    </select>

    <button class="btn btn-success w-100">
        🔄 อัปเดตสถานะ
    </button>
</form>
<?php endif; ?>

                    <!-- CUSTOMER -->
                    <?php if ($order['order_status'] === 'completed'): ?>
                        <div class="alert alert-success mt-3">
                            🎉 งานเสร็จแล้ว ขอบคุณที่ใช้บริการ
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning mt-3">
                            ⏳ กำลังดำเนินการ
                        </div>
                    <?php endif; ?>
                <?php endif; ?>

            </div>
        </div>


</div>

<script src="assets/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
