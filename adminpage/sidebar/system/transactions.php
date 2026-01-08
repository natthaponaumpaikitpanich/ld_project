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

<div class="container mt-4">

    <!-- SUMMARY -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card shadow text-center">
                <div class="card-body">
                    <h6 class="text-muted">รายได้รวม</h6>
                    <h4 class="fw-bold text-success">
                        <?= number_format($summary['total_amount'] ?? 0, 2) ?> ฿
                    </h4>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow text-center">
                <div class="card-body">
                    <h6 class="text-muted">ธุรกรรมทั้งหมด</h6>
                    <h4 class="fw-bold"><?= $summary['total_txn'] ?? 0 ?></h4>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow text-center">
                <div class="card-body">
                    <h6 class="text-muted">อนุมัติแล้ว</h6>
                    <h4 class="fw-bold text-primary"><?= $summary['approved_txn'] ?? 0 ?></h4>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow text-center">
                <div class="card-body">
                    <h6 class="text-muted">รอตรวจสอบ</h6>
                    <h4 class="fw-bold text-warning"><?= $summary['pending_txn'] ?? 0 ?></h4>
                </div>
            </div>
        </div>
    </div>

    <h3 class="mb-3">📑 รายงานรายได้จากการสมัครร้าน</h3>

    <!-- FILTER -->
    <div class="mb-3 d-flex gap-3">
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

    <!-- TABLE -->
    <div class="card shadow">
        <div class="card-body">

            <table class="table table-striped align-middle">
                <thead class="table-primary">
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
                            <td><?= number_format($r['monthly_fee'], 2) ?> ฿</td>
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

            <div class="mt-3 d-flex">
                <a href="billing/subscription_export_pdf.php?filter=<?= $filter ?>"
   class="btn btn-danger bi bi-file-earmark-pdf-fill">
   Export PDF
</a>

            </div>

        </div>
    </div>
</div>
