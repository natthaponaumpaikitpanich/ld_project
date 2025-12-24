<?php
session_start();
require_once "../ld_db.php"; // PDO

// --------------------
// ตรวจสิทธิ์
// --------------------
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    die('ไม่มีสิทธิ์เข้าถึงหน้านี้');
}

$staff_id = $_SESSION['user_id'];

/* --------------------
   UPDATE PICKUP STATUS
-------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pickup_id'])) {

    $pickup_id = $_POST['pickup_id'];
    $status    = $_POST['status'];

    $stmt = $pdo->prepare("
        UPDATE pickups
        SET status = ?, 
            completed_at = IF(? = 'completed', NOW(), completed_at)
        WHERE id = ?
    ");
    $stmt->execute([$status, $status, $pickup_id]);

    header("Location: delivery_index.php");
    exit;
}

/* --------------------
   FETCH DELIVERY JOBS
-------------------- */
$stmt = $pdo->prepare("
    SELECT 
        p.id            AS pickup_id,
        p.status        AS pickup_status,
        p.scheduled_at,
        p.pickup_address,
        o.id            AS order_id,
        o.order_number,
        o.status        AS order_status,
        o.created_at
    FROM pickups p
    JOIN orders o ON p.order_id = o.id
    ORDER BY p.created_at DESC
");

$stmt->execute();
$pickups = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>งานจัดส่ง / รับผ้า</title>
    <link href="../bootstrap/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<div class="container mt-4">

    <h3 class="mb-3">🚚 งานจัดส่ง / รับผ้า (Staff)</h3>

    <div class="card shadow-sm">
        <div class="card-body">

            <table class="table table-hover align-middle">
                <thead class="table-primary">
                    <tr>
                        <th>#</th>
                        <th>Order</th>
                        <th>สถานะออเดอร์</th>
                        <th>ที่อยู่</th>
                        <th>เวลานัด</th>
                        <th>สถานะจัดส่ง</th>
                        <th>จัดการ</th>
                    </tr>
                </thead>
                <tbody>

                <?php if (empty($pickups)): ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted">
                            ยังไม่มีงานจัดส่ง
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($pickups as $i => $p): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>

                        <td>
                            <strong><?= htmlspecialchars($p['order_number']) ?></strong><br>
                            <small class="text-muted">
                                <?= date('d/m/Y H:i', strtotime($p['created_at'])) ?>
                            </small>
                        </td>

                        <td>
                            <span class="badge bg-info">
                                <?= $p['order_status'] ?>
                            </span>
                        </td>

                        <td><?= nl2br(htmlspecialchars($p['pickup_address'])) ?></td>

                        <td>
                            <?= $p['scheduled_at']
                                ? date('d/m/Y H:i', strtotime($p['scheduled_at']))
                                : '-' ?>
                        </td>

                        <td>
                            <?php
                            $badge = match($p['pickup_status']) {
                                'scheduled'   => 'secondary',
                                'in_progress' => 'warning',
                                'completed'   => 'success',
                                'cancelled'   => 'danger',
                                default       => 'secondary'
                            };
                            ?>
                            <span class="badge bg-<?= $badge ?>">
                                <?= $p['pickup_status'] ?>
                            </span>
                        </td>

                        <td>
                            <form method="POST" class="d-flex gap-2">
                                <input type="hidden" name="pickup_id" value="<?= $p['pickup_id'] ?>">
                                <select name="status" class="form-select form-select-sm">
                                    <?php
                                    $statuses = ['scheduled','in_progress','completed','cancelled'];
                                    foreach ($statuses as $s):
                                    ?>
                                        <option value="<?= $s ?>"
                                            <?= $p['pickup_status'] === $s ? 'selected' : '' ?>>
                                            <?= $s ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button class="btn btn-sm btn-primary">
                                    อัปเดต
                                </button>
                            </form>
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
</html>
                                      