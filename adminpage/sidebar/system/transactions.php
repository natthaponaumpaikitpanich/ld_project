<?php
// --- ส่วน PHP ด้านบนคงเดิมทุกบรรทัด ---
$filter = $_GET['filter'] ?? 'all';
$where = "WHERE ss.status IN ('active','waiting_approve')";

if ($filter === 'today') {
    $where .= " AND DATE(ss.paid_at) = CURDATE()";
} elseif ($filter === 'month') {
    $where .= " AND MONTH(ss.paid_at) = MONTH(CURDATE())
                AND YEAR(ss.paid_at) = YEAR(CURDATE())";
}

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
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="icon" href="../../../image/3.jpg">

    <style>
        body {
            font-family: 'Kanit', sans-serif;
            background-color: #f8f9fa;
        }

        .card {
            border: none;
            border-radius: 15px;
            transition: transform 0.2s;
        }

        .card:hover {
            transform: translateY(-5px);
        }

        .kpi-icon {
            font-size: 2rem;
            opacity: 0.3;
            position: absolute;
            right: 20px;
            bottom: 10px;
        }

        .glass-table {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
        }

        .btn-filter {
            border-radius: 10px;
            padding: 8px 20px;
        }

        .status-badge {
            border-radius: 50px;
            padding: 5px 15px;
            font-weight: 400;
        }

        .table thead th {
            background-color: #f8f9fa;
            border-bottom: none;
            color: #6c757d;
            font-weight: 500;
        }
    </style>
</head>

<body>
    <div class="container-fluid px-4 mt-4">

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold mb-0">📑 รายงานธุรกรรมและรายได้</h3>
                <span class="text-muted">วิเคราะห์รายได้จากระบบจัดการร้านซักอบรีด</span>
            </div>
            <div class="d-flex gap-2">
                <a href="sidebar.php?link=transactions&filter=all" class="btn btn-filter <?= $filter == 'all' ? 'btn-dark' : 'btn-outline-secondary' ?>">ทั้งหมด</a>
                <a href="sidebar.php?link=transactions&filter=today" class="btn btn-filter <?= $filter == 'today' ? 'btn-success' : 'btn-outline-success' ?>">วันนี้</a>
                <a href="sidebar.php?link=transactions&filter=month" class="btn btn-filter <?= $filter == 'month' ? 'btn-primary' : 'btn-outline-primary' ?>">เดือนนี้</a>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card shadow-sm h-100 border-start border-success border-4">
                    <div class="card-body">
                        <small class="text-muted">รายได้รวม</small>
                        <h3 class="fw-bold text-success mb-0"><?= number_format($summary['total_amount'] ?? 0, 2) ?> ฿</h3>
                        <i class="bi bi-currency-dollar kpi-icon text-success"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <small class="text-muted">ธุรกรรมทั้งหมด</small>
                        <h3 class="fw-bold mb-0"><?= $summary['total_txn'] ?? 0 ?></h3>
                        <i class="bi bi-receipt kpi-icon text-dark"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm h-100 border-start border-primary border-4">
                    <div class="card-body">
                        <small class="text-muted">อนุมัติแล้ว</small>
                        <h3 class="fw-bold text-primary mb-0"><?= $summary['approved_txn'] ?? 0 ?></h3>
                        <i class="bi bi-check-circle kpi-icon text-primary"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm h-100 border-start border-warning border-4">
                    <div class="card-body">
                        <small class="text-muted">รอตรวจสอบ</small>
                        <h3 class="fw-bold text-warning mb-0"><?= $summary['pending_txn'] ?? 0 ?></h3>
                        <i class="bi bi-hourglass-split kpi-icon text-warning"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-4">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <h6 class="fw-bold mb-4">📊 สัดส่วนธุรกรรม</h6>
                        <div style="height: 250px;">
                            <canvas id="txnChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card shadow-sm glass-table">
                    <div class="card-body p-0">
                        <div class="p-3 d-flex justify-content-between align-items-center border-bottom">
                            <h6 class="fw-bold mb-0">📋 รายการธุรกรรม</h6>
                            <a href="system/subscription_export_pdf.php?filter=<?= $filter ?>" class="btn btn-sm btn-danger px-3">
                                <i class="bi bi-file-pdf"></i> Export PDF
                            </a>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th class="ps-3">#</th>
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
                                            <td colspan="6" class="text-center py-4 text-muted">ไม่มีข้อมูล</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($rows as $i => $r): ?>
                                            <tr>
                                                <td class="ps-3 text-muted"><?= $i + 1 ?></td>
                                                <td class="fw-bold"><?= htmlspecialchars($r['store_name']) ?></td>
                                                <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($r['plan']) ?></span></td>
                                                <td class="fw-bold text-success"><?= number_format($r['monthly_fee'], 2) ?> ฿</td>
                                                <td>
                                                    <span class="status-badge badge bg-<?= $r['status'] == 'active' ? 'success' : 'warning' ?>">
                                                        <?= $r['status'] == 'active' ? 'อนุมัติแล้ว' : 'รอตรวจสอบ' ?>
                                                    </span>
                                                </td>
                                                <td class="small text-muted"><?= date('d/m/Y H:i', strtotime($r['paid_at'])) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // ใช้ตัวแปร $summary ตามที่คุณตั้งไว้ใน PHP เป๊ะๆ
        const approved = <?= (int)($summary['approved_txn'] ?? 0) ?>;
        const pending = <?= (int)($summary['pending_txn'] ?? 0) ?>;

        new Chart(document.getElementById('txnChart'), {
            type: 'doughnut',
            data: {
                labels: ['อนุมัติแล้ว', 'รอตรวจสอบ'],
                datasets: [{
                    data: [approved, pending],
                    backgroundColor: ['#0d6efd', '#ffc107'],
                    hoverOffset: 4
                }]
            },
            options: {
                maintainAspectRatio: false,
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