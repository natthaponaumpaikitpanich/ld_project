<?php
$filter = $_GET['filter'] ?? 'all';

$where = "WHERE ss.status IN ('active','waiting_approve')";

if ($filter === 'today') {
    $where .= " AND DATE(ss.paid_at) = CURDATE()";
} elseif ($filter === 'month') {
    $where .= " AND MONTH(ss.paid_at) = MONTH(CURDATE())
                AND YEAR(ss.paid_at) = YEAR(CURDATE())";
}

/* ===== รายการธุรกรรม ===== */
$sql = "
SELECT
    ss.id,
    s.name AS store_name,
    ss.plan,
    ss.monthly_fee,
    ss.status,
    ss.paid_at
FROM store_subscriptions ss
JOIN stores s ON ss.store_id = s.id
$where
ORDER BY ss.paid_at DESC
";
$rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

/* ===== summary ===== */
$summarySql = "
SELECT
    COUNT(*) AS total_txn,
    SUM(monthly_fee) AS total_amount,
    SUM(CASE WHEN status='active' THEN 1 ELSE 0 END) AS approved_txn,
    SUM(CASE WHEN status!='active' THEN 1 ELSE 0 END) AS pending_txn
FROM store_subscriptions ss
$where
";
$summary = $pdo->query($summarySql)->fetch(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="th">
<head>
    <meta charset="utf-8">

    <link rel="stylesheet" href="../../../bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../../bootstrap/bootstrap-icons.css">
    <link href="../../assets/style.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Kanit:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <link rel="icon" href="../../../image/3.jpg">
</head>
    <body>
        <div class="container-fluid px-4 mt-4">

    <!-- PAGE HEADER -->
    <div class="mb-4">
        <h3 class="fw-bold mb-1">📑 รายงานธุรกรรมและรายได้</h3>
        <small class="text-muted">
            วิเคราะห์รายได้จากการสมัครแพ็กเกจร้านซักอบรีด
        </small>
    </div>

    <!-- SUMMARY KPI -->
    <div class="row g-3 mb-4">

        <div class="col-md-3">
            <div class="card shadow-sm border-0 text-center h-100">
                <div class="card-body">
                    <small class="text-muted">รายได้รวม</small>
                    <h4 class="fw-bold text-success">
                        <?= number_format($summary['total_amount'] ?? 0, 2) ?> ฿
                    </h4>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm border-0 text-center h-100">
                <div class="card-body">
                    <small class="text-muted">ธุรกรรมทั้งหมด</small>
                    <h4 class="fw-bold">
                        <?= $summary['total_txn'] ?? 0 ?>
                    </h4>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm border-0 text-center h-100">
                <div class="card-body">
                    <small class="text-muted">อนุมัติแล้ว</small>
                    <h4 class="fw-bold text-primary">
                        <?= $summary['approved_txn'] ?? 0 ?>
                    </h4>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm border-0 text-center h-100">
                <div class="card-body">
                    <small class="text-muted">รอตรวจสอบ</small>
                    <h4 class="fw-bold text-warning">
                        <?= $summary['pending_txn'] ?? 0 ?>
                    </h4>
                </div>
            </div>
        </div>

    </div>

    <!-- FILTER -->
    <div class="d-flex gap-2 mb-3">
        <a href="sidebar.php?link=transactions&filter=all"
           class="btn btn-outline-secondary <?= $filter=='all'?'active':'' ?>">
            ทั้งหมด
        </a>
        <a href="sidebar.php?link=transactions&filter=today"
           class="btn btn-outline-success <?= $filter=='today'?'active':'' ?>">
            วันนี้
        </a>
        <a href="sidebar.php?link=transactions&filter=month"
           class="btn btn-outline-primary <?= $filter=='month'?'active':'' ?>">
            เดือนนี้
        </a>
    </div>

    <!-- CHART + TABLE -->
    <div class="row g-4">

        <!-- CHART -->
        <div class="col-lg-5">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <h6 class="fw-semibold mb-3">
                        📊 สัดส่วนธุรกรรม
                    </h6>
                    <canvas id="txnChart" height="220"></canvas>
                </div>
            </div>
        </div>

        <!-- TABLE -->
        <div class="col-lg-7">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">

                    <h6 class="fw-semibold mb-3">
                        📋 รายการธุรกรรม
                    </h6>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>ร้านค้า</th>
                                    <th>แพ็กเกจ</th>
                                    <th>ยอดเงิน</th>
                                    <th>สถานะ</th>
                                    <th>วันที่ชำระ</th>
                                </tr>
                            </thead>
                            <tbody>

                            <?php if (empty($rows)): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted">
                                        ไม่มีข้อมูล
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($rows as $i => $r): ?>
                                <tr>
                                    <td><?= $i+1 ?></td>
                                    <td><?= htmlspecialchars($r['store_name']) ?></td>
                                    <td><?= htmlspecialchars($r['plan']) ?></td>
                                    <td class="fw-semibold text-success">
                                        <?= number_format($r['monthly_fee'], 2) ?> ฿
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= $r['status']=='active'?'success':'warning' ?>">
                                            <?= $r['status']=='active'?'อนุมัติแล้ว':'รอตรวจสอบ' ?>
                                        </span>
                                    </td>
                                    <td><?= date('d/m/Y H:i', strtotime($r['paid_at'])) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>

                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3 text-end">
                        <a href="system/subscription_export_pdf.php?filter=<?= $filter ?>"
                           class="btn btn-danger">
                           <i class="bi bi-file-earmark-pdf-fill me-1"></i>
                           Export PDF
                        </a>
                    </div>

                </div>
            </div>
        </div>

    </div>

</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
const approved = <?= (int)($summary['approved_txn'] ?? 0) ?>;
const pending  = <?= (int)($summary['pending_txn'] ?? 0) ?>;

new Chart(document.getElementById('txnChart'), {
    type: 'doughnut',
    data: {
        labels: ['อนุมัติแล้ว', 'รอตรวจสอบ'],
        datasets: [{
            data: [approved, pending],
        }]
    },
    options: {
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});
</script>

</body>
</html>