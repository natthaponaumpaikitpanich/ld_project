<?php
session_start();
require_once "../../ld_db.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    die('no permission');
}

$order_id = $_GET['order_id'] ?? null;
if (!$order_id) {
    header("Location: my_orders.php");
    exit;
}

/* ---------- ดึง order + QR ร้าน ---------- */
$stmt = $pdo->prepare("
    SELECT 
        o.id,
        o.order_number,
        o.total_amount,
        s.name AS store_name,
        s.promptpay_qr
    FROM orders o
    JOIN stores s ON s.id = o.store_id
    WHERE o.id = ? AND o.customer_id = ?
");
$stmt->execute([$order_id, $_SESSION['user_id']]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) die('not found');

/* ---------- upload slip ---------- */
if ($_SERVER['REQUEST_METHOD']==='POST') {

    if (empty($_FILES['slip']['name'])) {
        die('กรุณาแนบสลิป');
    }

    $allowed = ['image/jpeg','image/png'];
    if (!in_array($_FILES['slip']['type'],$allowed)) {
        die('สลิปต้องเป็น JPG / PNG');
    }

    $ext = pathinfo($_FILES['slip']['name'],PATHINFO_EXTENSION);
    $filename = uniqid().'_slip.'.$ext;

    $dir = $_SERVER['DOCUMENT_ROOT'].'/ld_project/uploads/slips/';
    if (!is_dir($dir)) mkdir($dir,0777,true);

    move_uploaded_file($_FILES['slip']['tmp_name'],$dir.$filename);
    $slip_path = 'uploads/slips/'.$filename;

    /* ---------- insert payment ---------- */
    $stmt = $pdo->prepare("
        INSERT INTO payments
        (id, order_id, amount, method, status, note, created_at)
        VALUES (UUID(), ?, ?, 'promptpay', 'pending', ?, NOW())
    ");
    $stmt->execute([
        $order['id'],
        $order['total_amount'],
        $slip_path
    ]);

    header("Location: payments_pending.php");
    exit;
}
?>
<!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<link href="../../bootstrap/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">

<h4 class="fw-bold mb-3">💳 ชำระเงิน</h4>

<div class="card shadow-sm mb-3">
<div class="card-body text-center">

<h5><?= htmlspecialchars($order['store_name']) ?></h5>
<p class="text-muted">Order: <?= $order['order_number'] ?></p>

<h3 class="text-success mb-3">
    <?= number_format($order['total_amount'],2) ?> บาท
</h3>

<?php if ($order['promptpay_qr']): ?>
<img src="../../<?= htmlspecialchars($order['promptpay_qr']) ?>"
     class="img-fluid mb-3"
     style="max-width:250px">
<?php else: ?>
<div class="alert alert-danger">
ร้านยังไม่ได้ตั้งค่า QR PromptPay
</div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data">
    <label class="form-label fw-semibold">
        📄 แนบสลิปการโอน
    </label>
    <input type="file"
           name="slip"
           class="form-control mb-3"
           accept="image/*"
           required>

    <button class="btn btn-primary w-100">
        ส่งหลักฐานการชำระเงิน
    </button>
</form>

</div>
</div>

</div>
</body>
</html>
