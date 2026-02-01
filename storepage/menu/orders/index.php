<?php
if (
    !isset($_SESSION['user_id']) ||
    !in_array($_SESSION['role'], ['store_owner', 'staff']) ||
    !isset($_SESSION['store_id'])
) {
    die('no permission');
}

$user_id  = $_SESSION['user_id'];
$store_id = $_SESSION['store_id'];

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
    WHERE o.store_id = :store_id
    ORDER BY o.created_at DESC
");
$stmt->execute([':store_id' => $_SESSION['store_id']]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

function status_badge($s)
{
    return match ($s) {
        'created'          => 'secondary',
        'picked_up'        => 'info',
        'in_process'       => 'warning',
        'ready'            => 'primary',
        'out_for_delivery' => 'dark',
        'completed'        => 'success',
        'cancelled'        => 'danger',
        default            => 'secondary'
    };
}
function status_label($s)
{
    return match ($s) {
        'created'          => 'สร้างงาน',
        'picked_up'        => 'รับผ้าแล้ว',
        'in_process'       => 'กำลังซัก',
        'ready'            => 'พร้อมส่ง',
        'out_for_delivery' => 'กำลังจัดส่ง',
        'completed'        => 'เสร็จสิ้น',
        'cancelled'        => 'ยกเลิก',
        default            => '-'
    };
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>งานซักของร้าน | Laundry Pro Management</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="../../../bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600&display=swap" rel="stylesheet">

    <style>
        :root {
            --bg-main: #f0f4f8;
            --primary-blue: #1e3c72;
            --accent-blue: #3a7bd5;
            --text-dark: #2d3436;
            --glass-white: rgba(255, 255, 255, 0.9);
        }

        body {
            background: var(--bg-main);
            font-family: 'Kanit', sans-serif;
            color: var(--text-dark);
            letter-spacing: 0.2px;
        }

        /* Header Section */
        .page-header h4 {
            color: var(--primary-blue);
            position: relative;
            display: inline-block;
        }

        .page-header h4::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 40%;
            height: 3px;
            background: var(--accent-blue);
            border-radius: 10px;
        }

        /* DASHBOARD STATS */
        .stat-box {
            border: none;
            border-radius: 20px;
            padding: 24px;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.6);
        }

        .stat-box:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 30px rgba(30, 60, 114, 0.15);
        }

        .stat-box i {
            position: absolute;
            right: -10px;
            bottom: -10px;
            font-size: 5rem;
            opacity: 0.15;
            transform: rotate(-15deg);
        }

        .stat-title {
            font-size: 0.9rem;
            font-weight: 400;
            margin-bottom: 5px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .stat-value {
            font-size: 2.2rem;
            font-weight: 700;
            line-height: 1;
        }

        /* Stat Colors - Blue Tones */
        .stat-created {
            background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
            color: #fff;
        }

        .stat-process {
            background: linear-gradient(135deg, #2193b0 0%, #6dd5ed 100%);
            color: #fff;
        }

        .stat-ready {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: #fff;
        }

        .stat-done {
            background: linear-gradient(135deg, #0f2027 0%, #2c5364 100%);
            color: #fff;
        }

        /* TABLE DESIGN */
        .card-container {
            background: var(--glass-white);
            border-radius: 24px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.8);
            overflow: hidden;
            backdrop-filter: blur(10px);
        }

        .table {
            margin-bottom: 0;
        }

        .table thead th {
            background: rgba(241, 245, 249, 0.6);
            border-bottom: none;
            padding: 20px;
            font-size: 0.85rem;
            color: #64748b;
            text-transform: uppercase;
        }

        .table tbody tr {
            border-bottom: 1px solid #edf2f7;
            transition: all 0.2s;
        }

        .table tbody tr:hover {
            background-color: #f8fbff !important;
            transform: scale(1.002);
            box-shadow: inset 4px 0 0 var(--accent-blue);
        }

        .order-id {
            background: #eef2ff;
            color: #4338ca;
            padding: 4px 10px;
            border-radius: 8px;
            font-family: 'Monaco', monospace;
            font-size: 0.9rem;
        }

        /* BADGES */
        .badge {
            padding: 8px 14px;
            border-radius: 10px;
            font-weight: 500;
            font-size: 0.75rem;
        }

        /* PULSE EFFECT for new orders */
        tr[data-status="created"] .badge {
            animation: pulse-blue 2s infinite;
        }

        @keyframes pulse-blue {
            0% {
                box-shadow: 0 0 0 0 rgba(107, 124, 179, 0.4);
            }

            70% {
                box-shadow: 0 0 0 10px rgba(107, 124, 179, 0);
            }

            100% {
                box-shadow: 0 0 0 0 rgba(107, 124, 179, 0);
            }
        }

        .btn-manage {
            border-radius: 12px;
            padding: 6px 16px;
            font-weight: 500;
            transition: all 0.3s;
            border: 2px solid #e2e8f0;
        }

        .btn-manage:hover {
            background: var(--primary-blue);
            border-color: var(--primary-blue);
            color: #fff;
            transform: translateX(-3px);
        }

        /* Soft Tooltip-like date */
        .date-text {
            font-size: 0.85rem;
            color: #64748b;
        }

        /* Row Hover Animation Delay */
        <?php for ($i = 1; $i <= 20; $i++): ?>tr:nth-child(<?= $i ?>) {
            animation: fadeInUp 0.5s ease forwards <?= $i * 0.05 ?>s;
            opacity: 0;
        }

        <?php endfor; ?>@keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>

<body>
    <div class="container py-5">

        <div class="page-header mb-4">
            <div>
                <h4 class="fw-bold mb-1"><i class="bi bi-layers-fill me-2"></i>งานซักของร้าน</h4>
                <p class="text-muted small">ติดตามและจัดการรายการผ้าของลูกค้าแบบ Real-time</p>
            </div>
        </div>

        <div class="row g-4 mb-5">
            <div class="col-6 col-md-3">
                <div class="stat-box stat-created">
                    <div class="stat-title">งานใหม่</div>
                    <div class="stat-value" id="count-created">0</div>
                    <i class="bi bi-stars"></i>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-box stat-process">
                    <div class="stat-title">กำลังดำเนินการ</div>
                    <div class="stat-value" id="count-process">0</div>
                    <i class="bi bi-water"></i>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-box stat-ready">
                    <div class="stat-title">รอรับผ้า</div>
                    <div class="stat-value" id="count-ready">0</div>
                    <i class="bi bi-box-seam"></i>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-box stat-done">
                    <div class="stat-title">สำเร็จแล้ว</div>
                    <div class="stat-value" id="count-done">0</div>
                    <i class="bi bi-check2-circle"></i>
                </div>
            </div>
        </div>

        <div class="card-container">
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th class="ps-4">#</th>
                            <th>เลขที่ใบงาน</th>
                            <th>ข้อมูลลูกค้า</th>
                            <th>สถานะปัจจุบัน</th>
                            <th>วัน-เวลาที่รับ</th>
                            <th class="text-end pe-4">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $i => $o): ?>
                            <tr data-status="<?= $o['status'] ?>">
                                <td class="ps-4 text-muted small"><?= $i + 1 ?></td>
                                <td>
                                    <span class="order-id">
                                        <i class="bi bi-hash small"></i><?= htmlspecialchars($o['order_number']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="fw-medium text-dark"><?= htmlspecialchars($o['customer_name'] ?? '-') ?></div>
                                    <div class="text-muted" style="font-size: 0.75rem;">Customer</div>
                                </td>
                                <td>
                                    <span class="badge bg-<?= status_badge($o['status']) ?>">
                                        <i class="bi bi-dot"></i> <?= status_label($o['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="date-text">
                                        <i class="bi bi-calendar3 me-1 small"></i>
                                        <?= date('d M Y', strtotime($o['created_at'])) ?>
                                        <div class="small opacity-75"><?= date('H:i', strtotime($o['created_at'])) ?> น.</div>
                                    </div>
                                </td>
                                <td class="text-end pe-4">
                                    <a href="menu/orders/detail.php?id=<?= $o['id'] ?>" class="btn btn-sm btn-manage text-primary">
                                        <i class="bi bi-pencil-square me-1"></i> รายละเอียด
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if (empty($orders)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-inbox text-muted display-1"></i>
                    <p class="mt-3 text-muted">ยังไม่มีรายการงานซักในขณะนี้</p>
                </div>
            <?php endif; ?>
        </div>

    </div>

    <script src="../../../bootstrap/js/bootstrap.bundle.min.js"></script>

    <script>
        // Logic นับจำนวน (ห้ามแตะ แต่เพิ่มลูกเล่นการนับแบบอนิเมชั่นได้)
        function animateCount(id, target) {
            let current = 0;
            let obj = document.getElementById(id);
            let step = Math.ceil(target / 20); // แบ่งเป็น 20 ขั้น
            if (target == 0) return;

            let timer = setInterval(() => {
                current += step;
                if (current >= target) {
                    obj.innerText = target;
                    clearInterval(timer);
                } else {
                    obj.innerText = current;
                }
            }, 30);
        }

        let created = 0,
            process = 0,
            ready = 0,
            done = 0;
        document.querySelectorAll("tr[data-status]").forEach(r => {
            const s = r.dataset.status;
            if (s === "created") created++;
            if (s === "picked_up" || s === "in_process") process++;
            if (s === "ready") ready++;
            if (s === "completed") done++;
        });

        // เรียกใช้อนิเมชั่นตัวเลข
        animateCount("count-created", created);
        animateCount("count-process", process);
        animateCount("count-ready", ready);
        animateCount("count-done", done);
    </script>

</body>

</html>