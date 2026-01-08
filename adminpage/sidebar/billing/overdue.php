<?php
require_once "../../ld_db.php";

/* ===== ร้านที่รออนุมัติ ===== */
$sql = "
SELECT
    ss.id,
    ss.store_id,
    ss.monthly_fee,
    ss.slip_image,
    ss.created_at,

    s.name AS store_name,

    bp.name AS plan_name,
    bp.price

FROM store_subscriptions ss
JOIN stores s ON ss.store_id = s.id
JOIN billing_plans bp ON ss.plan_id = bp.id
WHERE ss.status = 'waiting_approve'
ORDER BY ss.created_at DESC
";

$rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="card shadow">
<div class="card-body">

<h5 class="mb-3">🧾 ร้านที่รออนุมัติการชำระเงิน</h5>

<table class="table table-striped align-middle">
<thead>
<tr>
    <th>ร้าน</th>
    <th>แพ็กเกจ</th>
    <th>ราคา</th>
    <th>สลิป</th>
    <th width="180">จัดการ</th>
</tr>
</thead>

<tbody>
<?php if (!$rows): ?>
<tr>
<td colspan="5" class="text-center text-muted">
    🎉 ไม่มีรายการรออนุมัติ
</td>
</tr>
<?php endif; ?>

<?php foreach ($rows as $r): ?>
<tr>
<td><?= htmlspecialchars($r['store_name']) ?></td>

<td><?= htmlspecialchars($r['plan_name']) ?></td>

<td><?= number_format($r['price'],2) ?> ฿</td>

<td>
<?php if ($r['slip_image']): ?>
<a href="../../<?= htmlspecialchars($r['slip_image']) ?>" target="_blank">
    <img src="../../<?= htmlspecialchars($r['slip_image']) ?>"
         style="width:80px;border-radius:6px">
</a>
<?php else: ?>
-
<?php endif; ?>
</td>

<td>
<form method="post" action="billing/approve_action.php" class="d-inline">
    <input type="hidden" name="id" value="<?= $r['id'] ?>">
    <input type="hidden" name="action" value="approve">
    <button class="btn btn-sm btn-success">
        ✅ Approve
    </button>
</form>

<form method="post" action="billing/approve_action.php" class="d-inline"
      onsubmit="return confirm('ยืนยันปฏิเสธ?')">
    <input type="hidden" name="id" value="<?= $r['id'] ?>">
    <input type="hidden" name="action" value="reject">
    <button class="btn btn-sm btn-danger">
        ❌ Reject
    </button>
</form>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>

</div>
</div>
