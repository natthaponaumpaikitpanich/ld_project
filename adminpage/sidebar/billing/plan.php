<?php
$stmt = $pdo->query("
    SELECT *
    FROM billing_plans
    ORDER BY price ASC
");
$plans = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mt-4">
    <h3 class="fw-bold">💳 แพ็กเกจรายเดือน</h3>

    <a href="billing/plan_create.php" class="btn btn-primary mb-3">
        + เพิ่มแพ็กเกจ
    </a>

    <table class="table table-bordered align-middle">
        <thead class="table-light">
            <tr>
                <th>ชื่อแพ็กเกจ</th>
                <th>ราคา</th>
                <th>ยอดโอน</th>
                <th>QR Code</th>
                <th>สถานะ</th>
                <th width="160">จัดการ</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($plans as $p): ?>
            <tr>
                <td><?= htmlspecialchars($p['name']) ?></td>

                <td><?= number_format($p['price'], 2) ?> ฿</td>

                <td class="fw-bold text-danger">
                    <?= number_format($p['amount'], 2) ?> ฿
                </td>

                <td>
                    <?php if ($p['qr_image']): ?>
                        <img src="../<?= htmlspecialchars($p['qr_image']) ?>"
                             style="width:80px;height:80px;object-fit:contain;">
                    <?php else: ?>
                        -
                    <?php endif; ?>
                </td>

                <td>
                    <span class="badge bg-<?= $p['status'] === 'active' ? 'success' : 'secondary' ?>">
                        <?= htmlspecialchars($p['status']) ?>
                    </span>
                </td>

                <td>
                    <a href="billing/plan_edit.php?id=<?= $p['id'] ?>"
                       class="btn btn-sm btn-warning">
                        แก้ไข
                    </a>

                    <a href="billing/plan_delete.php?id=<?= $p['id'] ?>"
                       class="btn btn-sm btn-danger"
                       onclick="return confirm('ต้องการลบแพ็กเกจนี้จริงหรือไม่?')">
                        ลบ
                    </a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
