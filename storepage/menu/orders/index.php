<?php


// สมมติว่ามี store_id อยู่ใน session
$store_id = $_SESSION['store_id'] ?? null;
if (!$store_id) {
    die('ไม่พบข้อมูลร้านค้า');
}

/* ---------- UPDATE STATUS ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'])) {
    $order_id = $_POST['order_id'];
    $status = $_POST['status'];

    $stmt = $pdo->prepare("
        UPDATE orders 
        SET status = ?
        WHERE id = ? AND store_id = ?
    ");
    $stmt->execute([$status, $order_id, $store_id]);

    header("Location: index.php");
    exit;
}

/* ---------- FETCH ORDERS ---------- */
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

    <h3 class="mb-3">📦 Orders ของร้าน</h3>

    <div class="card shadow-sm">
        <div class="card-body">

            <table class="table table-hover align-middle">
                <thead class="table-primary">
                    <tr>
                        <th>#</th>
                        <th>Order</th>
                        <th>ยอดรวม</th>
                        <th>สถานะ</th>
                        <th>อัปเดตสถานะ</th>
                        <th>วันที่สั่ง</th>
                    </tr>
                </thead>
                <tbody>

                <?php if (empty($orders)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted">
                            ยังไม่มีรายการสั่งซื้อ
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($orders as $i => $o): ?>
                    <tr>
                        <td><?= $i+1 ?></td>
                        <td><?= htmlspecialchars($o['order_number']) ?></td>
                        <td><?= number_format($o['total_amount'], 2) ?> ฿</td>

                        <td>
                            <?php
                            $badge = match($o['status']) {
                                'created' => 'secondary',
                                'picked_up' => 'info',
                                'in_process' => 'warning',
                                'ready' => 'primary',
                                'out_for_delivery' => 'dark',
                                'completed' => 'success',
                                'cancelled' => 'danger',
                                default => 'secondary'
                            };
                            ?>
                            <span class="badge bg-<?= $badge ?>">
                                <?= $o['status'] ?>
                            </span>
                        </td>

                        <td>
                            <form method="POST" class="d-flex gap-2">
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
                                        <option value="<?= $s ?>" <?= $o['status']==$s?'selected':'' ?>>
                                            <?= $s ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button class="btn btn-sm btn-primary">อัปเดต</button>
                            </form>
                        </td>

                        <td><?= date('d/m/Y H:i', strtotime($o['created_at'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>

                </tbody>
            </table>

        </div>
    </div>
</div>
