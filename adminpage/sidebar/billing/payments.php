<?php


$sql = "
    SELECT
        pay.id,
        pay.order_id,
        pay.amount,
        pay.provider,
        pay.provider_txn_id,
        pay.status,
        pay.paid_at,
        pay.created_at
    FROM payments pay
    ORDER BY pay.created_at DESC
";


$payments = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
?>

<!doctype html>
<html lang="th">

<head>
    <meta charset="utf-8">
    <title>การชำระเงินทั้งหมด</title>
    <link rel="stylesheet" href="/assets/bootstrap/css/bootstrap.min.css">
</head>

<body style="margin-left:260px;">

    <div class="container mt-4">

        <h3 class="mb-3">💰 การชำระเงินทั้งหมด</h3>

        <div class="card shadow-sm">
            <div class="card-body">

                <table class="table table-striped">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Order ID</th>
                            <th>จำนวนเงิน</th>
                            <th>ช่องทางชำระ</th>
                            <th>รหัสธุรกรรม</th>
                            <th>วันที่ชำระ</th>
                            <th>สถานะ</th>
                        </tr>

                    </thead>
                    <tbody>

                        <?php foreach ($payments as $p): ?>
                            <tr>
                                <td><?= htmlspecialchars($p['id']) ?></td>
                                <td><?= htmlspecialchars($p['order_id']) ?></td>
                                <td><?= number_format($p['amount'], 2) ?> ฿</td>
                                <td><?= htmlspecialchars(strtoupper($p['provider'])) ?></td>
                                <td><?= htmlspecialchars($p['provider_txn_id'] ?? '-') ?></td>
                                <td>
                                    <?= $p['paid_at'] ? date('d/m/Y H:i', strtotime($p['paid_at'])) : '-' ?>
                                </td>
                                <td>
                                    <?php if ($p['status'] === 'success'): ?>
                                        <span class="badge bg-success">ชำระแล้ว</span>
                                    <?php elseif ($p['status'] === 'pending'): ?>
                                        <span class="badge bg-warning">รอดำเนินการ</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">ล้มเหลว</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>


                    </tbody>
                </table>

            </div>
        </div>

    </div>

    <script src="/assets/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>

</html>