<?php
session_start();
require_once "../../../ld_db.php";

/* ========= AUTH ========= */
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['store_owner','staff'])) {
    die('no permission');
}

$user_id  = $_SESSION['user_id'];
$order_id = $_GET['id'] ?? null;
if (!$order_id) die('no order');

/* ========= VERIFY ORDER OWNERSHIP ========= */
$stmt = $pdo->prepare("
    SELECT o.*
    FROM orders o
    JOIN store_staff ss ON ss.store_id = o.store_id
    WHERE o.id = ? AND ss.user_id = ?
");
$stmt->execute([$order_id, $user_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$order) die('not found');

/* ========= CASH PAYMENT ========= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cash_paid'])) {

    $cash_amount = (float)($_POST['cash_amount'] ?? 0);
    if ($cash_amount <= 0) {
        die('จำนวนเงินไม่ถูกต้อง');
    }

    if ($order['payment_status'] !== 'paid') {

        // กันรับเงินซ้ำ
        $chk = $pdo->prepare("
            SELECT id FROM payments
            WHERE order_id = ? AND status = 'confirmed'
        ");
        $chk->execute([$order_id]);

        if (!$chk->fetch()) {

            $pdo->beginTransaction();

            // insert payment (cash only)
            $pdo->prepare("
                INSERT INTO payments
                (id, order_id, amount, provider, status, confirmed_by, confirmed_at)
                VALUES (UUID(), ?, ?, 'cash', 'confirmed', ?, NOW())
            ")->execute([
                $order_id,
                $cash_amount,
                $user_id
            ]);

            // update order payment status
            $pdo->prepare("
                UPDATE orders 
                SET payment_status = 'paid'
                WHERE id = ?
            ")->execute([$order_id]);

            $pdo->commit();
        }
    }

    header("Location: detail.php?id=".$order_id);
    exit;
}

/* ========= STATUS CHANGE ========= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['next_status'])) {

    // HARD GATE: ห้ามไปขั้นส่ง ถ้ายังไม่จ่าย
    if ($order['status'] === 'ready' && $order['payment_status'] !== 'paid') {
        die('ยังไม่ได้ชำระเงิน');
    }

    $next = $_POST['next_status'];

    $pdo->beginTransaction();

    // update order
    $pdo->prepare("
        UPDATE orders SET status = ?
        WHERE id = ?
    ")->execute([$next, $order_id]);

    // update pickup (ถ้ามี)
    $chk = $pdo->prepare("SELECT id FROM pickups WHERE order_id = ?");
    $chk->execute([$order_id]);
    if ($chk->fetch()) {
        $pdo->prepare("
            UPDATE pickups SET status = ?
            WHERE order_id = ?
        ")->execute([$next, $order_id]);
    }

    // log status
    $pdo->prepare("
        INSERT INTO order_status_logs
        (id, order_id, status, changed_by)
        VALUES (UUID(), ?, ?, ?)
    ")->execute([$order_id, $next, $user_id]);

    $pdo->commit();

    header("Location: detail.php?id=".$order_id);
    exit;
}

/* ========= FETCH ORDER ========= */
$stmt = $pdo->prepare("
    SELECT o.*, u.display_name AS customer_name
    FROM orders o
    JOIN users u ON u.id = o.customer_id
    JOIN store_staff ss ON ss.store_id = o.store_id
    WHERE o.id = ? AND ss.user_id = ?
");
$stmt->execute([$order_id, $user_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$order) die('not found');

/* ========= HELPERS ========= */
function label($s){
    return match($s){
        'created'=>'รอรับงาน',
        'picked_up'=>'รับผ้าแล้ว',
        'in_process'=>'กำลังซัก',
        'ready'=>'ซักเสร็จ',
        'out_for_delivery'=>'กำลังส่ง',
        'completed'=>'เสร็จงาน',
        default=>$s
    };
}
function next_status($s){
    return match($s){
        'created'=>'picked_up',
        'picked_up'=>'in_process',
        'in_process'=>'ready',
        'ready'=>'out_for_delivery',
        'out_for_delivery'=>'completed',
        default=>null
    };
}

$need_payment = ($order['status'] === 'ready' && $order['payment_status'] !== 'paid');
?>

<!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<link href="../../../bootstrap/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
<div class="container py-4">

<h4><?= label($order['status']) ?> | <?= htmlspecialchars($order['order_number']) ?></h4>
<p>👤 ลูกค้า: <?= htmlspecialchars($order['customer_name']) ?></p>

<!-- ===== PAYMENT STATUS ===== -->
<div class="card mb-3">
<div class="card-body">

<h6 class="fw-bold">💳 การชำระเงิน</h6>

<?php if ($order['payment_status'] === 'paid'): ?>
    <div class="alert alert-success mb-0">
        ✅ ชำระเงินเรียบร้อยแล้ว
    </div>
<?php else: ?>
    <div class="alert alert-secondary">
        ยังไม่ได้ชำระเงิน
    </div>

    <button type="button"
            class="btn btn-outline-success"
            data-bs-toggle="modal"
            data-bs-target="#cashModal">
        💵 รับเงินสดแล้ว
    </button>
<?php endif; ?>

</div>
</div>

<?php if ($need_payment): ?>
<div class="alert alert-warning">
⚠️ งานนี้ยังไม่ได้ชำระเงิน
</div>
<?php endif; ?>

<?php if ($next = next_status($order['status'])): ?>
<form method="post">
    <input type="hidden" name="next_status" value="<?= $next ?>">
    <button class="btn btn-primary">
        ไปขั้นถัดไป
    </button>
</form>
<?php endif; ?>

<a href="../../index.php?link=orders" class="btn btn-outline-secondary mt-3">
← กลับ
</a>

</div>

<!-- ===== CASH PAYMENT MODAL ===== -->
<div class="modal fade" id="cashModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">

      <form method="post">

        <div class="modal-header">
          <h5 class="modal-title">💵 รับเงินสด</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          <label class="form-label">จำนวนเงินที่รับ (บาท)</label>
          <input type="number"
                 name="cash_amount"
                 class="form-control"
                 step="0.01"
                 min="0"
                 value="<?= htmlspecialchars($order['total_amount']) ?>"
                 required>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
            ยกเลิก
          </button>
          <button type="submit" name="cash_paid" class="btn btn-success">
            ยืนยันรับเงิน
          </button>
        </div>

      </form>

    </div>
  </div>
</div>

<script src="../../../bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
