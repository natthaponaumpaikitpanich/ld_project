<?php
session_start();
require_once "../../../ld_db.php";
require_once "../../assets/boostap.php";

// เช็คสิทธิ์
if (!isset($_SESSION['user_id'])) {
    die("กรุณาเข้าสู่ระบบ");
}

$pickup_id = $_GET['id'] ?? null;
if (!$pickup_id) {
    die("ไม่พบรหัสงานจัดส่ง");
}

/* -------------------------
   ดึงข้อมูลงานจัดส่ง (PDO)
-------------------------- */
$sql = "
SELECT 
    p.id AS pickup_id,
    p.status AS pickup_status,
    p.pickup_address,
    p.scheduled_at,
    p.completed_at,

    o.id AS order_id,
    o.order_number,
    o.status AS order_status,
    o.notes,
    o.created_at,

    u.display_name AS customer_name,
    u.phone AS customer_phone,

    s.name AS store_name
FROM pickups p
LEFT JOIN orders o ON p.order_id = o.id
LEFT JOIN users u ON o.customer_id = u.id
LEFT JOIN stores s ON o.store_id = s.id
WHERE p.id = ?
LIMIT 1
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$pickup_id]);
$data = $stmt->fetch();

if (!$data) {
    die("ไม่พบข้อมูลงานจัดส่ง");
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>รายละเอียดการจัดส่ง</title>
    <link href="../../../bootstrap/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-4">

    <a href="../../index.php?link=delivery" class="btn btn-secondary mb-3">
        ← กลับไปหน้ารายการจัดส่ง
    </a>

    <div class="card shadow-sm mb-3">
        <div class="card-header bg-primary text-white">
            🚚 รายละเอียดงานจัดส่ง
        </div>
        <div class="card-body">

            <h5 class="mb-3">📦 ข้อมูล Order</h5>
            <p><strong>Order No:</strong> <?= htmlspecialchars($data['order_number']) ?></p>
            <p><strong>สถานะ Order:</strong>
                <span class="badge bg-info"><?= $data['order_status'] ?></span>
            </p>
            <p><strong>วันที่สร้าง:</strong>
                <?= date('d/m/Y H:i', strtotime($data['created_at'])) ?>
            </p>

            <hr>

            <h5 class="mb-3">👤 ลูกค้า</h5>
            <p><strong>ชื่อ:</strong> <?= $data['customer_name'] ?? '-' ?></p>
            <p><strong>เบอร์:</strong> <?= $data['customer_phone'] ?? '-' ?></p>

            <hr>

            <h5 class="mb-3">📍 ข้อมูลจัดส่ง</h5>
            <p><strong>ที่อยู่รับผ้า:</strong><br>
                <?= nl2br(htmlspecialchars($data['pickup_address'])) ?>
            </p>

            <p><strong>เวลานัดรับ:</strong>
                <?= $data['scheduled_at']
                    ? date('d/m/Y H:i', strtotime($data['scheduled_at']))
                    : '-' ?>
            </p>

            <p><strong>สถานะจัดส่ง:</strong>
                <?php
                $badge = match($data['pickup_status']) {
                    'scheduled' => 'secondary',
                    'in_progress' => 'warning',
                    'completed' => 'success',
                    'cancelled' => 'danger',
                    default => 'secondary'
                };
                ?>
                <span class="badge bg-<?= $badge ?>">
                    <?= $data['pickup_status'] ?>
                </span>
            </p>

            <?php if ($data['completed_at']): ?>
                <p><strong>เสร็จสิ้นเมื่อ:</strong>
                    <?= date('d/m/Y H:i', strtotime($data['completed_at'])) ?>
                </p>
            <?php endif; ?>

        </div>
    </div>

</div>

<script src="../bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
