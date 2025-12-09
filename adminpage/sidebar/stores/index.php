<?php

// ดึงร้านค้าทั้งหมด
$stmt = $pdo->prepare("SELECT * FROM stores ORDER BY created_at DESC");
$stmt->execute();
$stores = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
                            <?php else: ?>
                                <span class="badge bg-secondary">Disabled</span>
                            <?php endif; ?>
                        </td>

                        <td>
                            <a href="view.php?id=<?= $s['id'] ?>" class="btn btn-info btn-sm">ดู</a>
                            <a href="edit.php?id=<?= $s['id'] ?>" class="btn btn-warning btn-sm">แก้ไข</a>
                            <a href="delete.php?id=<?= $s['id'] ?>" class="btn btn-danger btn-sm"
                               onclick="return confirm('ยืนยันลบร้านค้านี้?');">
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
