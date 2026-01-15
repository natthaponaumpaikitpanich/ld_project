<?php
$sql = "
SELECT
    r.id,
    r.title,
    r.message,
    r.status,
    r.created_at,
    s.name AS store_name,
    s.phone
FROM reports r
LEFT JOIN stores s ON r.store_id = s.id
ORDER BY r.created_at DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute();
$reports = $stmt->fetchAll(PDO::FETCH_ASSOC);?>
<!doctype html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <title>รายงานปัญหาจากร้านค้า</title>
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
        <h3 class="fw-bold mb-1">🚨 รายงานปัญหาจากร้านค้า</h3>
        <small class="text-muted">
            ตรวจสอบและจัดการปัญหาที่ร้านค้าส่งเข้ามาในระบบ
        </small>
    </div>

    <?php if (empty($reports)): ?>
        <div class="alert alert-secondary text-center">
            🎉 ไม่มีการรายงานปัญหา
        </div>
    <?php endif; ?>

    <?php foreach ($reports as $report): ?>

    <div class="card shadow-sm border-0 mb-4 report-card">

        <!-- HEADER -->
        <div class="card-header d-flex justify-content-between align-items-center
            <?= $report['status']=='new'?'bg-warning-subtle':
                ($report['status']=='resolved'?'bg-success-subtle':'bg-light') ?>">
            
            <div>
                <strong><?= htmlspecialchars($report['title']) ?></strong>
                <div class="small text-muted">
                    <?= date('d/m/Y H:i', strtotime($report['created_at'])) ?>
                </div>
            </div>

            <span class="badge bg-<?= 
                $report['status']=='new'?'warning':
                ($report['status']=='resolved'?'success':'secondary')
            ?>">
                <?= $report['status']=='new'?'ใหม่':
                    ($report['status']=='resolved'?'จัดการแล้ว':'รอดำเนินการ') ?>
            </span>
        </div>

        <!-- BODY -->
        <div class="card-body">

            <div class="row mb-3">
                <div class="col-md-6">
                    <div class="fw-semibold">ร้านค้า</div>
                    <div><?= htmlspecialchars($report['store_name'] ?? 'ไม่ระบุ') ?></div>
                </div>

                <div class="col-md-6">
                    <div class="fw-semibold">เบอร์ติดต่อ</div>
                    <div><?= htmlspecialchars($report['phone'] ?? '-') ?></div>
                </div>
            </div>

            <div class="mb-3">
                <div class="fw-semibold">รายละเอียดปัญหา</div>
                <div class="text-muted">
                    <?= nl2br(htmlspecialchars($report['message'])) ?>
                </div>
            </div>

            <!-- ACTION -->
            <div class="d-flex justify-content-end gap-2 mt-4">

                <a href="system/report_action.php?action=accept&id=<?= $report['id'] ?>"
                   class="btn btn-success">
                   <i class="bi bi-check-circle me-1"></i> รับเรื่อง
                </a>

                <a href="system/report_action.php?action=reject&id=<?= $report['id'] ?>"
                   class="btn btn-outline-danger"
                   onclick="return confirm('ยืนยันไม่รับเรื่องนี้?')">
                   <i class="bi bi-x-circle me-1"></i> ไม่อนุมัติ
                </a>

            </div>

        </div>
    </div>

    <?php endforeach; ?>

</div>
<script>
document.querySelectorAll('.report-card').forEach(card => {
    if (card.innerText.includes('ใหม่')) {
        card.style.borderLeft = '6px solid #f59e0b';
    }
});
</script>
</body>
</html>