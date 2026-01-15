<?php
session_start();
require_once "../../../ld_db.php";


if (!isset($_SESSION['user_id'], $_SESSION['store_id'])) {
    die("กรุณาเข้าสู่ระบบ");
}

$store_id  = $_SESSION['store_id'];
$pickup_id = $_GET['id'] ?? null;

if (!$pickup_id) {
    die("ไม่พบรหัสงานจัดส่ง");
}

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
JOIN orders o ON p.order_id = o.id
LEFT JOIN users u ON o.customer_id = u.id
JOIN stores s ON o.store_id = s.id
WHERE p.id = ?
  AND o.store_id = ?
LIMIT 1
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$pickup_id, $store_id]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$data) {
    die("ไม่พบข้อมูลงานจัดส่ง หรือคุณไม่มีสิทธิ์เข้าถึง");
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>รายละเอียดการจัดส่ง</title>
    <link rel="icon" href="../../../image/3.jpg">
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

            <h5>📦 ข้อมูล Order</h5>
            <p><strong>Order No:</strong> <?= htmlspecialchars($data['order_number']) ?></p>
            <p><strong>สถานะ Order:</strong>
                <span class="badge bg-info"><?= $data['order_status'] ?></span>
            </p>

            <hr>

            <h5>👤 ลูกค้า</h5>
            <p><?= htmlspecialchars($data['customer_name'] ?? '-') ?></p>
            <p><?= htmlspecialchars($data['customer_phone'] ?? '-') ?></p>

            <hr>

            <h5>📍 ข้อมูลจัดส่ง</h5>
            <p><?= nl2br(htmlspecialchars($data['pickup_address'])) ?></p>
            <p>
                <strong>เวลานัดรับ:</strong>
                <?= $data['scheduled_at']
                    ? date('d/m/Y H:i', strtotime($data['scheduled_at']))
                    : '-' ?>
            </p>

            <p>
                <strong>สถานะจัดส่ง:</strong>
                <span class="badge bg-secondary"><?= $data['pickup_status'] ?></span>
            </p>

        </div>
    </div>

</div>
</body>
</html>
