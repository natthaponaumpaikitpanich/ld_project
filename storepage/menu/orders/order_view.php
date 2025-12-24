<?php
<<<<<<< HEAD
session_start();// ตรวจสิทธิ์
include_once "../../../ld_db.php"; // PDO
include_once "../../assets/boostap.php"; // PDO

$store_id = $_SESSION['store_id'] ?? null;
$order_id = $_GET['id'] ?? null;

if (!$store_id || !$order_id) {
    die("ข้อมูลไม่ครบ");
}

/* ---------- ดึงข้อมูล Order ---------- */
=======
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
>>>>>>> b8413d33f720bfbfdad726b30edfa9749767ce2e
$stmt = $pdo->prepare("
    SELECT *
    FROM orders
    WHERE id = ? AND store_id = ?
<<<<<<< HEAD
=======
    LIMIT 1
>>>>>>> b8413d33f720bfbfdad726b30edfa9749767ce2e
");
$stmt->execute([$order_id, $store_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
<<<<<<< HEAD
    die("ไม่พบออเดอร์");
}

/* ---------- เช็คว่ามีงานจัดส่งแล้วหรือยัง ---------- */
$stmt = $pdo->prepare("
    SELECT *
    FROM pickups
    WHERE order_id = ?
");
$stmt->execute([$order_id]);
$pickup = $stmt->fetch(PDO::FETCH_ASSOC);
=======
    echo "<div class='alert alert-danger'>ไม่พบงานซักนี้</div>";
    exit;
}
>>>>>>> b8413d33f720bfbfdad726b30edfa9749767ce2e
?>

<div class="container mt-4">

<<<<<<< HEAD
    <h4>🧺 รายละเอียดออเดอร์</h4>

    <div class="card mb-3">
        <div class="card-body">
            <p><b>Order No:</b> <?= htmlspecialchars($order['order_number']) ?></p>
            <p><b>สถานะ:</b> <?= $order['status'] ?></p>
            <p><b>หมายเหตุ:</b> <?= $order['notes'] ?: '-' ?></p>
            <p><b>สร้างเมื่อ:</b> <?= $order['created_at'] ?></p>
        </div>
    </div>

    <!-- ===== ส่วนจัดส่ง ===== -->
    <h5>🚚 การจัดส่ง</h5>

    <?php if ($pickup): ?>
        <div class="alert alert-info">
            มีงานจัดส่งแล้ว <br>
            สถานะ: <b><?= $pickup['status'] ?></b>
        </div>
    <?php else: ?>
        <form method="post" action="delivery_create.php">
            <input type="hidden" name="order_id" value="<?= $order_id ?>">

            <div class="mb-3">
                <label class="form-label">ที่อยู่รับผ้า</label>
                <textarea name="pickup_address" class="form-control" required></textarea>
            </div>

            <div class="mb-3">
                <label class="form-label">เวลานัดรับ</label>
                <input type="datetime-local" name="scheduled_at" class="form-control">
            </div>

            <button class="btn btn-primary">
                ➕ สร้างงานจัดส่ง
            </button>
        </form>
    <?php endif; ?>

    <a href="../../index.php?link=orders" class="btn btn-secondary mt-3">
        ← กลับหน้า Orders
    </a>

=======
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

>>>>>>> b8413d33f720bfbfdad726b30edfa9749767ce2e
</div>