<?php

$store_id = $_SESSION['store_id'] ?? null;
if (!$store_id) {
    die("ไม่พบร้าน");
}

/* ---------- รายได้วันนี้ ---------- */
$stmt = $pdo->prepare("
    SELECT IFNULL(SUM(p.amount),0)
    FROM payments p
    JOIN orders o ON p.order_id = o.id
    WHERE p.status = 'confirmed'
      AND o.store_id = ?
      AND DATE(p.confirmed_at) = CURDATE()
");
$stmt->execute([$store_id]);
$today_income = $stmt->fetchColumn();

/* ---------- รายได้เดือนนี้ ---------- */
$stmt = $pdo->prepare("
    SELECT IFNULL(SUM(p.amount),0)
    FROM payments p
    JOIN orders o ON p.order_id = o.id
    WHERE p.status = 'confirmed'
      AND o.store_id = ?
      AND MONTH(p.confirmed_at) = MONTH(CURDATE())
      AND YEAR(p.confirmed_at) = YEAR(CURDATE())
");
$stmt->execute([$store_id]);
$month_income = $stmt->fetchColumn();

/* ---------- รายการชำระเงิน ---------- */
$stmt = $pdo->prepare("
    SELECT 
        p.amount,
        p.provider,
        p.status,
        p.confirmed_at,
        o.order_number
    FROM payments p
    JOIN orders o ON p.order_id = o.id
    WHERE o.store_id = ?
      AND p.status = 'confirmed'
    ORDER BY p.confirmed_at DESC
");
$stmt->execute([$store_id]);
$payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mt-4">

    <h3 class="mb-4">💰 รายได้ร้าน</h3>

    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card p-3 shadow-sm">
                <small class="text-muted">รายได้วันนี้</small>
                <h4 class="text-success">
                    <?= number_format($today_income, 2) ?> ฿
                </h4>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card p-3 shadow-sm">
                <small class="text-muted">รายได้เดือนนี้</small>
                <h4 class="text-primary">
                    <?= number_format($month_income, 2) ?> ฿
                </h4>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <h5 class="mb-3">ประวัติการชำระเงิน</h5>

            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Order</th>
                        <th>จำนวนเงิน</th>
                        <th>ช่องทาง</th>
                        <th>สถานะ</th>
                        <th>วันที่ชำระ</th>
                    </tr>
                </thead>
                <tbody>

                <?php if (empty($payments)): ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted">
                            ยังไม่มีการชำระเงิน
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($payments as $p): ?>
                    <tr>
                        <td><?= htmlspecialchars($p['order_number']) ?></td>
                        <td><?= number_format($p['amount'], 2) ?> ฿</td>
                        <td><?= htmlspecialchars($p['provider']) ?></td>
                        <td>
                            <span class="badge bg-success">
                                รับเงินแล้ว
                            </span>
                        </td>
                        <td>
                            <?= date('d/m/Y H:i', strtotime($p['confirmed_at'])) ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>

                </tbody>
            </table>
        </div>
    </div>

</div>
