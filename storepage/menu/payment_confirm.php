<?php
session_start();
require_once "../../ld_db.php";

/* ================= AUTH ================= */
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['store_owner','staff'])) {
    die("ไม่มีสิทธิ์เข้าถึง");
}

$user_id = $_SESSION['user_id'];
$payment_id = $_GET['id'] ?? null;

if (!$payment_id) {
    die("ไม่พบข้อมูลการชำระเงิน");
}

/* ================= FETCH PAYMENT ================= */
$stmt = $pdo->prepare("
    SELECT 
        p.*,
        o.order_number,
        o.id AS order_id,
        u.display_name AS customer_name,
        u.phone AS customer_phone
    FROM payments p
    JOIN orders o ON p.order_id = o.id
    JOIN users u ON o.customer_id = u.id
    WHERE p.id = ?
");
$stmt->execute([$payment_id]);
$payment = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$payment) {
    die("ไม่พบข้อมูลการชำระเงิน");
}

/* ================= CONFIRM ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm'])) {

    $pdo->beginTransaction();

    $pdo->prepare("
        UPDATE payments
        SET status='confirmed',
            confirmed_by=?,
            confirmed_at=NOW()
        WHERE id=?
    ")->execute([$user_id, $payment_id]);

    $pdo->prepare("
        UPDATE orders
        SET payment_status='paid'
        WHERE id=?
    ")->execute([$payment['order_id']]);

    $pdo->commit();

    header("Location: payments_pending.php");
    exit;
}

/* ================= REJECT ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reject'])) {

    $pdo->prepare("
        UPDATE payments
        SET status='rejected',
            confirmed_by=?,
            confirmed_at=NOW()
        WHERE id=?
    ")->execute([$user_id, $payment_id]);

    header("Location: payments_pending.php");
    exit;
}

/* ================= SLIP PATH ================= */
$slip_path = null;
if (!empty($payment['note'])) {
    $slip_path = "/ld_project/" . ltrim($payment['note'], '/');
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>ตรวจสอบการชำระเงิน</title>
<link href="../../bootstrap/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
<div class="container py-4">

<h4 class="fw-bold mb-3">🧾 ตรวจสอบการชำระเงิน</h4>

<div class="card shadow-sm">
<div class="card-body">

<p><strong>Order:</strong> <?= htmlspecialchars($payment['order_number']) ?></p>
<p><strong>ลูกค้า:</strong> <?= htmlspecialchars($payment['customer_name']) ?> (<?= htmlspecialchars($payment['customer_phone']) ?>)</p>
<p><strong>ยอดเงิน:</strong> <?= number_format($payment['amount'],2) ?> บาท</p>

<hr>

<h6 class="fw-bold mb-2">📸 สลิปการโอน</h6>

<?php if ($slip_path && file_exists($_SERVER['DOCUMENT_ROOT'].$slip_path)): ?>
    <a href="<?= htmlspecialchars($slip_path) ?>" target="_blank">
        <img src="<?= htmlspecialchars($slip_path) ?>"
             class="img-fluid rounded border"
             style="max-height:420px">
    </a>
<?php else: ?>
    <div class="alert alert-secondary">
        ❌ ไม่พบไฟล์สลิป
    </div>
<?php endif; ?>

<div class="d-flex gap-2 mt-4">
    <form method="post">
        <button name="confirm" class="btn btn-success w-100">
            ✅ ยืนยันการชำระเงิน
        </button>
    </form>

    <form method="post">
        <button name="reject" class="btn btn-danger w-100"
                onclick="return confirm('ปฏิเสธการชำระเงินนี้?')">
            ❌ ปฏิเสธ
        </button>
    </form>
</div>

</div>
</div>

<a href="payments_pending.php" class="btn btn-outline-secondary mt-3">
    ← กลับ
</a>

</div>
</body>
</html>
