<?php


// ดึงแพ็กเกจ (เอาไว้ใช้ที่อื่นได้ ไม่กระทบ)
$plans = $pdo->query("SELECT * FROM billing_plans WHERE status='active'")
    ->fetchAll(PDO::FETCH_ASSOC);

// QUERY หลัก
$sql = "
    SELECT
        s.*,
        p.name  AS plan_name,
        p.price AS plan_price
    FROM stores s
    LEFT JOIN billing_plans p ON s.billing_plan_id = p.id
    ORDER BY s.created_at DESC
";

$stores = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ร้านซักอบรีดทั้งหมด</title>
</head>

<body style="margin-left:260px;">

<div class="container mt-4">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>ร้านซักอบรีดทั้งหมด</h3>
    </div>

    <div class="card shadow">
        <div class="card-body">

            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ชื่อร้าน</th>
                        <th>เบอร์โทร</th>
                        <th>ที่อยู่</th>
                        <th>วันที่สมัคร</th>
                        <th>สถานะ</th>
                        <th>แพ็คเกจที่สมัคร</th>
                        <th width="180">จัดการ</th>
                    </tr>
                </thead>

                <tbody>
                <?php foreach ($stores as $s): ?>
                    <tr>
                        <td><?= htmlspecialchars($s['name']) ?></td>
                        <td><?= htmlspecialchars($s['phone']) ?></td>
                        <td><?= nl2br(htmlspecialchars($s['address'])) ?></td>
                        <td><?= date('d/m/Y', strtotime($s['created_at'])) ?></td>

                        <td>
                            <?php if ($s['status'] === 'active'): ?>
                                <span class="badge bg-success">Active</span>
                            <?php elseif ($s['status'] === 'pending'): ?>
                                <span class="badge bg-warning">Pending</span>
                            <?php endif; ?>
                        </td>

                        <td>
                            <?php if ($s['plan_name']): ?>
                                <span class="badge bg-primary">
                                    <?= htmlspecialchars($s['plan_name']) ?>
                                </span><br>
                                <small class="text-muted">
                                    <?= number_format($s['plan_price'], 2) ?> ฿ / เดือน
                                </small>
                            <?php else: ?>
                                <span class="badge bg-secondary">ไม่เลือกแพ็กเกจ</span>
                            <?php endif; ?>
                        </td>

                        <td>
                            <a href="stores/edit.php?id=<?= $s['id'] ?>"
                               class="btn btn-warning btn-sm">แก้ไข</a>

                           <a href="stores/delete.php?id=<?= $s['id'] ?>"
   class="btn btn-danger btn-sm"
   onclick="return confirm('ยืนยันลบร้านนี้?')">
   ลบ
</a>

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
