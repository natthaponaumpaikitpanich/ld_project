<?php
// system/transactions.php

$filter = $_GET['filter'] ?? 'all';

$where = '';
if ($filter === 'today') {
    $where = "WHERE DATE(pay.paid_at) = CURDATE()";
} elseif ($filter === 'month') {
    $where = "WHERE MONTH(pay.paid_at) = MONTH(CURDATE())
              AND YEAR(pay.paid_at) = YEAR(CURDATE())";
}

$sql = "
SELECT
    pay.id AS payment_id,
    s.name AS store_name,
    o.order_number,
    pay.amount,
    pay.provider,
    pay.status,
    pay.paid_at
FROM payments pay
LEFT JOIN orders o ON pay.order_id = o.id
LEFT JOIN stores s ON o.store_id = s.id
$where
ORDER BY pay.paid_at DESC
";

$rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mt-4">

    <h3 class="mb-3">📑 รายงานธุรกรรม</h3>

    <!-- FILTER -->
    <div class="mb-3 d-flex gap-3">
        <a href="sidebar.php?link=transactions&filter=all"
           class="btn btn-outline-secondary <?= $filter=='all'?'active':'' ?>">
           ทั้งหมด
        </a>

        <a href="sidebar.php?link=transactions&filter=today"
           class="btn btn-outline-success <?= $filter=='today'?'active':'' ?>">
           วันนี้
        </a>

        <a href="sidebar.php?link=transactions&filter=month"
           class="btn btn-outline-primary <?= $filter=='month'?'active':'' ?>">
           เดือนนี้
        </a>
    </div>

    <div class="card shadow">
        <div class="card-body">

            <table class="table table-striped align-middle">
                <thead class="table-primary">
                    <tr>
                        <th>#</th>
                        <th>ร้านค้า</th>
                        <th>Order</th>
                        <th>ยอดเงิน</th>
                        <th>ช่องทาง</th>
                        <th>สถานะ</th>
                        <th>วันที่ชำระ</th>
                    </tr>
                </thead>

                <tbody>
                <?php if (empty($rows)): ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted">
                            ไม่มีข้อมูลธุรกรรม
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($rows as $i => $r): ?>
                    <tr>
                        <td><?= $i+1 ?></td>
                        <td><?= htmlspecialchars($r['store_name'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($r['order_number'] ?? '-') ?></td>
                        <td><?= number_format($r['amount'], 2) ?> ฿</td>
                        <td><?= htmlspecialchars($r['provider']) ?></td>
                        <td>
                            <span class="badge bg-<?= $r['status']=='success'?'success':'danger' ?>">
                                <?= $r['status']=='success'?'สำเร็จ':'ไม่สำเร็จ' ?>
                            </span>
                        </td>
                        <td><?= date('d/m/Y H:i', strtotime($r['paid_at'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>

            </table>

        </div>
    </div>
</div>