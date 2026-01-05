<?php
session_start();
require_once "../../../ld_db.php";

/* ========= AUTH ========= */
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['store_owner','staff'])) {
    die('no permission');
}

$user_id = $_SESSION['user_id'];

/* ========= FETCH PAYMENTS ========= */
$stmt = $pdo->prepare("
    SELECT 
    p.id AS payment_id,
    p.order_id,
    p.amount,
    p.method,
    p.note,
    p.created_at,
    o.order_number,
    u.display_name AS customer_name
    FROM payments p
    JOIN orders o ON o.id = p.order_id
    JOIN users u ON u.id = o.customer_id
    JOIN store_staff ss ON ss.store_id = o.store_id
    WHERE ss.user_id = ?
      AND p.status = 'pending'
    ORDER BY p.created_at ASC
");
$stmt->execute([$user_id]);
$payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>รอยืนยันการชำระเงิน</title>
<link href="../../../bootstrap/css/bootstrap.min.css" rel="stylesheet">
<style>
body{background:#f4f6f9}
</style>
</head>

<body>
<div class="container py-4">

<h4 class="fw-bold mb-3">💳 รอยืนยันการชำระเงิน</h4>

<?php if (!$payments): ?>
<div class="alert alert-success">
    🎉 ไม่มีรายการรอยืนยัน
</div>
<?php endif; ?>

<?php foreach ($payments as $p): ?>
<div class="card shadow-sm mb-3">
<div class="card-body">

    <div class="d-flex justify-content-between align-items-center mb-2">
        <div>
            <b><?= htmlspecialchars($p['customer_name']) ?></b><br>
            <small class="text-muted">
                Order: <?= htmlspecialchars($p['order_number']) ?>
            </small>
        </div>
        <span class="badge bg-warning text-dark">
            <?= number_format($p['amount'],2) ?> บาท
        </span>
    </div>

    <div class="small text-muted mb-3">
        วิธีชำระ: <?= strtoupper($p['method']) ?> |
        แจ้งเมื่อ <?= date('d/m/Y H:i', strtotime($p['created_at'])) ?>
    </div>
<?php if (!empty($p['note'])): ?>
    <div class="mb-3">
        <a href="../../../<?= htmlspecialchars($p['note']) ?>"
           target="_blank"
           class="btn btn-sm btn-outline-primary">
            📄 ดูสลิปการโอนเงิน
        </a>
    </div>
<?php endif; ?>

    <form method="post"
          action="payment_confirm.php"
          class="d-flex gap-2">

        <input type="hidden" name="payment_id" value="<?= $p['payment_id'] ?>">

        <button name="action"
                value="confirm"
                class="btn btn-success w-100">
            ✅ ยืนยันเงินเข้า
        </button>

        <button name="action"
                value="reject"
                class="btn btn-outline-danger w-100"
                onclick="return confirm('ปฏิเสธการชำระเงินนี้?')">
            ❌ ปฏิเสธ
        </button>
    </form>

</div>
</div>
<?php endforeach; ?>

<a href="../../index.php" class="btn btn-secondary mt-2">← กลับ</a>

</div>
</body>
</html>
