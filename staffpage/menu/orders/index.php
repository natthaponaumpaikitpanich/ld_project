
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

<style>
body {
    background:#f4f6f9;
}

.page-header {
    background: linear-gradient(135deg,#0d6efd,#20c997);
    color:#fff;
    border-radius:16px;
}

.table thead th {
    font-weight:600;
}

.status-badge {
    padding: .4em .75em;
    font-size: .75rem;
}

.action-btn {
    transition:.2s;
}
.action-btn:hover {
    transform: translateY(-1px);
}
</style>
</head>

<body>

<div class="container py-4">

    <!-- ===== HEADER ===== -->
    <div class="page-header p-4 mb-4 shadow-sm">
        <h4 class="fw-bold mb-1">📦 งานซักของร้าน</h4>
        <div class="opacity-75 small">
            ทั้งหมด <?= count($orders) ?> งาน
        </div>
    </div>

    <!-- ===== CONTENT ===== -->
    <div class="card shadow-sm border-0">
        <div class="card-body p-0">

            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width:60px">#</th>
                        <th>เลขงาน</th>
                        <th>ลูกค้า</th>
                        <th>สถานะ</th>
                        <th>วันที่สร้าง</th>
                        <th style="width:120px"></th>
                    </tr>
                </thead>
                <tbody>

                <?php if (!$orders): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">
                            ยังไม่มีงานในระบบ
                        </td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($orders as $i => $o): ?>
                    <tr>
                        <td class="text-muted"><?= $i+1 ?></td>

                        <td class="fw-semibold">
                            <?= htmlspecialchars($o['order_number']) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($o['customer_name'] ?? '-') ?>
                        </td>

                        <td>
                            <span class="badge status-badge bg-<?= status_badge($o['status']) ?>">
                                <?= strtoupper($o['status']) ?>
                            </span>
                        </td>

                        <td class="text-muted">
                            <?= date('d/m/Y H:i', strtotime($o['created_at'])) ?>
                        </td>

                        <td class="text-end">
                            <a href="menu/orders/detail.php?id=<?= $o['id'] ?>"
                               class="btn btn-sm btn-outline-primary action-btn">
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

<script src="../../../bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>

