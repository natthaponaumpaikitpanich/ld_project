<?php
$store_id = $_SESSION['store_id'] ?? null;
if (!$store_id) {
    die('ไม่พบข้อมูลร้าน');
}

$sql = "
SELECT 
    o.id AS order_id,
    o.order_number,
    o.status AS order_status,
    o.created_at,

    p.id AS pickup_id,
    p.status AS pickup_status,
    p.scheduled_at

FROM orders o
LEFT JOIN pickups p ON p.order_id = o.id
WHERE o.store_id = ?
ORDER BY o.created_at DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$store_id]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>การจัดส่ง</title>

</head>

<body>
<div class="container mt-4">

    <h3 class="mb-3">🚚 งานจัดส่งของร้าน</h3>

    <div class="card shadow-sm">
        <div class="card-body">

            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Order</th>
                        <th>สถานะงานซัก</th>
                        <th>สถานะจัดส่ง</th>
                        <th>เวลานัดรับ</th>
                        <th>การจัดการ</th>
                    </tr>
                </thead>
                <tbody>

                <?php if (empty($orders)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted">
                            ยังไม่มีออเดอร์
                        </td>
                    </tr>
                <?php else: ?>

                    <?php foreach ($orders as $i => $o): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>

                        <td><?= htmlspecialchars($o['order_number']) ?></td>

                        <td>
                            <span class="badge bg-secondary">
                                <?= $o['order_status'] ?>
                            </span>
                        </td>

                        <td>
                            <?php if ($o['pickup_id']): ?>
                                <span class="badge bg-info">
                                    <?= $o['pickup_status'] ?>
                                </span>
                            <?php else: ?>
                                <span class="badge bg-warning">
                                    ยังไม่สร้างงานจัดส่ง
                                </span>
                            <?php endif; ?>
                        </td>

                        <td>
                            <?= $o['scheduled_at']
                                ? date('d/m/Y H:i', strtotime($o['scheduled_at']))
                                : '-' ?>
                        </td>

                        <td>
                            <?php if (!$o['pickup_id']): ?>
                                <a href="delivery_create.php?order_id=<?= $o['order_id'] ?>"
                                   class="btn btn-sm btn-primary">
                                   สร้างงานจัดส่ง
                                </a>
                            <?php else: ?>
                                <a href="menu/delivery/delivery_view.php?id=<?= $o['pickup_id'] ?>"
                                   class="btn btn-sm btn-outline-info">
                                   ดูรายละเอียด
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>

                <?php endif; ?>

                </tbody>
            </table>

        </div>
    </div>

</div>
</body>
</html>