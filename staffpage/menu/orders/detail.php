<?php
session_start();
require_once "../../../ld_db.php";

/* ========= 1. AUTH & VALIDATION ========= */
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['store_owner', 'staff'])) {
    die('no permission');
}

$user_id  = $_SESSION['user_id'];
$order_id = $_GET['id'] ?? null;
if (!$order_id) die('no order');

/* ========= 2. POST : รับเงินสด (ระบุยอดเงินเองได้) ========= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cash_paid'])) {
    $amount_paid = $_POST['amount_paid'] ?? 0;

    if ($amount_paid <= 0) {
        die("<script>alert('กรุณาระบุยอดเงินที่ถูกต้อง'); window.history.back();</script>");
    }

    $pdo->beginTransaction();
    try {
        // ตรวจสอบสถานะก่อนบันทึก
        $stmt = $pdo->prepare("SELECT payment_status FROM orders WHERE id=? FOR UPDATE");
        $stmt->execute([$order_id]);
        $curr_order = $stmt->fetch();

        if ($curr_order['payment_status'] !== 'paid') {
            // 1. บันทึกยอดเงินลงตาราง payments
            $stmt = $pdo->prepare("
                INSERT INTO payments 
                (id, order_id, amount, provider, status, confirmed_by, confirmed_at, created_at) 
                VALUES (UUID(), :order_id, :amount, 'cash', 'confirmed', :user, NOW(), NOW())
            ");
            $stmt->execute([
                ':order_id' => $order_id,
                ':amount'   => $amount_paid,
                ':user'     => $user_id
            ]);

            // 2. อัปเดตสถานะในตาราง orders
            $pdo->prepare("UPDATE orders SET payment_status='paid' WHERE id=?")
                ->execute([$order_id]);

            $pdo->commit();
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        die("Error: " . $e->getMessage());
    }
    header("Location: detail.php?id=" . $order_id);
    exit;
}

/* ========= 3. POST : เปลี่ยนสถานะงาน ========= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['next_status'])) {
    $stmt = $pdo->prepare("SELECT payment_status, status FROM orders WHERE id=?");
    $stmt->execute([$order_id]);
    $chk = $stmt->fetch(PDO::FETCH_ASSOC);

    // Guard: ถ้าซักเสร็จแล้ว (ready) จะส่งของต้องจ่ายเงินก่อน
    if ($chk['status'] === 'ready' && $chk['payment_status'] !== 'paid') {
        die("<script>alert('กรุณาบันทึกการชำระเงินก่อนดำเนินการส่งคืน'); window.history.back();</script>");
    }

    $pdo->beginTransaction();
    try {
        $next = $_POST['next_status'];
        $pdo->prepare("UPDATE orders SET status=? WHERE id=?")->execute([$next, $order_id]);
        $pdo->prepare("UPDATE pickups SET status=? WHERE order_id=?")->execute([$next, $order_id]);
        $pdo->prepare("INSERT INTO order_status_logs (id,order_id,status,changed_by) VALUES (UUID(),?,?,?)")
            ->execute([$order_id, $next, $user_id]);
        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
    }
    header("Location: detail.php?id=" . $order_id);
    exit;
}

/* ========= 4. FETCH DATA ========= */
$stmt = $pdo->prepare("
    SELECT o.*, u.display_name AS customer_name 
    FROM orders o
    JOIN users u ON u.id = o.customer_id
    JOIN stores s ON s.id = o.store_id
    LEFT JOIN store_staff ss ON ss.store_id = o.store_id AND ss.user_id = :u1
    WHERE o.id = :oid AND (s.owner_id = :u2 OR ss.user_id IS NOT NULL)
");
$stmt->execute([':oid' => $order_id, ':u1' => $user_id, ':u2' => $user_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$order) die('Order not found or Access denied');

$stmt = $pdo->prepare("SELECT * FROM pickups WHERE order_id=? LIMIT 1");
$stmt->execute([$order_id]);
$pickup = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT * FROM payments WHERE order_id=? AND status='confirmed' ORDER BY created_at DESC LIMIT 1");
$stmt->execute([$order_id]);
$payment = $stmt->fetch(PDO::FETCH_ASSOC);

/* ========= 5. HELPERS ========= */
function label($s)
{
    return match ($s) {
        'created' => 'รอรับงาน',
        'picked_up' => 'รับผ้าแล้ว',
        'in_process' => 'กำลังซัก',
        'ready' => 'ซักเสร็จ',
        'out_for_delivery' => 'กำลังส่ง',
        'completed' => 'เสร็จงาน',
        default => $s
    };
}
function next_status($s)
{
    return match ($s) {
        'created' => 'picked_up',
        'picked_up' => 'in_process',
        'in_process' => 'ready',
        'ready' => 'out_for_delivery',
        'out_for_delivery' => 'completed',
        default => null
    };
}
?>
<!doctype html>
<html lang="th">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Order #<?= $order['order_number'] ?></title>
    <link href="../../../bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-blue: #1e3c72;
            --accent-blue: #2a5298;
            --success-green: #198754;
            --bg-light: #f0f4f8;
        }

        body {
            background: var(--bg-light);
            font-family: 'Kanit', sans-serif;
            padding-bottom: 100px;
        }

        .card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 10px 25px rgba(30, 60, 114, 0.05);
        }

        .card-header-custom {
            background: linear-gradient(135deg, var(--primary-blue), var(--accent-blue));
            color: white;
            padding: 25px;
            border-radius: 20px 20px 0 0;
        }

        .status-container {
            display: flex;
            justify-content: space-between;
            position: relative;
            margin: 20px 0;
        }

        .status-container::before {
            content: '';
            position: absolute;
            top: 15px;
            left: 0;
            right: 0;
            height: 4px;
            background: #e2e8f0;
            z-index: 1;
        }

        .step-item {
            position: relative;
            z-index: 2;
            display: flex;
            flex-direction: column;
            align-items: center;
            flex: 1;
        }

        .status-dot {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #fff;
            border: 4px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: 0.4s;
        }

        .active-step .status-dot {
            border-color: #fff;
            background: var(--accent-blue);
            box-shadow: 0 0 0 5px rgba(42, 82, 152, 0.2);
            transform: scale(1.2);
        }

        .done-step .status-dot {
            background: var(--success-green);
            border-color: #fff;
        }

        .step-label {
            font-size: 0.65rem;
            margin-top: 10px;
            font-weight: 500;
            color: #94a3b8;
        }

        .active-step .step-label {
            color: var(--primary-blue);
            font-weight: 600;
        }

        .payment-status-box {
            padding: 15px;
            border-radius: 15px;
            border: 2px dashed #e2e8f0;
        }

        .payment-paid {
            background: #f0fdf4;
            border: 2px solid #bbf7d0;
            color: #166534;
        }

        .action-bar {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            padding: 15px 0;
            box-shadow: 0 -10px 30px rgba(0, 0, 0, 0.08);
            z-index: 1000;
        }

        .pulse-green {
            animation: pulse-animation 2s infinite;
        }

        @keyframes pulse-animation {
            0% {
                box-shadow: 0 0 0 0 rgba(25, 135, 84, 0.4);
            }

            70% {
                box-shadow: 0 0 0 10px rgba(25, 135, 84, 0);
            }

            100% {
                box-shadow: 0 0 0 0 rgba(25, 135, 84, 0);
            }
        }
    </style>
</head>

<body>

    <div class="container py-4">
        <div class="card mb-4">
            <div class="card-header-custom">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <span class="badge bg-white text-primary mb-2">Order Management</span>
                        <h3 class="fw-bold mb-1">#<?= htmlspecialchars($order['order_number']) ?></h3>
                        <p class="mb-0 opacity-75">ลูกค้า: <?= htmlspecialchars($order['customer_name']) ?></p>
                    </div>
                    <div class="text-end text-white">
                        <h4 class="mb-0">฿<?= number_format($order['total_amount'], 2) ?></h4>
                    </div>
                </div>
            </div>
            <div class="card-body px-3 py-4">
                <div class="status-container">
                    <?php
                    $steps = ['created', 'picked_up', 'in_process', 'ready', 'out_for_delivery', 'completed'];
                    foreach ($steps as $idx => $s):
                        $curr_idx = array_search($order['status'], $steps);
                        $step_idx = $idx;
                        $cls = ($step_idx == $curr_idx) ? 'active-step' : (($step_idx < $curr_idx) ? 'done-step' : 'wait-step');
                    ?>
                        <div class="step-item <?= $cls ?>">
                            <div class="status-dot">
                                <?php if ($step_idx < $curr_idx): ?><i class="bi bi-check text-white"></i>
                                <?php else: ?><i class="bi bi-circle-fill" style="font-size:8px"></i><?php endif; ?>
                            </div>
                            <div class="step-label text-center"><?= label($s) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-body">
                        <h6 class="fw-bold mb-3"><i class="bi bi-cash-stack me-2 text-primary"></i>การชำระเงิน</h6>
                        <?php if ($order['payment_status'] === 'paid'): ?>
                            <div class="payment-status-box payment-paid text-center">
                                <i class="bi bi-check-circle-fill fs-3 mb-1 d-block"></i>
                                <div class="fw-bold">ชำระแล้ว ฿<?= number_format($payment['amount'] ?? $order['total_amount'], 2) ?></div>
                                <div class="small opacity-75">บันทึกเมื่อ: <?= date('d/m/Y H:i', strtotime($payment['confirmed_at'] ?? 'now')) ?></div>
                            </div>
                        <?php else: ?>
                            <div class="payment-status-box">
                                <form method="post" onsubmit="return confirm('ยืนยันบันทึกยอดเงินสด?')">
                                    <div class="mb-3 text-center">
                                        <label class="form-label small text-muted">ระบุยอดเงินที่รับมา</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-white border-end-0">฿</span>
                                            <input type="number" name="amount_paid" step="0.01" class="form-control form-control-lg fw-bold border-start-0 text-primary" value="<?= $order['total_amount'] ?>" required>
                                        </div>
                                    </div>
                                    <button name="cash_paid" class="btn btn-success w-100 py-2 fw-bold pulse-green">
                                        <i class="bi bi-cash me-2"></i>บันทึกรับเงินสด
                                    </button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-body">
                        <h6 class="fw-bold mb-3"><i class="bi bi-geo-alt me-2 text-primary"></i>การจัดส่ง</h6>
                        <?php if ($pickup): ?>
                            <div class="p-3 bg-light rounded-3 mb-3">
                                <p class="small mb-1 text-muted">ที่อยู่:</p>
                                <p class="small mb-0 fw-medium"><?= htmlspecialchars($pickup['pickup_address']) ?></p>
                            </div>
                            <?php if ($order['status'] === 'out_for_delivery'): ?>
                                <button class="btn btn-outline-danger w-100 py-2" onclick="openMaps(<?= $pickup['lat'] ?>, <?= $pickup['lng'] ?>, '<?= addslashes($pickup['pickup_address']) ?>')">
                                    <i class="bi bi-map me-2"></i>นำทาง Google Maps
                                </button>
                            <?php endif; ?>
                        <?php else: ?>
                            <p class="text-center text-muted py-3">ไม่มีข้อมูลที่อยู่</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="action-bar">
            <div class="container d-flex justify-content-between px-4">
                <a href="../../index.php" class="btn btn-light text-muted border"><i class="bi bi-chevron-left"></i> กลับ</a>
                <?php if ($next = next_status($order['status'])): ?>
                    <form method="post">
                        <input type="hidden" name="next_status" value="<?= $next ?>">
                        <button class="btn btn-primary px-5 fw-bold shadow-sm" style="border-radius:12px">
                            <?= label($next) ?> <i class="bi bi-arrow-right ms-2"></i>
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function openMaps(lat, lng, addr) {
            const url = (lat && lng) ?
                `https://www.google.com/maps?q=${lat},${lng}` :
                `https://www.google.com/maps?q=${encodeURIComponent(addr)}`;
            window.open(url, '_blank');
        }
    </script>

</body>

</html>