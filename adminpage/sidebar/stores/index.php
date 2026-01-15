<?php
require_once "../../ld_db.php";

$sql = "
SELECT
    s.id   AS store_id,
    s.name AS store_name,
    s.phone,
    s.address,
    s.created_at AS store_created,

    ss.id        AS sub_id,
    ss.plan      AS plan_name,
    ss.monthly_fee AS plan_price,
    ss.status    AS sub_status,
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
    <title>ร้านค้าทั้งหมด</title>
    <link href="/ld_project/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="/ld_project/adminpage/assets/style.css" rel="stylesheet">
    <link href="../assets/style.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Kanit:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
</head>

<body>
    <div class="container-fluid px-4 mt-4">

    <!-- HEADER -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h3 class="fw-bold mb-0">🏪 ร้านซักอบรีดทั้งหมด</h3>
            <small class="text-muted">
                ร้านค้าที่ลงทะเบียนและเช่าระบบในแพลตฟอร์ม
            </small>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">

            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>ร้านค้า</th>
                        <th>ติดต่อ</th>
                        <th>วันที่สมัคร</th>
                        <th>แพ็กเกจ</th>
                        <th>สถานะ</th>
                        <th class="text-end" width="260">จัดการ</th>
                    </tr>
                </thead>

                <tbody>
                <?php if (empty($stores)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">
                            ยังไม่มีร้านในระบบ
                        </td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($stores as $s): ?>
                <tr>

                    <!-- STORE -->
                    <td>
                        <div class="fw-semibold">
                            <?= htmlspecialchars($s['store_name']) ?>
                        </div>
                        <small class="text-muted">
                            <?= nl2br(htmlspecialchars($s['address'])) ?>
                        </small>
                    </td>

                    <!-- CONTACT -->
                    <td>
                        <span class="fw-semibold">
                            <?= htmlspecialchars($s['phone']) ?>
                        </span>
                    </td>

                    <!-- DATE -->
                    <td>
                        <?= date('d/m/Y', strtotime($s['store_created'])) ?>
                    </td>

                    <!-- PLAN -->
                    <td>
                    <?php if ($s['plan_name']): ?>
                        <span class="badge bg-primary mb-1">
                            <?= htmlspecialchars($s['plan_name']) ?>
                        </span><br>
                        <small class="text-muted">
                            <?= number_format($s['plan_price'],2) ?> ฿ / เดือน
                        </small>
                    <?php else: ?>
                        <span class="badge bg-secondary">
                            ยังไม่สมัครแพ็กเกจ
                        </span>
                    <?php endif; ?>
                    </td>

                    <!-- STATUS -->
                    <td>
                        <?php
                        echo match($s['sub_status']) {
                            'waiting_approve' => '<span class="badge bg-warning text-dark">รออนุมัติ</span>',
                            'active'          => '<span class="badge bg-success">ใช้งานอยู่</span>',
                            'rejected'        => '<span class="badge bg-danger">ถูกปฏิเสธ</span>',
                            'expired'         => '<span class="badge bg-dark">หมดอายุ</span>',
                            default           => '<span class="badge bg-secondary">-</span>'
                        };
                        ?>
                    </td>

                    <!-- ACTION -->
                    <td class="text-end">

                    <?php if ($s['sub_status'] === 'waiting_approve'): ?>

                        <?php if ($s['slip_image']): ?>
                            <a href="/ld_project/<?= htmlspecialchars($s['slip_image']) ?>"
                               target="_blank"
                               class="btn btn-sm btn-outline-info me-1">
                               ดูสลิป
                            </a>
                        <?php endif; ?>

                        <a href="approve.php?id=<?= $s['sub_id'] ?>"
                           class="btn btn-sm btn-success me-1"
                           onclick="return confirm('อนุมัติร้านนี้?')">
                           Approve
                        </a>

                        <a href="reject.php?id=<?= $s['sub_id'] ?>"
                           class="btn btn-sm btn-outline-danger"
                           onclick="return confirm('ปฏิเสธร้านนี้?')">
                           Reject
                        </a>

                    <?php else: ?>
                        <span class="text-muted">-</span>
                    <?php endif; ?>

                    </td>

                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

        </div>
    </div>

</div>

</body>

</html>