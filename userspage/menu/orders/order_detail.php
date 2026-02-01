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

// แก้ไข Helper สีสถานะให้ดู Soft ขึ้น
function getStatusTheme($s) {
    return match($s) {
        'ready', 'completed' => ['color' => '#00b894', 'bg' => '#e8fdf5'], 
        'in_process', 'out_for_delivery' => ['color' => '#0984e3', 'bg' => '#e1f5fe'], 
        default => ['color' => '#636e72', 'bg' => '#f5f6fa'] 
    };
}
$theme = getStatusTheme($order['status']);
?>

<style>
    :root {
        --bg-main: #f4f7fe;
        --primary-blue: #0984e3;
        --glass-white: rgba(255, 255, 255, 0.98);
    }

    body {
        background-color: var(--bg-main);
        font-family: 'Kanit', sans-serif;
    }

    .hero-banner {
        background: linear-gradient(135deg, #0984e3 0%, #4facfe 100%);
        border-radius: 0 0 35px 35px;
        padding: 60px 20px;
        color: white;
        text-align: center;
        margin-bottom: -50px;
    }

    .custom-card {
        background: var(--glass-white);
        border: none;
        border-radius: 25px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.03);
        margin-bottom: 20px;
    }

    /* Timeline Section */
    .timeline-item {
        display: flex;
        gap: 15px;
        padding-bottom: 20px;
        position: relative;
    }

    .timeline-item:not(:last-child)::after {
        content: "";
        position: absolute;
        left: 17px;
        top: 35px;
        bottom: 0;
        width: 2px;
        background: #ebf0f5;
    }

    .timeline-icon-box {
        width: 34px;
        height: 34px;
        border-radius: 50%;
        background: #f1f2f6;
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 2;
    }

    .timeline-item.done .timeline-icon-box {
        background: var(--primary-blue);
        color: white;
    }

    .timeline-item.active .timeline-icon-box {
        background: #fff;
        border: 2px solid var(--primary-blue);
        color: var(--primary-blue);
        animation: pulse-blue 2s infinite;
    }

    /* Payment Summary Section */
    .bill-box {
        border: 2px dashed #d1d8e0;
        border-radius: 20px;
        padding: 20px;
        background: #fcfdff;
    }

    .price-total {
        font-size: 1.8rem;
        font-weight: 700;
        color: var(--primary-blue);
    }

    .payment-badge {
        background: #fff9e6;
        color: #d39e00;
        padding: 5px 15px;
        border-radius: 10px;
        font-size: 0.85rem;
        font-weight: 500;
    }

    @keyframes pulse-blue {
        0% { box-shadow: 0 0 0 0px rgba(9, 132, 227, 0.4); }
        70% { box-shadow: 0 0 0 8px rgba(9, 132, 227, 0); }
        100% { box-shadow: 0 0 0 0px rgba(9, 132, 227, 0); }
    }
</style>

<div class="hero-banner">
    <div class="container text-center">
        <h3 class="fw-bold mb-1">รายละเอียดออเดอร์</h3>
        <p class="opacity-75">#<?= htmlspecialchars($order['order_number']) ?></p>
    </div>
</div>

<div class="container pb-5" style="position: relative; z-index: 5;">
    
    <div class="card custom-card">
        <div class="card-body p-4 text-center">
            <div class="d-inline-flex p-3 rounded-circle mb-3" style="background: <?= $theme['bg'] ?>;">
                <i class="bi <?= status_icon($order['status']) ?> fs-2" style="color: <?= $theme['color'] ?>;"></i>
            </div>
            <h4 class="fw-bold mb-1" style="color: <?= $theme['color'] ?>;"><?= status_text($order['status']) ?></h4>
            <p class="text-muted small">อัปเดตล่าสุด: <?= date('d/m/Y H:i') ?></p>
        </div>
    </div>

    <div class="card custom-card">
        <div class="card-body p-4">
            <h6 class="fw-bold mb-3"><i class="bi bi-shop me-2"></i>ร้านค้าที่ให้บริการ</h6>
            <div class="d-flex align-items-start">
                <div class="bg-primary bg-opacity-10 p-2 rounded-3 me-3 text-primary">
                    <i class="bi bi-geo-alt fs-5"></i>
                </div>
                <div>
                    <div class="fw-bold text-dark"><?= htmlspecialchars($order['store_name']) ?></div>
                    <div class="text-muted small"><?= htmlspecialchars($order['store_address']) ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="card custom-card">
        <div class="card-body p-4">
            <h6 class="fw-bold mb-4">สถานะแบบละเอียด</h6>
            <div class="timeline-container">
                <?php
                $done_logs = array_column($logs, 'status');
                $steps = ['created', 'picked_up', 'in_process', 'ready', 'out_for_delivery', 'completed'];
                ?>
                <?php foreach ($steps as $step): 
                    $isDone = in_array($step, $done_logs);
                    $isActive = ($order['status'] === $step);
                ?>
                    <div class="timeline-item <?= $isDone ? 'done' : '' ?> <?= $isActive ? 'active' : '' ?>">
                        <div class="timeline-icon-box">
                            <i class="bi <?= status_icon($step) ?> small"></i>
                        </div>
                        <div class="timeline-content">
                            <span class="fw-bold <?= $isActive ? 'text-primary' : ($isDone ? 'text-dark' : 'text-muted') ?>">
                                <?= status_text($step) ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <a href="../../index.php" class="btn btn-outline-primary border-2 rounded-pill w-100 py-3 fw-bold">
        <i class="bi bi-arrow-left me-2"></i> กลับหน้ารายการของฉัน
    </a>

</div>