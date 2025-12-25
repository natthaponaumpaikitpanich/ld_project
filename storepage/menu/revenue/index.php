<?php


$store_id = $_SESSION['store_id'] ?? null;
if (!$store_id) {
    die("ไม่พบข้อมูลร้าน");
}

/* ---------- SUMMARY ---------- */

// รายได้วันนี้
$stmt = $pdo->prepare("
    SELECT SUM(total_amount) 
    FROM orders
    WHERE store_id = ?
      AND status = 'completed'
      AND DATE(created_at) = CURDATE()
");
$stmt->execute([$store_id]);
$today_revenue = $stmt->fetchColumn() ?? 0;

// รายได้เดือนนี้
$stmt = $pdo->prepare("
    SELECT SUM(total_amount) 
    FROM orders
    WHERE store_id = ?
      AND status = 'completed'
      AND MONTH(created_at) = MONTH(CURDATE())
      AND YEAR(created_at) = YEAR(CURDATE())
");
$stmt->execute([$store_id]);
$month_revenue = $stmt->fetchColumn() ?? 0;

// จำนวนออเดอร์สำเร็จ
$stmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM orders
    WHERE store_id = ?
      AND status = 'completed'
");
$stmt->execute([$store_id]);
$total_orders = $stmt->fetchColumn();

/* ---------- LIST ---------- */
$stmt = $pdo->prepare("
    SELECT order_number, total_amount, created_at
    FROM orders
    WHERE store_id = ?
      AND status = 'completed'
    ORDER BY created_at DESC
");
$stmt->execute([$store_id]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mt-4">

    <h3 class="mb-4">💰 รายได้ร้าน</h3>

    <!-- SUMMARY -->
    <div class="row g-3 mb-4">

        <div class="col-md-4">
            <div class="card p-3 shadow-sm">
                <small class="text-muted">รายได้วันนี้</small>
                <h4><?= number_format($today_revenue, 2) ?> ฿</h4>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card p-3 shadow-sm">
                <small class="text-muted">รายได้เดือนนี้</small>
                <h4><?= number_format($month_revenue, 2) ?> ฿</h4>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card p-3 shadow-sm">
                <small class="text-muted">ออเดอร์ที่สำเร็จ</small>
                <h4><?= $total_orders ?> รายการ</h4>
            </div>
        </div>

    </div>

    <!-- TABLE -->
    <div class="card shadow-sm">
        <div class="card-body">

            <table class="table table-hover align-middle">
                <thead class="table-primary">
                    <tr>
                        <th>#</th>
                        <th>เลขออเดอร์</th>
                        <th>ยอดเงิน</th>
                        <th>วันที่สำเร็จ</th>
                    </tr>
                </thead>
                <tbody>

                <?php if (empty($orders)): ?>
                    <tr>
                        <td colspan="4" class="text-center text-muted">
                            ยังไม่มีรายได้
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($orders as $i => $o): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><?= htmlspecialchars($o['order_number']) ?></td>
                            <td><?= number_format($o['total_amount'], 2) ?> ฿</td>
                            <td><?= date('d/m/Y H:i', strtotime($o['created_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>

                </tbody>
            </table>

        </div>
    </div>

</div>
