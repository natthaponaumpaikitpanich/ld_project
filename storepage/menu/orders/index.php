
<?php

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['store_owner','staff'])) {
    die('no permission');
}

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT 
        o.id,
        o.order_number,
        o.status,
        o.notes,
        o.created_at,
        u.display_name AS customer_name
    FROM orders o
    LEFT JOIN users u ON u.id = o.customer_id
    JOIN store_staff ss ON ss.store_id = o.store_id
    WHERE ss.user_id = :user_id
    ORDER BY o.created_at DESC
");
$stmt->execute([':user_id' => $user_id]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

function status_badge($s) {
    return match($s) {
        'created'=>'secondary',
        'picked_up'=>'info',
        'in_process'=>'warning',
        'ready'=>'primary',
        'out_for_delivery'=>'dark',
        'completed'=>'success',
        default=>'secondary'
    };
}
?>


<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>งานซักของร้าน</title>
<link href="../../../bootstrap/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <h4 class="fw-bold mb-3 mt-3">📦 งานซักของร้าน (<?= count($orders) ?> งาน)</h4>
<div class="card shadow-sm mt-3">
<div class="container py-4">
    

    <div class="card">
        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>เลขงาน</th>
                        <th>ลูกค้า</th>
                        <th>สถานะ</th>
                        <th>วันที่</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!$orders): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">
                            ยังไม่มีงาน
                        </td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($orders as $i => $o): ?>
                    <tr>
                        <td><?= $i+1 ?></td>
                        <td><?= htmlspecialchars($o['order_number']) ?></td>
                        <td><?= htmlspecialchars($o['customer_name'] ?? '-') ?></td>
                        <td>
                            <span class="badge bg-<?= status_badge($o['status']) ?>">
                                <?= $o['status'] ?>
                            </span>
                        </td>
                        <td><?= date('d/m/Y H:i', strtotime($o['created_at'])) ?></td>
                        <td>
                            <a href="menu/orders/detail.php?id=<?= $o['id'] ?>"
                               class="btn btn-sm btn-outline-primary">
                                จัดการงาน
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</div></div>
</body>
</html>
