<?php
// PHP เดิมทั้งหมด (ห้ามยุ่ง)
require_once "../../ld_db.php";

$sql = "
SELECT
    s.id   AS store_id,
    s.name AS store_name,
    s.phone,
    s.address,
    s.created_at AS store_created,

    ss.id          AS sub_id,
    ss.plan        AS plan_name,
    ss.monthly_fee AS plan_price,
    ss.status      AS sub_status,
    ss.slip_image,
    ss.created_at AS sub_created

FROM stores s
LEFT JOIN store_subscriptions ss
    ON ss.store_id = s.id
    AND ss.id = (
        SELECT ss2.id
        FROM store_subscriptions ss2
        WHERE ss2.store_id = s.id
        ORDER BY ss2.created_at DESC
        LIMIT 1
    )
ORDER BY s.created_at DESC
";

$stores = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
?>

<!doctype html>
<html lang="th">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบจัดการร้านค้า | Modern Glassmorphism</title>

    <link href="/ld_project/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@200;300;400;500;600&display=swap" rel="stylesheet">

    <style>
        :root {
            /* Master Theme Colors */
            --primary-gradient: linear-gradient(135deg, #4361ee 0%, #3f37c9 100%);
            --bg-body: #f0f2f5;
            --glass-bg: rgba(255, 255, 255, 0.7);
            --glass-border: rgba(255, 255, 255, 0.3);
            --card-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.07);

            /* Action Colors */
            --edit-color: #4361ee;
            --delete-color: #ef233c;
            --success-color: #4cc9f0;
        }

   
        /* Hero Header with Master Gradient */
        .page-header {
            background: var(--primary-gradient);
            padding: 60px 0 120px 0;
            color: white;
            margin-bottom: -80px;
        }

        .breadcrumb-custom {
            font-size: 0.85rem;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(5px);
            display: inline-block;
            padding: 5px 20px;
            border-radius: 50px;
            margin-bottom: 15px;
            border: 1px solid var(--glass-border);
        }

        /* Modern Glassmorphism Card */
        .main-card {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-radius: 20px;
            border: 1px solid var(--glass-border);
            box-shadow: var(--card-shadow);
            overflow: hidden;
            animation: fadeIn 0.8s ease-in-out;
        }

        /* Modern Table with Hover Shadow */
        .table {
            margin-bottom: 0;
        }

        .table thead th {
            background: rgba(67, 97, 238, 0.05);
            color: #3f37c9;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            padding: 20px;
            border: none;
        }

        .table tbody tr {
            transition: all 0.3s ease;
        }

        .table tbody tr:hover {
            background: white !important;
            transform: scale(1.002);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            z-index: 1;
        }

        .table td {
            padding: 20px;
            vertical-align: middle;
            border-bottom: 1px solid rgba(0, 0, 0, 0.03);
        }

        /* Store Icon */
        .store-icon {
            width: 48px;
            height: 48px;
            background: var(--primary-gradient);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 15px;
            font-size: 1.2rem;
            box-shadow: 0 4px 10px rgba(67, 97, 238, 0.3);
        }

        /* Rounded Action Buttons */
        .btn-action {
            border-radius: 12px;
            padding: 8px 16px;
            font-size: 0.85rem;
            font-weight: 500;
            transition: 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
            border: none;
        }

        .btn-edit {
            background: rgba(67, 97, 238, 0.1);
            color: var(--edit-color);
        }

        .btn-edit:hover {
            background: var(--edit-color);
            color: white;
        }

        .btn-delete {
            background: rgba(239, 35, 60, 0.1);
            color: var(--delete-color);
        }

        .btn-delete:hover {
            background: var(--delete-color);
            color: white;
        }

        /* Status Badge Glassmorphism */
        .badge-glass {
            padding: 6px 14px;
            border-radius: 10px;
            font-weight: 500;
            font-size: 0.75rem;
            border: 1px solid rgba(255, 255, 255, 0.5);
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .text-xs {
            font-size: 0.75rem;
        }
    </style>
</head>

<body>

    <div class="page-header">
        <div class="container-fluid px-5">
            <div class="breadcrumb-custom text-white">Admin / Store Management</div>
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="fw-bold mb-1 text-white"><i class="bi bi-grid-1x2-fill me-2"></i> จัดการร้านค้าสมาชิก</h1>
                    <p class="mb-0 text-white-50">ควบคุมและตรวจสอบสถานะร้านค้าในระบบทั้งหมด</p>
                </div>
                <div class="main-card px-4 py-2 text-center" style="background: rgba(255,255,255,0.2);">
                    <div class="text-white-50 text-xs">Total Stores</div>
                    <div class="h3 mb-0 fw-bold text-white"><?= count($stores) ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid px-5 mb-5">
        <div class="main-card">
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>ข้อมูลร้านค้า</th>
                            <th>การติดต่อ</th>
                            <th>วันที่ลงทะเบียน</th>
                            <th>แพ็กเกจ</th>
                            <th>สถานะ</th>
                            <th class="text-center">จัดการ</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (empty($stores)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-5">
                                    <i class="bi bi-folder-x display-4 text-muted opacity-20"></i>
                                    <p class="text-muted mt-2">ไม่มีข้อมูลร้านค้า</p>
                                </td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach ($stores as $s): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="store-icon">
                                            <i class="bi bi-shop"></i>
                                        </div>
                                        <div>
                                            <div class="fw-bold text-dark"><?= htmlspecialchars($s['store_name']) ?></div>
                                            <div class="text-muted text-xs">
                                                <i class="bi bi-geo-alt-fill me-1"></i>
                                                <?= mb_strimwidth(htmlspecialchars($s['address']), 0, 40, "...") ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <td>
                                    <span class="fw-medium"><i class="bi bi-telephone me-2 text-primary"></i><?= htmlspecialchars($s['phone']) ?></span>
                                </td>

                                <td>
                                    <div class="text-dark small"><?= date('d M Y', strtotime($s['store_created'])) ?></div>
                                    <div class="text-muted text-xs"><?= date('H:i', strtotime($s['store_created'])) ?> น.</div>
                                </td>

                                <td>
                                    <?php if ($s['plan_name']): ?>
                                        <div class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-3">
                                            <?= htmlspecialchars($s['plan_name']) ?>
                                        </div>
                                        <div class="text-xs mt-1 fw-bold text-center">฿<?= number_format($s['plan_price']) ?></div>
                                    <?php else: ?>
                                        <span class="text-muted text-xs">Free Plan</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?php
                                    echo match ($s['sub_status']) {
                                        'waiting_approve' => '<span class="badge-glass bg-warning bg-opacity-25 text-dark border-warning border-opacity-25">รออนุมัติ</span>',
                                        'active'          => '<span class="badge-glass bg-success bg-opacity-25 text-success border-success border-opacity-25">เปิดใช้งาน</span>',
                                        'rejected'        => '<span class="badge-glass bg-danger bg-opacity-25 text-danger border-danger border-opacity-25">ถูกปฏิเสธ</span>',
                                        default           => '<span class="badge-glass bg-light text-muted">ไม่มีข้อมูล</span>'
                                    };
                                    ?>
                                </td>

                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-2">
                                        <?php if ($s['sub_status'] === 'waiting_approve'): ?>
                                            <?php if ($s['slip_image']): ?>
                                                <a href="/ld_project/<?= htmlspecialchars($s['slip_image']) ?>" target="_blank" class="btn-action btn-edit" title="ดูสลิป">
                                                    <i class="bi bi-receipt"></i>
                                                </a>
                                            <?php endif; ?>

                                            <a href="billing/approve_action.php?id=<?= $s['sub_id'] ?>" class="btn-action btn-edit" onclick="return confirm('อนุมัติการสมัคร?')">
                                                <i class="bi bi-check2-circle"></i> อนุมัติ
                                            </a>

                                            <a href="billing/approve_action.php?id=<?= $s['sub_id'] ?>" class="btn-action btn-delete" onclick="return confirm('ปฏิเสธการสมัคร?')">
                                                <i class="bi bi-x-circle"></i> ปฏิเสธ
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted text-xs">ดำเนินการแล้ว</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="/ld_project/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>

</html>