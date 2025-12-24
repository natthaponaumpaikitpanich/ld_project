<?php


// สมมติว่ามี store_id อยู่ใน session
$store_id = $_SESSION['store_id'] ?? null;
if (!$store_id) {
    die("ไม่พบข้อมูลร้าน");
}

// --------------------
// UPDATE STATUS
// --------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'], $_POST['status'])) {

    $stmt = $pdo->prepare("
        UPDATE orders
        SET status = ?
        WHERE id = ? AND store_id = ?
    ");
    $stmt->execute([
        $_POST['status'],
        $_POST['order_id'],
        $store_id
    ]);


    exit;
}

// --------------------
// FETCH ORDERS
// --------------------
$stmt = $pdo->prepare("
    SELECT *
    FROM orders
    WHERE store_id = ?
    ORDER BY created_at DESC
");
$stmt->execute([$store_id]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>


<div class="container mt-4">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4>📦 งานซักของร้าน</h4>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">

            <table class="table table-hover align-middle">
                <thead class="table-primary">
                    <tr>
                        <th>#</th>
                        <th>เลขงาน</th>
                        <th>หมายเหตุ</th>
                        <th>สถานะ</th>
                        <th>อัปเดตสถานะ</th>
                        <th>วันที่สร้าง</th>
                        <th>จัดการ</th>
                    </tr>
                </thead>
                <tbody>

                <?php if (empty($orders)): ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted">
                            ยังไม่มีงานซัก
                        </td>
                    </tr>
                <?php else: ?>

                <?php foreach ($orders as $i => $o): ?>
                    <?php
                    $badge = match($o['status']) {
                        'created'          => 'secondary',
                        'picked_up'        => 'info',
                        'in_process'       => 'warning',
                        'ready'            => 'primary',
                        'out_for_delivery' => 'dark',
                        'completed'        => 'success',
                        'cancelled'        => 'danger',
                        default            => 'secondary'
                    };
                    ?>

                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><?= htmlspecialchars($o['order_number']) ?></td>
                        <td><?= htmlspecialchars($o['notes'] ?? '-') ?></td>

                        <td>
                            <span class="badge bg-<?= $badge ?>">
                                <?= $o['status'] ?>
                            </span>
                        </td>

                        <td style="width:220px;">
                            <form method="post" class="d-flex gap-2">
                                <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                                <select name="status" class="form-select form-select-sm">
                                    <?php
                                    $statuses = [
                                        'created',
                                        'picked_up',
                                        'in_process',
                                        'ready',
                                        'out_for_delivery',
                                        'completed'
                                    ];
                                    foreach ($statuses as $s):
                                    ?>
                                        <option value="<?= $s ?>" <?= $o['status'] === $s ? 'selected' : '' ?>>
                                            <?= $s ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button class="btn btn-sm btn-primary">บันทึก</button>
                            </form>
                        </td>

                        <td>
                            <?= date('d/m/Y H:i', strtotime($o['created_at'])) ?>
                        </td>

                        <td>
                            <a href="menu/orders/order_view.php?id=<?= $o['id'] ?>"
                               class="btn btn-sm btn-outline-info">
                               ดูรายละเอียด
                            </a>
                        </td>
                    </tr>

                <?php endforeach; ?>
                <?php endif; ?>

                </tbody>
            </table>

        </div>
    </div>

</div>

<script src="../bootstrap/js/bootstrap.bundle.min.js"></script>
</body>