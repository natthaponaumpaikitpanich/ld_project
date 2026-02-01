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
    <title>รายละเอียดการจัดส่ง | <?= htmlspecialchars($data['order_number']) ?></title>
    <link rel="icon" href="../../../image/3.jpg">
    <link href="../../../bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600&display=swap" rel="stylesheet">

    <style>
        :root {
            --bg-blue: #f0f7ff;
            --main-blue: #007bff;
            --dark-blue: #0056b3;
            --soft-blue: #e1efff;
        }

        body {
            background-color: var(--bg-blue);
            font-family: 'Kanit', sans-serif;
            color: #334155;
        }

        .header-section {
            background: linear-gradient(135deg, var(--main-blue), var(--dark-blue));
            padding: 40px 0 80px 0;
            color: white;
            border-radius: 0 0 40px 40px;
            margin-bottom: -60px;
        }

        .back-link {
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: 0.3s;
            font-size: 0.9rem;
        }
        .back-link:hover { color: white; }

        .info-card {
            background: white;
            border: none;
            border-radius: 25px;
            box-shadow: 0 10px 25px rgba(0, 123, 255, 0.08);
            overflow: hidden;
            margin-bottom: 20px;
        }

        .section-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--dark-blue);
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .info-label {
            font-size: 0.85rem;
            color: #64748b;
            margin-bottom: 2px;
        }

        .info-value {
            font-weight: 500;
            color: #1e293b;
            margin-bottom: 15px;
        }

        .status-badge {
            padding: 8px 16px;
            border-radius: 50px;
            font-weight: 500;
            font-size: 0.85rem;
        }

        .action-btn {
            border-radius: 15px;
            padding: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: 0.3s;
            font-weight: 500;
        }

        .btn-call {
            background-color: #28a745;
            color: white;
            border: none;
        }
        .btn-call:hover { background-color: #218838; color: white; transform: translateY(-2px); }

        .btn-nav {
            background-color: var(--main-blue);
            color: white;
            border: none;
        }
        .btn-nav:hover { background-color: var(--dark-blue); color: white; transform: translateY(-2px); }

        .address-box {
            background: var(--soft-blue);
            padding: 15px;
            border-radius: 15px;
            border-left: 5px solid var(--main-blue);
        }

        /* ลูกเล่น Hover Card */
        .info-card {
            transition: transform 0.3s ease;
        }
        .info-card:hover {
            transform: translateY(-5px);
        }
    </style>
</head>

<body>

<div class="header-section">
    <div class="container text-center">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <a href="../../index.php?link=delivery" class="back-link">
                <i class="bi bi-chevron-left"></i> กลับ
            </a>
            <span class="badge bg-white text-primary rounded-pill px-3">Delivery Detail</span>
        </div>
        <h2 class="fw-bold mb-1">📦 #<?= htmlspecialchars($data['order_number']) ?></h2>
        <p class="opacity-75 mb-0 small">ร้าน: <?= htmlspecialchars($data['store_name']) ?></p>
    </div>
</div>

<div class="container pb-5">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            
            <div class="row g-2 mb-3">
                <div class="col-6">
                    <a href="tel:<?= $data['customer_phone'] ?>" class="btn action-btn btn-call shadow-sm w-100">
                        <i class="bi bi-telephone-fill"></i> โทรหาลูกค้า
                    </a>
                </div>
                <div class="col-6">
                    <button onclick="window.open('https:www.google.com/maps/search/?api=1&query=<?= urlencode($data['pickup_address']) ?>')" class="btn action-btn btn-nav shadow-sm w-100">
                        <i class="bi bi-geo-alt-fill"></i> นำทางไป
                    </button>
                </div>
            </div>

            <div class="card info-card p-4">
                <div class="section-title">
                    <i class="bi bi-info-circle-fill"></i> ข้อมูล Order
                </div>
                <div class="row">
                    <div class="col-6">
                        <div class="info-label">สถานะออเดอร์</div>
                        <div class="info-value">
                            <span class="badge bg-info text-dark status-badge"><?= $data['order_status'] ?></span>
                        </div>
                    </div>
                    <div class="col-6 text-end">
                        <div class="info-label">วันที่สร้าง</div>
                        <div class="info-value small"><?= date('d/m/Y', strtotime($data['created_at'])) ?></div>
                    </div>
                </div>

                <hr class="my-3 opacity-10">

                <div class="section-title">
                    <i class="bi bi-person-fill"></i> ข้อมูลลูกค้า
                </div>
                <div class="fw-bold fs-5 text-primary mb-1"><?= htmlspecialchars($data['customer_name'] ?? '-') ?></div>
                <div class="text-muted"><i class="bi bi-phone me-1"></i> <?= htmlspecialchars($data['customer_phone'] ?? '-') ?></div>
            </div>

            <div class="card info-card p-4">
                <div class="section-title">
                    <i class="bi bi-truck"></i> ข้อมูลจัดส่ง
                </div>
                
                <div class="info-label">ที่อยู่จัดส่ง</div>
                <div class="address-box mb-3">
                    <?= nl2br(htmlspecialchars($data['pickup_address'])) ?>
                </div>

                <div class="row">
                    <div class="col-6">
                        <div class="info-label">เวลานัดรับ/ส่ง</div>
                        <div class="info-value">
                            <i class="bi bi-calendar-event me-1"></i>
                            <?= $data['scheduled_at'] ? date('d/m/Y H:i', strtotime($data['scheduled_at'])) : '-' ?>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="info-label">สถานะจัดส่ง</div>
                        <div class="info-value text-end">
                            <span class="badge bg-secondary status-badge"><?= $data['pickup_status'] ?></span>
                        </div>
                    </div>
                </div>

                <?php if ($data['notes']): ?>
                <div class="mt-2">
                    <div class="info-label">หมายเหตุจากลูกค้า</div>
                    <div class="p-3 bg-light rounded-3 small">
                        <i class="bi bi-chat-left-text me-1 text-muted"></i> <?= nl2br(htmlspecialchars($data['notes'])) ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>

        </div>
    </div>
</div>

<script src="../../../bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>