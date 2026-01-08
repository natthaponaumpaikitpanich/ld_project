<?php
$sql = "
    SELECT
        ss.id,
        s.name AS store_name,
        ss.plan,
        ss.monthly_fee,
        ss.status,
        ss.slip_image,
        ss.paid_at,
        ss.created_at
    FROM store_subscriptions ss
    JOIN stores s ON ss.store_id = s.id
    ORDER BY ss.created_at DESC
";

$rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
?>

<!doctype html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <title>การสมัครแพ็กเกจร้านค้า</title>
    <link rel="stylesheet" href="/assets/bootstrap/css/bootstrap.min.css">
</head>

<body style="margin-left:260px;">

<div class="container mt-4">

    <h3 class="mb-3">💳 ประวัติการสมัครแพ็กเกจร้านค้า</h3>

    <div class="card shadow-sm">
        <div class="card-body">

            <table class="table table-striped align-middle">
                <thead class="table-light">
                    <tr>
                        <th>ร้านค้า</th>
                        <th>แพ็กเกจ</th>
                        <th>จำนวนเงิน</th>
                        <th>สลิป</th>
                        <th>วันที่ชำระ</th>
                        <th>สถานะ</th>
                    </tr>
                </thead>
                <tbody>

                <?php if (empty($rows)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted">
                            ยังไม่มีข้อมูลการสมัคร
                        </td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td><?= htmlspecialchars($r['store_name']) ?></td>

                        <td>
                            <span class="badge bg-primary">
                                <?= htmlspecialchars($r['plan']) ?>
                            </span>
                        </td>

                        <td class="fw-bold">
                            <?= number_format($r['monthly_fee'], 2) ?> ฿
                        </td>

                        <td>
                            <?php if ($r['slip_image']): ?>
                                <a href="../../<?= htmlspecialchars($r['slip_image']) ?>"
   target="_blank"
   class="btn btn-sm btn-outline-info">
    ดูสลิป
</a>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>

                        <td>
                            <?= $r['paid_at']
                                ? date('d/m/Y H:i', strtotime($r['paid_at']))
                                : '-' ?>
                        </td>

                        <td>
                            <?php
                            $badge = match ($r['status']) {
                                'active'           => 'success',
                                'waiting_approve'  => 'warning',
                                'rejected'         => 'danger',
                                default            => 'secondary'
                            };
                            ?>
                            <span class="badge bg-<?= $badge ?>">
                                <?= $r['status'] ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>

                </tbody>
            </table>

        </div>
    </div>

</div>

<script src="../../../bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
