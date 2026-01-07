<?php
$stmt = $pdo->query("
    SELECT *
    FROM billing_plans
    ORDER BY price ASC
");
$plans = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php if (isset($_GET['error']) && $_GET['error'] === 'used'): ?>
    <div class="alert alert-danger">
        ไม่สามารถลบแพ็กเกจได้ เนื่องจากมีร้านที่กำลังใช้งานแพ็กเกจนี้อยู่
    </div>
<?php endif; ?>

<div class="container mt-4">
    <h3 class="fw-bold">💳 แพ็กเกจรายเดือน</h3>

    <a href="billing/plan_create.php" class="btn btn-primary mb-3">
        + เพิ่มแพ็กเกจ
    </a>

    <table class="table table-bordered">
        <thead class="table-light">
            <tr>
                <th>ชื่อแพ็กเกจ</th>
                <th>ราคา/เดือน</th>
                <th>สถานะ</th>
                <th width="160">จัดการ</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($plans as $p): ?>
                <tr>
                    <td><?= htmlspecialchars($p['name']) ?></td>
                    <td><?= number_format($p['price'], 2) ?> ฿</td>
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
