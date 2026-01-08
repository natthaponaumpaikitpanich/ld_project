<?php
$sql = "
SELECT
    ss.id AS sub_id,
    s.id AS store_id,
    s.name AS store_name,
    ss.plan,
    ss.monthly_fee,
    ss.end_date,
    ss.status,
    ss.slip,
    DATEDIFF(CURDATE(), ss.end_date) AS overdue_days
FROM store_subscriptions ss
JOIN stores s ON ss.store_id = s.id
WHERE ss.status IN ('pending_payment','pending_approve')
ORDER BY ss.created_at DESC
";

$rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="card shadow">
<div class="card-body">

<h5 class="mb-3">🏪 ร้านที่รอชำระ / รออนุมัติ</h5>

<table class="table table-striped align-middle">
<thead>
<tr>
    <th>ร้าน</th>
    <th>แพ็กเกจ</th>
    <th>ราคา</th>
    <th>สถานะ</th>
    <th>สลิป</th>
    <th>จัดการ</th>
</tr>
</thead>
<tbody>

<?php if (!$rows): ?>
<tr>
    <td colspan="6" class="text-center text-muted">
        ไม่มีรายการ
    </td>
</tr>
<?php endif; ?>

<?php foreach ($rows as $r): ?>
<tr>
<td><?= htmlspecialchars($r['store_name']) ?></td>
<td><?= htmlspecialchars($r['plan']) ?></td>
<td><?= number_format($r['monthly_fee'],2) ?> ฿</td>

<td>
<?php if ($r['status']==='pending_payment'): ?>
    <span class="badge bg-warning">รอชำระเงิน</span>
<?php elseif ($r['status']==='pending_approve'): ?>
    <span class="badge bg-info">รออนุมัติ</span>
<?php endif; ?>
</td>

<td>
<?php if ($r['slip']): ?>
    <a href="../uploads/slips/<?= htmlspecialchars($r['slip']) ?>"
       target="_blank"
       class="btn btn-sm btn-outline-primary">
       ดูสลิป
    </a>
<?php else: ?>
    -
<?php endif; ?>
</td>

<td>
<?php if ($r['status']==='pending_approve'): ?>
    <button class="btn btn-success btn-sm"
        onclick="approveSub('<?= $r['sub_id'] ?>')">
        Approve
    </button>

    <button class="btn btn-danger btn-sm"
        onclick="rejectSub('<?= $r['sub_id'] ?>')">
        Reject
    </button>
<?php endif; ?>
</td>

</tr>
<?php endforeach; ?>

</tbody>
</table>
</div>
</div>

<script>
function approveSub(id){
    if(!confirm('อนุมัติร้านนี้?')) return;
    fetch('billing/subscription_action.php',{
        method:'POST',
        headers:{'Content-Type':'application/json'},
        body:JSON.stringify({id,action:'approve'})
    }).then(()=>location.reload());
}

function rejectSub(id){
    if(!confirm('ปฏิเสธสลิป?')) return;
    fetch('billing/subscription_action.php',{
        method:'POST',
        headers:{'Content-Type':'application/json'},
        body:JSON.stringify({id,action:'reject'})
    }).then(()=>location.reload());
}
</script>
