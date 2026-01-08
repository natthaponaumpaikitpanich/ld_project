<?php
session_start();

/* ===== CHECK ADMIN ===== */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'platform_admin') {
    die('no permission');
}

/* ===== LOAD WAITING SUBSCRIPTIONS ===== */
$sql = "
SELECT
    ss.id,
    ss.store_id,
    ss.monthly_fee,
    ss.slip_image,
    ss.created_at,
    s.name AS store_name,
    bp.name AS plan_name
FROM store_subscriptions ss
JOIN stores s ON ss.store_id = s.id
JOIN billing_plans bp ON ss.plan_id = bp.id
WHERE ss.status = 'waiting_approve'
ORDER BY ss.created_at ASC
";

$rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<title>ตรวจสลิปชำระเงิน</title>
<link href="../../bootstrap/css/bootstrap.min.css" rel="stylesheet">
</head>

<body style="margin-left:260px;">
<div class="container mt-4">

<h3 class="fw-bold mb-3">🧾 ตรวจสอบสลิปชำระเงิน</h3>

<?php if (empty($rows)): ?>
    <div class="alert alert-success">
        🎉 ไม่มีรายการรออนุมัติ
    </div>
<?php endif; ?>

<?php foreach ($rows as $r): ?>
<div class="card shadow-sm mb-4">
<div class="card-body">

<div class="row">
    <div class="col-md-4 text-center">
        <?php if ($r['slip_image']): ?>
            <img src="../../uploads/slips/<?= htmlspecialchars($r['slip_image']) ?>"
                 class="img-fluid rounded">
        <?php else: ?>
            <div class="text-muted">ไม่มีรูปสลิป</div>
        <?php endif; ?>
    </div>

    <div class="col-md-8">
        <h5 class="fw-bold"><?= htmlspecialchars($r['store_name']) ?></h5>
        <p class="mb-1">แพ็กเกจ: <?= htmlspecialchars($r['plan_name']) ?></p>
        <p class="mb-1">ค่าบริการ: <?= number_format($r['monthly_fee'],2) ?> บาท</p>
        <p class="mb-2 text-muted">
            ส่งเมื่อ <?= date('d/m/Y H:i', strtotime($r['created_at'])) ?>
        </p>

        <form method="post" action="approve_action.php"
              onsubmit="return confirm('ยืนยันการดำเนินการ?')"
              class="d-flex gap-2">

            <input type="hidden" name="subscription_id" value="<?= $r['id'] ?>">

            <button name="action" value="approve"
                    class="btn btn-success">
                ✅ อนุมัติ
            </button>

            <button name="action" value="reject"
                    class="btn btn-danger">
                ❌ ปฏิเสธ
            </button>

        </form>
    </div>
</div>

</div>
</div>
<?php endforeach; ?>

</div>
</body>
</html>
