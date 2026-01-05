<?php
session_start();
require_once "../../ld_db.php";

/* ================= AUTH ================= */
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['store_owner','staff'])) {
    die("ไม่มีสิทธิ์เข้าถึง");
}

$user_id = $_SESSION['user_id'];

/* ================= GET STORE ================= */
$stmt = $pdo->prepare("
    SELECT store_id
    FROM store_staff
    WHERE user_id = ?
    LIMIT 1
");
$stmt->execute([$user_id]);
$store = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$store) {
    die("ไม่พบร้านของคุณ");
}

$store_id = $store['store_id'];

/* ================= FETCH PENDING PAYMENTS ================= */
$stmt = $pdo->prepare("
    SELECT 
        p.id AS payment_id,
        p.amount,
        p.method,
        p.status,
        p.created_at,

        o.id AS order_id,
        o.order_number,
        o.total_amount,

        u.display_name AS customer_name,
        u.phone AS customer_phone

    FROM payments p
    JOIN orders o ON p.order_id = o.id
    JOIN users u ON o.customer_id = u.id

    WHERE p.status = 'pending'
      AND o.store_id = :store_id

    ORDER BY p.created_at ASC
");
$stmt->execute([':store_id' => $store_id]);
$payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>รอตรวจสอบการชำระเงิน</title>
<link href="../../assets/bootstrap/css/bootstrap.min.css" rel="stylesheet">
<style>
body{background:#f4f6f9}
.card{border-radius:16px}
</style>
</head>

<body>
<div class="container py-4">

<h4 class="fw-bold mb-4">💳 การชำระเงินที่รอตรวจสอบ</h4>

<?php if (!$payments): ?>
    <div class="alert alert-secondary text-center">
        ยังไม่มีสลิปที่รอตรวจสอบ
    </div>
<?php else: ?>

<?php foreach ($payments as $p): ?>
<div class="card shadow-sm mb-3">
    <div class="card-body">

        <div class="d-flex justify-content-between">
            <div>
                <div class="fw-bold">
                    🧾 Order : <?= htmlspecialchars($p['order_number']) ?>
                </div>
                <div class="text-muted small">
                    ลูกค้า: <?= htmlspecialchars($p['customer_name']) ?>
                    | โทร: <?= htmlspecialchars($p['customer_phone']) ?>
                </div>
            </div>
            <div class="text-end">
                <div class="fw-bold text-primary">
                    <?= number_format($p['amount'],2) ?> บาท
                </div>
                <div class="badge bg-warning text-dark">
                    รอตรวจสอบ
                </div>
            </div>
        </div>

        <hr>

        <div class="d-flex justify-content-between align-items-center">
            <div class="small text-muted">
                ส่งเมื่อ <?= date('d/m/Y H:i', strtotime($p['created_at'])) ?>
            </div>

            <div class="d-flex gap-2">
                <a href="payment_confirm.php?id=<?= $p['payment_id'] ?>"
                   class="btn btn-success btn-sm">
                    ✅ ตรวจสอบ / ยืนยัน
                </a>
            </div>
        </div>

    </div>
</div>
<?php endforeach; ?>

<?php endif; ?>

<a href="../index.php?link=orders" class="btn btn-outline-secondary mt-3">
    ← กลับหน้าร้าน
</a>

</div>
</body>
</html>
