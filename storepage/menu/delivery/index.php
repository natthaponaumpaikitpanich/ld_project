<?php
$store_id = $_SESSION['store_id'] ?? null;
if (!$store_id) {
    die('ไม่พบข้อมูลร้าน');
}

$sql = "
SELECT 
    o.id AS order_id,
    o.order_number,
    o.status AS order_status,
    o.created_at,

    p.id AS pickup_id,
    p.status AS pickup_status,
    p.scheduled_at
FROM orders o
LEFT JOIN pickups p 
  ON p.id = (
      SELECT id 
      FROM pickups 
      WHERE order_id = o.id 
      ORDER BY created_at DESC 
      LIMIT 1
  )
WHERE o.store_id = ?
ORDER BY o.created_at DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$store_id]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>การจัดส่ง | Store Management</title>
    <link href="../../../bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary-blue: #0061ff;
            --soft-blue: #e3f2fd;
            --navy: #1e3c72;
            --glass-white: rgba(255, 255, 255, 0.9);
        }

        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            font-family: 'Kanit', sans-serif;
            min-height: 100vh;
        }

        /* HEADER SECTION */
        .page-header {
            background: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            box-shadow: 0 4px 15px rgba(0,0,0,0.03);
        }

        .header-icon {
            width: 50px;
            height: 50px;
            background: var(--soft-blue);
            color: var(--primary-blue);
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            font-size: 1.5rem;
        }

        /* CARD & TABLE STYLE */
        .main-card {
            border: none;
            border-radius: 20px;
            background: var(--glass-white);
            backdrop-filter: blur(10px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            overflow: hidden;
        }

        .table thead {
            background: rgba(0, 97, 255, 0.05);
        }

        .table thead th {
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
            color: var(--navy);
            border: none;
            padding: 1.2rem;
        }

        .table tbody tr {
            transition: all 0.3s ease;
            cursor: default;
        }

        .table tbody tr:hover {
            background-color: white !important;
            transform: translateY(-3px) scale(1.01);
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            z-index: 2;
        }

        .table td {
            padding: 1.2rem;
            border-bottom: 1px solid rgba(0,0,0,0.03);
            vertical-align: middle;
        }

        /* BADGE STYLING */
        .status-pill {
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-weight: 500;
            font-size: 0.85rem;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .status-ready { background: #e0f2fe; color: #0369a1; }
        .status-shipping { background: #fef3c7; color: #92400e; }
        .status-completed { background: #dcfce7; color: #166534; }
        .status-none { background: #f1f5f9; color: #475569; }

        /* ACTION BUTTONS */
        .btn-action {
            border-radius: 10px;
            padding: 0.5rem 1.2rem;
            font-weight: 500;
            transition: all 0.3s;
        }

        .btn-create {
            background: var(--primary-blue);
            color: white;
            border: none;
            box-shadow: 0 4px 10px rgba(0, 97, 255, 0.2);
        }

        .btn-create:hover {
            background: var(--navy);
            transform: translateY(-2px);
            color: white;
        }

        .btn-view {
            border: 2px solid var(--soft-blue);
            color: var(--primary-blue);
            background: transparent;
        }

        .btn-view:hover {
            background: var(--soft-blue);
            border-color: var(--primary-blue);
        }

        /* DATE STYLE */
        .date-box {
            display: flex;
            flex-direction: column;
        }
        .date-main { font-weight: 600; color: #334155; }
        .date-sub { font-size: 0.75rem; color: #94a3b8; }

        /* EMPTY STATE */
        .empty-state {
            padding: 4rem 2rem;
            text-align: center;
        }
        .empty-state i {
            font-size: 4rem;
            color: var(--soft-blue);
            margin-bottom: 1.5rem;
        }
    </style>
</head>

<body>

<div class="page-header mb-5">
    <div class="container">
        <div class="d-flex align-items-center gap-3">
            <div class="header-icon pulse">
                <i class="bi bi-truck"></i>
            </div>
            <div>
                <h2 class="fw-bold mb-0">งานจัดส่งของร้าน</h2>
                <p class="text-muted mb-0">จัดการและติดตามสถานะการรับ-ส่งผ้าของลูกค้า</p>
            </div>
        </div>
    </div>
</div>

<div class="container">
    <div class="card main-card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th width="80">#</th>
                            <th>หมายเลขออเดอร์</th>
                            <th>สถานะงานซัก</th>
                            <th>สถานะจัดส่ง</th>
                            <th>เวลานัดรับ/ส่ง</th>
                            <th class="text-end">การจัดการ</th>
                        </tr>
                    </thead>
                    <tbody>

                    <?php if (empty($orders)): ?>
                        <tr>
                            <td colspan="6">
                                <div class="empty-state">
                                    <i class="bi bi-inbox"></i>
                                    <h4>ยังไม่มีออเดอร์ในขณะนี้</h4>
                                    <p class="text-muted">เมื่อมีลูกค้าสั่งงาน รายการจะมาปรากฏที่นี่</p>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>

                        <?php foreach ($orders as $i => $o): ?>
                        <tr>
                            <td class="text-muted fw-bold"><?= sprintf('%02d', $i + 1) ?></td>
                            <td>
                                <div class="fw-bold text-primary">
                                    <i class="bi bi-hash"></i><?= htmlspecialchars($o['order_number']) ?>
                                </div>
                                <small class="text-muted">สร้างเมื่อ: <?= date('d/m/Y', strtotime($o['created_at'])) ?></small>
                            </td>

                            <td>
                                <span class="status-pill status-ready">
                                    <i class="bi bi-info-circle"></i> <?= $o['order_status'] ?>
                                </span>
                            </td>

                            <td>
                                <?php if ($o['pickup_id']): ?>
                                    <span class="status-pill status-shipping" style="background: #e0f2fe; color: #0369a1;">
                                        <i class="bi bi-box-seam"></i> <?= $o['pickup_status'] ?>
                                    </span>
                                <?php else: ?>
                                    <span class="status-pill status-none">
                                        <i class="bi bi-hourglass"></i> ยังไม่เริ่ม
                                    </span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?php if($o['scheduled_at']): ?>
                                    <div class="date-box">
                                        <span class="date-main"><i class="bi bi-calendar3 me-2 text-primary"></i><?= date('d M Y', strtotime($o['scheduled_at'])) ?></span>
                                        <span class="date-sub"><i class="bi bi-clock me-2"></i><?= date('H:i', strtotime($o['scheduled_at'])) ?> น.</span>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted opacity-50">-</span>
                                <?php endif; ?>
                            </td>

                            <td class="text-end">
                                <?php if (!$o['pickup_id']): ?>
                                    <a href="menu/delivery/delivery_create.php?order_id=<?= $o['order_id'] ?>"
                                       class="btn btn-action btn-create btn-sm">
                                        <i class="bi bi-plus-lg me-1"></i> สร้างงานจัดส่ง
                                    </a>
                                <?php else: ?>
                                    <a href="menu/delivery/delivery_view.php?id=<?= $o['pickup_id'] ?>"
                                       class="btn btn-action btn-view btn-sm">
                                        <i class="bi bi-eye me-1"></i> รายละเอียด
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>

                    <?php endif; ?>

                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="../../../bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>