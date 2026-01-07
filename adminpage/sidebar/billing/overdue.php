<?php
$sql = "
SELECT
    s.id AS store_id,
    s.name AS store_name,
    ss.plan AS plan_name,
    ss.monthly_fee AS price,
    ss.end_date AS billing_end,
    DATEDIFF(CURDATE(), ss.end_date) AS overdue_days
FROM store_subscriptions ss
JOIN stores s ON ss.store_id = s.id
WHERE ss.status != 'active'
  AND ss.end_date < CURDATE()
  AND s.status = 'active'
ORDER BY overdue_days DESC
";

$rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="card shadow">
    <div class="card-body">

        <h5 class="mb-3">ร้านที่ค้างชำระ</h5>

        <table class="table table-striped align-middle">
            <thead>
                <tr>
                    <th>ร้าน</th>
                    <th>แพ็กเกจ</th>
                    <th>ราคา</th>
                    <th>วันหมดอายุ</th>
                    <th>ค้าง (วัน)</th>
                    <th width="180">จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted">
                            🎉 ไม่มีร้านค้างชำระ
                        </td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td><?= htmlspecialchars($r['store_name']) ?></td>
                        <td><?= htmlspecialchars($r['plan_name'] ?? '-') ?></td>
                        <td><?= number_format($r['price'], 2) ?> ฿</td>
                        <td><?= date('d/m/Y', strtotime($r['billing_end'])) ?></td>
                        <td>
                            <span class="badge bg-danger">
                                <?= (int)$r['overdue_days'] ?> วัน
                            </span>
                        </td>
                        <td>
                            <a href="store_view.php?id=<?= $r['store_id'] ?>"
                               class="btn btn-sm btn-info">
                               ดูร้าน
                            </a>

                            <a href="pay.php?store_id=<?= $r['store_id'] ?>"
                               class="btn btn-sm btn-primary">
                               ชำระเงิน
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

    </div>
</div>
