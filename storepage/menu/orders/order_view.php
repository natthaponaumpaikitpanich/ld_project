
<?php
session_start();
require_once "../../assets/boostap.php";
require_once "../../../ld_db.php";

$store_id = $_SESSION['store_id'] ?? null;
$order_id = $_GET['id'] ?? null;

if (!$store_id || !$order_id) {
    die("ข้อมูลไม่ครบ");
}

/* ---------- ดึงข้อมูล Order ---------- */
$stmt = $pdo->prepare("
    SELECT *
    FROM orders
    WHERE id = ? AND store_id = ?
");
$stmt->execute([$order_id, $store_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
    

<div class="container mt-4">

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
            <?php if ($order['payment_status'] !== 'paid'): ?>
    <button 
        class="btn btn-success"
        data-bs-toggle="modal"
        data-bs-target="#paymentModal">
        💰 บันทึกการชำระเงิน
    </button>
    
<?php else: ?>
    <span class="badge bg-success">ชำระเงินแล้ว</span>
<?php endif; ?>

        </div>
        
    <?php else: ?>
        <div>
        </div>
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

    <a href="../../index.php?link=orders" class="btn btn-warning mt-3">
        ← กลับหน้า Orders
    </a>

</div>
<div class="modal fade" id="paymentModal" tabindex="-1">
  <div class="modal-dialog">
    <form method="post" action="payment_store.php" class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title">บันทึกการชำระเงิน</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">

        <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
        <input type="hidden" name="amount" value="<?= $order['total_amount'] ?>">

        <div class="mb-3">
          <label class="form-label">วิธีชำระเงิน</label>
          <select name="provider" class="form-select" required>
            <option value="cash">เงินสด</option>
            <option value="transfer">โอนเงิน</option>
            <option value="promptpay">PromptPay</option>
          </select>
        </div>

        <div class="mb-3">
          <label class="form-label">หมายเหตุ</label>
          <input type="text" name="note" class="form-control">
        </div>

      </div>

      <div class="modal-footer">
        <button class="btn btn-success">บันทึกการชำระเงิน</button>
      </div>

    </form>
  </div>
</div>
</body>
</html>