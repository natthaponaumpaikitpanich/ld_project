<?php


if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: ../loginpage/login.php");
    exit;
}

$customer_id = $_SESSION['user_id'];

/* ---------- ดึง orders ---------- */
$stmt = $pdo->prepare("
    SELECT o.*, s.name AS store_name
    FROM orders o
    JOIN stores s ON o.store_id = s.id
    WHERE o.customer_id = :customer_id
    ORDER BY o.created_at DESC
");
$stmt->execute([':customer_id' => $customer_id]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ---------- function แปลง status ---------- */
function status_label($status) {
    return match($status) {
        'created' => 'รอร้านรับงาน',
        'picked_up' => 'รับผ้าแล้ว',
        'in_process' => 'กำลังซัก',
        'ready' => 'ซักเสร็จ',
        'out_for_delivery' => 'กำลังนำส่ง',
        'completed' => 'ส่งคืนแล้ว',
        default => $status
    };
}

function status_icon($status) {
    return match($status) {
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
    <title>คำสั่งซักของฉัน</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        .order-card {
            border-radius: 20px;
            box-shadow: 0 8px 24px rgba(0,0,0,.08);
        }

        .timeline {
            position: relative;
            margin-left: 20px;
        }

        .timeline::before {
            content: '';
            position: absolute;
            left: 8px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #e9ecef;
        }

        .timeline-item {
            position: relative;
            padding-left: 40px;
            margin-bottom: 20px;
        }

        .timeline-dot {
            position: absolute;
            left: 0;
            top: 0;
            width: 18px;
            height: 18px;
            background: #0d6efd;
            border-radius: 50%;
        }

        .timeline-item.inactive .timeline-dot {
            background: #ced4da;
        }

        .status-badge {
            font-size: .9rem;
            padding: 6px 14px;
            border-radius: 50px;
        }
    </style>
</head>

<body class="bg-light">

<div class="container py-5">
    <h3 class="fw-bold mb-4">🧺 คำสั่งซักของฉัน</h3>

    <?php foreach ($orders as $order): ?>

        <?php
        // ดึง timeline
        $stmt = $pdo->prepare("
            SELECT status, created_at
            FROM order_status_logs
            WHERE order_id = :order_id
            ORDER BY created_at ASC
        ");
        $stmt->execute([':order_id' => $order['id']]);
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        ?>

        <div class="card order-card mb-4">
            <div class="card-body p-4">

                <!-- Header -->
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h5 class="fw-bold mb-1"><?= htmlspecialchars($order['store_name']) ?></h5>
                        <div class="text-muted small">
                            เลขออเดอร์: <?= $order['order_number'] ?>
                        </div>
                    </div>
                    <span class="badge bg-primary status-badge">
                        <?= status_label($order['status']) ?>
                    </span>
                </div>

                <!-- Timeline -->
                <div class="timeline mt-4">

                    <?php
                    $shown = array_column($logs, 'status');
                    $all_status = [
                        'created',
                        'picked_up',
                        'in_process',
                        'ready',
                        'out_for_delivery',
                        'completed'
                    ];
                    ?>

                    <?php foreach ($all_status as $status): ?>
                        <div class="timeline-item <?= in_array($status, $shown) ? '' : 'inactive' ?>">
                            <div class="timeline-dot"></div>
                            <div>
                                <i class="bi <?= status_icon($status) ?>"></i>
                                <?= status_label($status) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
<div class="text-end mt-3">
    <a href="menu/orders/order_detail.php?id=<?= $order['id'] ?>"
       class="btn btn-outline-primary rounded-pill">
        ดูรายละเอียด
    </a>
</div>
                </div>

            </div>
        </div>

    <?php endforeach; ?>

</div>

</body>
</html>
