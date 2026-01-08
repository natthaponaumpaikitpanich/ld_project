<?php
require_once "../../ld_db.php";

/*
|--------------------------------------------------------------------------
| QUERY: ร้านทั้งหมด + subscription ล่าสุด
|--------------------------------------------------------------------------
*/
$sql = "
SELECT
    s.id   AS store_id,
    s.name AS store_name,
    s.phone,
    s.address,
    s.created_at AS store_created,

    ss.id        AS sub_id,
    ss.plan      AS plan_name,
    ss.monthly_fee AS plan_price,
    ss.status    AS sub_status,
    ss.slip_image,
    ss.created_at AS sub_created

FROM stores s
LEFT JOIN store_subscriptions ss
    ON ss.store_id = s.id
    AND ss.id = (
        SELECT ss2.id
        FROM store_subscriptions ss2
        WHERE ss2.store_id = s.id
        ORDER BY ss2.created_at DESC
        LIMIT 1
    )
ORDER BY s.created_at DESC
";

$stores = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
?>

<!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<title>ร้านค้าทั้งหมด</title>
<link href="/ld_project/bootstrap/css/bootstrap.min.css" rel="stylesheet">
</head>

<body style="margin-left:260px;">
<div class="container mt-4">

<h3 class="fw-bold mb-3">🏪 ร้านซักอบรีดทั้งหมด</h3>

<div class="card shadow">
<div class="card-body p-0">

<table class="table table-striped align-middle mb-0">
<thead class="table-light">
<tr>
    <th>ชื่อร้าน</th>
    <th>เบอร์</th>
    <th>วันที่สมัคร</th>
    <th>แพ็กเกจ</th>
    <th>สถานะ</th>
    <th width="220">จัดการ</th>
</tr>
</thead>

<tbody>
<?php if (empty($stores)): ?>
<tr>
    <td colspan="6" class="text-center text-muted py-4">
        ยังไม่มีร้าน
    </td>
</tr>
<?php endif; ?>

<?php foreach ($stores as $s): ?>
<tr>

<td>
    <div class="fw-semibold"><?= htmlspecialchars($s['store_name']) ?></div>
    <small class="text-muted"><?= nl2br(htmlspecialchars($s['address'])) ?></small>
</td>

<td><?= htmlspecialchars($s['phone']) ?></td>

<td><?= date('d/m/Y', strtotime($s['store_created'])) ?></td>

<td>
<?php if ($s['plan_name']): ?>
    <span class="badge bg-primary">
        <?= htmlspecialchars($s['plan_name']) ?>
    </span><br>
    <small class="text-muted">
        <?= number_format($s['plan_price'],2) ?> ฿
    </small>
<?php else: ?>
    <span class="badge bg-secondary">ยังไม่สมัคร</span>
<?php endif; ?>
</td>

<td>
<?php
    echo match($s['sub_status']) {
        'waiting_approve' => '<span class="badge bg-warning">รออนุมัติ</span>',
        'active'          => '<span class="badge bg-success">ใช้งานได้</span>',
        'rejected'        => '<span class="badge bg-danger">ปฏิเสธ</span>',
        'expired'         => '<span class="badge bg-dark">หมดอายุ</span>',
        default           => '<span class="badge bg-secondary">-</span>'
    };
?>
</td>

<td>
<?php if ($s['sub_status'] === 'waiting_approve'): ?>

    <?php if ($s['slip_image']): ?>
        <a href="/ld_project/<?= htmlspecialchars($s['slip_image']) ?>"
           target="_blank"
           class="btn btn-sm btn-outline-info">
           ดูสลิป
        </a>
    <?php endif; ?>

    <a href="approve.php?id=<?= $s['sub_id'] ?>"
       class="btn btn-sm btn-success"
       onclick="return confirm('อนุมัติร้านนี้?')">
       Approve
    </a>

    <a href="reject.php?id=<?= $s['sub_id'] ?>"
       class="btn btn-sm btn-danger"
       onclick="return confirm('ปฏิเสธร้านนี้?')">
       Reject
    </a>

<?php else: ?>
    <span class="text-muted">-</span>
<?php endif; ?>
</td>

</tr>
<?php endforeach; ?>
</tbody>
</table>

</div>
</div>

</div>
</body>
</html>
