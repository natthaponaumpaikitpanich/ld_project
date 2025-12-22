<?php
session_start();
require_once '../../db.php'; // ปรับ path ตามโปรเจคคุณ

$store_id = $_SESSION['store_id'] ?? null;
if (!$store_id) {
    die('ไม่พบข้อมูลร้านค้า');
}

$order_id = $_GET['id'] ?? null;
if (!$order_id) {
    echo "<div class='alert alert-danger'>ไม่พบงานซัก</div>";
    exit;
}

/* ---------- FETCH ORDER ---------- */
$stmt = $pdo->prepare("
    SELECT *
    FROM orders
    WHERE id = ? AND store_id = ?
    LIMIT 1
");
$stmt->execute([$order_id, $store_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    echo "<div class='alert alert-danger'>ไม่พบงานซักนี้</div>";
    exit;
}
?>

<div class="container mt-4">

    <h3 class="mb-3">🧺 รายละเอียดงานซัก</h3>

    <div class="card shadow">
        <div class="card-body">

            <p><strong>เลขออเดอร์:</strong> <?= htmlspecialchars($order['order_number']) ?></p>

            <p><strong>ลูกค้า:</strong>
                <?= htmlspecialchars($order['customer_name'] ?? 'ไม่ระบุ') ?>
            </p>

            <p><strong>เบอร์โทร:</strong>
                <?= htmlspecialchars($order['customer_phone'] ?? 'ไม่ระบุ') ?>
            </p>

            <p><strong>รหัส AirTag:</strong>
                <span class="badge bg-dark">
                    <?= htmlspecialchars($order['airtag_code'] ?? '-') ?>
                </span>
            </p>

            <p><strong>สถานะงาน:</strong>
                <?php
                $badge = match($order['status']) {
                    'created' => 'secondary',
                    'picked_up' => 'info',
                    'in_process' => 'warning',
                    'ready' => 'primary',
                    'out_for_delivery' => 'dark',
                    'completed' => 'success',
                    'cancelled' => 'danger',
                    default => 'secondary'
                };
                ?>
                <span class="badge bg-<?= $badge ?>">
                    <?= $order['status'] ?>
                </span>
            </p>

            <p><strong>หมายเหตุ:</strong><br>
                <?= nl2br(htmlspecialchars($order['note'] ?? 'ไม่มีหมายเหตุ')) ?>
            </p>

            <p><strong>วันที่รับผ้า:</strong>
                <?= date('d/m/Y H:i', strtotime($order['created_at'])) ?>
            </p>

            <div class="mt-4">
                <a href="index.php" class="btn btn-secondary">
                    ⬅ กลับหน้ารายการงานซัก
                </a>
            </div>

        </div>
    </div>

</div>