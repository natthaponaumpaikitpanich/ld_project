<?php
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: ../../../loginpage/login.php");
    exit;
}

$customer_id = $_SESSION['user_id'];

/* ---------- orders ---------- */
$stmt = $pdo->prepare("
    SELECT o.*, s.name AS store_name
    FROM orders o
    JOIN stores s ON o.store_id = s.id
    WHERE o.customer_id = :customer_id
      AND o.status != 'completed'
    ORDER BY o.created_at DESC
");
$stmt->execute([':customer_id' => $customer_id]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ---------- logs ---------- */
$orderIds = array_column($orders, 'id');
$logsByOrder = [];

if ($orderIds) {
    $in  = str_repeat('?,', count($orderIds) - 1) . '?';
    $stmt = $pdo->prepare("
        SELECT order_id, status
        FROM order_status_logs
        WHERE order_id IN ($in)
        ORDER BY created_at ASC
    ");
    $stmt->execute($orderIds);

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $log) {
        $logsByOrder[$log['order_id']][] = $log['status'];
    }
}

/* helpers */
function status_label($status) {
    return match($status) {
        'created'=>'รอร้านรับงาน',
        'picked_up'=>'รับผ้าแล้ว',
        'in_process'=>'กำลังซัก',
        'ready'=>'ซักเสร็จ',
        'out_for_delivery'=>'กำลังนำส่ง',
        'completed'=>'ส่งคืนแล้ว',
        default=>$status
    };
}
function status_icon($status) {
    return match($status) {
        'created'=>'bi-receipt',
        'picked_up'=>'bi-box-seam',
        'in_process'=>'bi-arrow-repeat',
        'ready'=>'bi-check-circle',
        'out_for_delivery'=>'bi-truck',
        'completed'=>'bi-house-check',
        default=>'bi-clock'
    };
}
function status_color($status) {
    return match($status) {
        'ready' => '#00b894', // เขียวสดใสเมื่อเสร็จ
        'out_for_delivery' => '#0984e3', // ฟ้าเข้มตอนส่ง
        default => '#54a0ff' // ฟ้าปกติ
    };
}
?>

<style>
    :root {
        --sky-blue: #f0f7ff;
        --primary-blue: #54a0ff;
        --glass-white: rgba(255, 255, 255, 0.9);
        --text-dark: #2d3436;
        --text-muted: #b2bec3;
    }

    body {
        background-color: var(--sky-blue);
        font-family: 'Kanit', sans-serif;
    }

    /* หัวข้อหน้า */
    .page-header {
        padding: 20px 0;
        color: var(--text-dark);
    }

    /* การ์ด Order แบบใหม่ */
    .order-card {
        border: none;
        border-radius: 25px;
        background: var(--glass-white);
        box-shadow: 0 10px 20px rgba(84, 160, 255, 0.08);
        transition: transform 0.3s ease;
        margin-bottom: 25px;
        overflow: hidden;
    }

    .order-card:hover {
        transform: translateY(-5px);
    }

    /* แถบสีด้านข้างสถานะ */
    .status-indicator {
        width: 6px;
        height: 50px;
        border-radius: 0 10px 10px 0;
        background: var(--primary-blue);
        position: absolute;
        left: 0;
        top: 25px;
    }

    /* Timeline แนวนอน (Stepper) */
    .stepper-wrapper {
        display: flex;
        justify-content: space-between;
        margin-top: 25px;
        position: relative;
    }

    .stepper-item {
        position: relative;
        display: flex;
        flex-direction: column;
        align-items: center;
        flex: 1;
        z-index: 2;
    }

    /* เส้นเชื่อม Timeline */
    .stepper-item::before {
        position: absolute;
        content: "";
        border-bottom: 2px dashed #e0e0e0;
        width: 100%;
        top: 15px;
        left: -50%;
        z-index: 1;
    }

    .stepper-item:first-child::before { content: none; }

    .step-counter {
        position: relative;
        z-index: 5;
        display: flex;
        justify-content: center;
        align-items: center;
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: #fff;
        border: 2px solid #e0e0e0;
        margin-bottom: 6px;
        transition: all 0.3s ease;
        font-size: 14px;
        color: var(--text-muted);
    }

    .active .step-counter {
        background-color: var(--primary-blue);
        color: white;
        border-color: var(--primary-blue);
        box-shadow: 0 0 10px rgba(84, 160, 255, 0.4);
    }

    .active .step-name {
        color: var(--primary-blue);
        font-weight: 500;
    }

    .step-name {
        font-size: 10px;
        color: var(--text-muted);
        text-align: center;
    }

    /* ปุ่มรายละเอียด */
    .btn-detail {
        background: #fff;
        color: var(--primary-blue);
        border: 1.5px solid var(--primary-blue);
        border-radius: 12px;
        padding: 8px 20px;
        font-size: 0.9rem;
        transition: 0.3s;
    }

    .btn-detail:hover {
        background: var(--primary-blue);
        color: #fff;
    }

    /* สัญลักษณ์ "กำลังทำงาน" */
    .pulse-blue {
        animation: pulse-blue-animation 2s infinite;
    }

    @keyframes pulse-blue-animation {
        0% { box-shadow: 0 0 0 0px rgba(84, 160, 255, 0.4); }
        70% { box-shadow: 0 0 0 10px rgba(84, 160, 255, 0); }
        100% { box-shadow: 0 0 0 0px rgba(84, 160, 255, 0); }
    }
</style>

<div class="container py-4">
    <div class="page-header d-flex align-items-center">
        <div class="flex-grow-1">
            <h4 class="fw-bold mb-1">ติดตามสถานะซักผ้า</h4>
            <p class="text-muted small">เราจะดูแลผ้าของคุณให้ดีที่สุด 😊</p>
        </div>
        <div class="bg-white p-2 rounded-circle shadow-sm">
            <i class="bi bi-funnel text-primary"></i>
        </div>
    </div>

    <?php if (empty($orders)): ?>
        <div class="text-center py-5">
            <img src="https://cdn-icons-png.flaticon.com/512/4076/4076432.png" style="width: 120px; opacity: 0.5;">
            <p class="mt-3 text-muted">ยังไม่มีรายการสั่งซักในขณะนี้</p>
        </div>
    <?php endif; ?>

    <?php foreach ($orders as $order): ?>
    <?php
        $shown = $logsByOrder[$order['id']] ?? [];
        $all_status = [
            ['id' => 'created', 'label' => 'จองคิว', 'icon' => 'bi-receipt'],
            ['id' => 'picked_up', 'label' => 'รับผ้า', 'icon' => 'bi-box-seam'],
            ['id' => 'in_process', 'label' => 'กำลังซัก', 'icon' => 'bi-arrow-repeat'],
            ['id' => 'ready', 'label' => 'เสร็จแล้ว', 'icon' => 'bi-check-circle'],
            ['id' => 'out_for_delivery', 'label' => 'กำลังส่ง', 'icon' => 'bi-truck']
        ];
    ?>

    <div class="card order-card">
        <div class="status-indicator" style="background: <?= status_color($order['status']) ?>"></div>
        <div class="card-body p-4">
            
            <div class="d-flex justify-content-between align-items-start mb-4">
                <div>
                    <h6 class="fw-bold mb-1" style="color: var(--text-dark);"><?= htmlspecialchars($order['store_name']) ?></h6>
                    <span class="badge" style="background: var(--sky-blue); color: var(--primary-blue); font-weight: 400;">
                        #<?= $order['order_number'] ?>
                    </span>
                </div>
                <div class="text-end">
                    <div class="small text-muted mb-1">สถานะปัจจุบัน</div>
                    <span class="fw-bold" style="color: <?= status_color($order['status']) ?>">
                        <?= status_label($order['status']) ?>
                    </span>
                </div>
            </div>

            <div class="stepper-wrapper">
                <?php foreach ($all_status as $st): 
                    $is_done = in_array($st['id'], $shown);
                    $is_current = ($order['status'] == $st['id']);
                ?>
                <div class="stepper-item <?= $is_done || $is_current ? 'active' : '' ?>">
                    <div class="step-counter <?= $is_current ? 'pulse-blue' : '' ?>">
                        <i class="bi <?= $st['icon'] ?>"></i>
                    </div>
                    <div class="step-name"><?= $st['label'] ?></div>
                </div>
                <?php endforeach; ?>
            </div>

            <hr class="my-4" style="border-top: 1px dashed #eee;">

            <div class="d-flex justify-content-between align-items-center">
                <div class="text-muted small">
                    <i class="bi bi-clock-history me-1"></i> อัปเดตล่าสุด: <?= date('H:i') ?> น.
                </div>
                <a href="menu/orders/order_detail.php?id=<?= $order['id'] ?>" class="btn btn-detail">
                    รายละเอียด <i class="bi bi-chevron-right ms-1"></i>
                </a>
            </div>

        </div>
    </div>
    <?php endforeach; ?>
</div>