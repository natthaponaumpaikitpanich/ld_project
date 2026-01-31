<?php
// --- คง Logic เดิมของคุณไว้ทั้งหมด ---
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
$reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="th">

<head>
    <meta charset="utf-8">
    <title>รายงานปัญหาจากร้านค้า</title>
    <link rel="stylesheet" href="../../../bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../../bootstrap/bootstrap-icons.css">
    <link href="../../assets/style.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="icon" href="../../../image/3.jpg">

    <style>
        body {
            font-family: 'Kanit', sans-serif;
            background-color: #f0f2f5;
            color: #334155;
        }

        .page-header {
            background: white;
            padding: 2rem;
            border-radius: 0 0 20px 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.02);
        }

        .report-card {
            border: none;
            border-radius: 16px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            background: #ffffff;
        }

        .report-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.08) !important;
        }

        /* สถานะแถบสีข้าง Card */
        .status-strip {
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 6px;
        }

        .strip-new {
            background: #f59e0b;
        }

        .strip-resolved {
            background: #10b981;
        }

        .strip-pending {
            background: #64748b;
        }

        .card-header {
            background: transparent !important;
            border-bottom: 1px solid #f1f5f9 !important;
            padding: 1.25rem;
        }

        .card-body {
            padding: 1.5rem;
        }

        .store-info-box {
            background: #f8fafc;
            border-radius: 12px;
            padding: 12px 15px;
        }

        .msg-content {
            background: #fff;
            border: 1px dashed #e2e8f0;
            border-radius: 12px;
            padding: 15px;
            margin-top: 10px;
        }

        .badge-pill {
            border-radius: 50px;
            padding: 6px 16px;
            font-weight: 500;
            font-size: 0.85rem;
        }

        /* Animation สำหรับปุ่ม */
        .btn-action {
            border-radius: 10px;
            padding: 10px 20px;
            font-weight: 500;
            transition: 0.2s;
        }

        .btn-action:hover {
            filter: brightness(90%);
            transform: scale(1.02);
        }

        .empty-state {
            padding: 80px 0;
            color: #94a3b8;
        }

        .empty-state i {
            font-size: 4rem;
            display: block;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
    </style>
</head>

<body>

    <div class="page-header mb-5">
        <div class="container-fluid px-4">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="fw-bold mb-1 text-dark">🚨 รายงานปัญหาจากร้านค้า</h2>
                    <p class="text-muted mb-0">มีปัญหาทั้งหมด <span class="badge bg-dark rounded-pill"><?= count($reports) ?></span> รายการที่ต้องดูแล</p>
                </div>
                <button class="btn btn-outline-primary btn-sm rounded-pill" onclick="window.location.reload()">
                    <i class="bi bi-arrow-clockwise"></i> อัปเดตข้อมูล
                </button>
            </div>
        </div>
    </div>

    <div class="container-fluid px-4">

        <?php if (empty($reports)): ?>
            <div class="empty-state text-center">
                <i class="bi bi-shield-check"></i>
                <h4>ทุกอย่างเรียบร้อยดี!</h4>
                <p>ขณะนี้ยังไม่มีรายงานปัญหาใหม่ส่งเข้ามาในระบบ</p>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            <?php foreach ($reports as $report):
                $statusClass = $report['status'] == 'new' ? 'new' : ($report['status'] == 'resolved' ? 'resolved' : 'pending');
                $statusLabel = $report['status'] == 'new' ? 'มาใหม่' : ($report['status'] == 'resolved' ? 'จัดการแล้ว' : 'รอดำเนินการ');
                $statusColor = $report['status'] == 'new' ? 'warning' : ($report['status'] == 'resolved' ? 'success' : 'secondary');
            ?>

                <div class="col-xl-6">
                    <div class="card shadow-sm report-card h-100">
                        <div class="status-strip strip-<?= $statusClass ?>"></div>

                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center">
                                <div class="bg-<?= $statusColor ?> bg-opacity-10 text-<?= $statusColor ?> p-2 rounded-3 me-3">
                                    <i class="bi bi-exclamation-triangle-fill fs-4"></i>
                                </div>
                                <div>
                                    <h5 class="fw-bold mb-0 text-dark"><?= htmlspecialchars($report['title']) ?></h5>
                                    <small class="text-muted">
                                        <i class="bi bi-clock me-1"></i> <?= date('d M Y | H:i', strtotime($report['created_at'])) ?>
                                    </small>
                                </div>
                            </div>
                            <span class="badge badge-pill bg-<?= $statusColor ?>-subtle text-<?= $statusColor ?> border border-<?= $statusColor ?> border-opacity-25">
                                ● <?= $statusLabel ?>
                            </span>
                        </div>

                        <div class="card-body">
                            <div class="row g-3 mb-4">
                                <div class="col-sm-6">
                                    <div class="store-info-box border-start border-3 border-primary">
                                        <small class="text-muted d-block uppercase small fw-bold">ร้านค้าผู้แจ้ง</small>
                                        <span class="fw-semibold text-primary"><i class="bi bi-shop me-1"></i> <?= htmlspecialchars($report['store_name'] ?? 'ไม่ระบุ') ?></span>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="store-info-box border-start border-3 border-info">
                                        <small class="text-muted d-block uppercase small fw-bold">เบอร์ติดต่อ</small>
                                        <span class="fw-semibold text-info"><i class="bi bi-telephone-fill me-1"></i> <?= htmlspecialchars($report['phone'] ?? '-') ?></span>
                                    </div>
                                </div>
                            </div>

                            <div class="fw-bold text-dark mb-2"><i class="bi bi-chat-left-text me-2"></i>รายละเอียดปัญหา:</div>
                            <div class="msg-content shadow-sm-inset">
                                <p class="mb-0 text-secondary" style="line-height: 1.6;">
                                    <?= nl2br(htmlspecialchars($report['message'])) ?>
                                </p>
                            </div>

                            <div class="d-flex justify-content-end gap-3 mt-4">
                                <a href="system/report_action.php?action=reject&id=<?= $report['id'] ?>"
                                    class="btn btn-light btn-action text-danger"
                                    onclick="return confirm('ยืนยันไม่รับเรื่องนี้?')">
                                    <i class="bi bi-trash3 me-1"></i> ปฏิเสธ
                                </a>

                                <a href="system/report_action.php?action=accept&id=<?= $report['id'] ?>"
                                    class="btn btn-success btn-action shadow-sm px-4">
                                    <i class="bi bi-check2-all me-1"></i> รับเรื่องและแก้ไข
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

            <?php endforeach; ?>
        </div>
    </div>



</body>

</html>