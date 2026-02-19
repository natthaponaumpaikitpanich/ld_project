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

/* ========= 2. POST : กำหนดราคาและรับงาน (New Logic) ========= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['set_price_and_accept'])) {
    $total_amount = $_POST['total_amount'] ?? 0;

    if ($total_amount <= 0) {
        die("กรุณาระบุจำนวนเงินที่ถูกต้อง");
    }

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("UPDATE orders SET total_amount = ?, status = 'picked_up' WHERE id = ?");
        $stmt->execute([$total_amount, $order_id]);

        $pdo->prepare("UPDATE pickups SET status = 'picked_up' WHERE order_id = ?")->execute([$order_id]);

        $pdo->prepare("INSERT INTO order_status_logs (id, order_id, status, changed_by) VALUES (UUID(), ?, 'picked_up', ?)")
            ->execute([$order_id, $user_id]);

        $pdo->commit();
        header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $order_id);
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        die("Error: " . $e->getMessage());
    }
}

/* ========= 3. POST : บันทึกการชำระเงิน (ยืนยันรายการที่ลูกค้าส่งมา) ========= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['process_payment'])) {
    $amount_paid = $_POST['amount_fixed'] ?? 0;
    $pay_method  = $_POST['payment_method'] ?? 'cash';
    $db_method   = ($pay_method === 'qr_promptpay') ? 'promptpay' : 'cash';

    $pdo->beginTransaction();
    try {
        // อัปเดตรายการ Payment ล่าสุดให้เป็น confirmed
        $stmt = $pdo->prepare("UPDATE payments SET status = 'confirmed', confirmed_by = ?, confirmed_at = NOW() WHERE order_id = ? AND status = 'pending' ORDER BY created_at DESC LIMIT 1");
        $stmt->execute([$user_id, $order_id]);

        // อัปเดตสถานะใน orders
        $pdo->prepare("UPDATE orders SET payment_status='paid' WHERE id=?")->execute([$order_id]);

        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        die("Error: " . $e->getMessage());
    }
    header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $order_id);
    exit;
}

/* ========= 4. POST : เปลี่ยนสถานะงานทั่วไป ========= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['next_status'])) {
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
    header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $order_id);
    exit;
}

/* ========= 5. FETCH DATA ========= */
$stmt = $pdo->prepare("
    SELECT o.*, u.display_name AS customer_name, s.promptpay_number, s.qr_image
    FROM orders o
    JOIN users u ON u.id = o.customer_id
    JOIN stores s ON s.id = o.store_id
    LEFT JOIN store_staff ss ON ss.store_id = o.store_id AND ss.user_id = :u1
    WHERE o.id = :oid AND (s.owner_id = :u2 OR ss.user_id IS NOT NULL)
");
$stmt->execute([':oid' => $order_id, ':u1' => $user_id, ':u2' => $user_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) die('Order not found');

$stmt = $pdo->prepare("SELECT * FROM pickups WHERE order_id=? LIMIT 1");
$stmt->execute([$order_id]);
$pickup = $stmt->fetch(PDO::FETCH_ASSOC);

// แก้ไขตรงนี้: เอา status='confirmed' ออก เพื่อให้ดึงข้อมูลที่ลูกค้าเลือก (pending) มาโชว์ได้
$stmt = $pdo->prepare("SELECT * FROM payments WHERE order_id=? ORDER BY created_at DESC LIMIT 1");
$stmt->execute([$order_id]);
$payment = $stmt->fetch(PDO::FETCH_ASSOC);

/* ========= 6. HELPERS ========= */
function label($s)
{
    return match ($s) {
        'created' => 'รอรับงาน',
        'picked_up' => 'รับผ้าแล้ว',
        'in_process' => 'กำลังซัก',
        'ready' => 'ซักเสร็จ',
        'out_for_delivery' => 'กำลังนำส่ง',
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
    <title>Manage Order #<?= $order['order_number'] ?></title>
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
            padding-bottom: 120px;
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
        }

        .active-step .status-dot {
            border-color: #fff;
            background: var(--accent-blue);
            transform: scale(1.2);
            box-shadow: 0 0 15px rgba(42, 82, 152, 0.4);
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

        .action-bar {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(255, 255, 255, 0.98);
            padding: 20px 0;
            border-top: 1px solid #eee;
            z-index: 1000;
            box-shadow: 0 -5px 20px rgba(0, 0, 0, 0.05);
        }

        .btn-nav-map {
            background: #ff4757;
            color: white;
            border: none;
            transition: 0.3s;
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
    </style>
</head>

<body>

    <div class="container py-4">
        <div class="card mb-4">
            <div class="card-header-custom text-center">
                <p class="mb-1 opacity-75">หมายเลขคำสั่งซื้อ</p>
                <h2 class="fw-bold mb-0">#<?= htmlspecialchars($order['order_number']) ?></h2>
            </div>
            <div class="card-body px-3 py-4">
                <div class="status-container">
                    <?php
                    $steps = ['created', 'picked_up', 'in_process', 'ready', 'out_for_delivery', 'completed'];
                    foreach ($steps as $idx => $s):
                        $curr_idx = array_search($order['status'], $steps);
                        $cls = ($idx == $curr_idx) ? 'active-step' : (($idx < $curr_idx) ? 'done-step' : 'wait-step');
                    ?>
                        <div class="step-item <?= $cls ?>">
                            <div class="status-dot">
                                <?php if ($idx < $curr_idx): ?><i class="bi bi-check text-white"></i>
                                <?php else: ?><i class="bi bi-circle-fill" style="font-size:8px"></i><?php endif; ?>
                            </div>
                            <div class="step-label text-center"><?= label($s) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="fw-bold mb-4"><i class="bi bi-person-circle me-2 text-primary"></i>ข้อมูลลูกค้า</h5>
                        <div class="mb-4">
                            <label class="small text-muted d-block">ชื่อลูกค้า</label>
                            <span class="fs-5 fw-medium"><?= htmlspecialchars($order['customer_name']) ?></span>
                        </div>
                        <div class="p-3 bg-light rounded-4 mb-4">
                            <label class="small text-muted d-block mb-1"><i class="bi bi-geo-alt"></i> ที่อยู่:</label>
                            <p class="small mb-0 text-dark"><?= nl2br(htmlspecialchars($pickup['pickup_address'] ?? 'ไม่ได้ระบุที่อยู่')) ?></p>
                        </div>
                        <?php if ($pickup): ?>
                            <button type="button" class="btn btn-nav-map w-100 py-3 rounded-4 fw-bold shadow-sm"
                                onclick="openMaps(<?= $pickup['lat'] ?? 'null' ?>, <?= $pickup['lng'] ?? 'null' ?>, '<?= addslashes($pickup['pickup_address']) ?>')">
                                <i class="bi bi-send-fill me-2"></i> เปิด Google Maps นำทาง
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="fw-bold mb-4"><i class="bi bi-wallet2 me-2 text-primary"></i>สถานะการชำระเงิน</h5>

                        <?php if ($order['payment_status'] === 'paid'): ?>
                            <div class="payment-status-box payment-paid text-center py-4">
                                <i class="bi bi-check-circle-fill fs-1 mb-2"></i>
                                <h4 class="fw-bold mb-0">ชำระเงินเรียบร้อย</h4>
                                <p class="mb-0">ยอดเงิน: ฿<?= number_format($order['total_amount'], 2) ?></p>
                            </div>

                        <?php elseif (!$payment): ?>
                            <div class="text-center py-5">
                                <i class="bi bi-hourglass-split display-4 text-muted"></i>
                                <p class="text-muted mt-2">ลูกค้ายืนยังไม่ได้เลือกวิธีชำระเงิน</p>
                            </div>

                        <?php elseif ($payment['method'] === 'cash'): ?>
                            <div class="payment-status-box border-warning text-center py-4" style="background-color: #fffbef; border: 2px dashed #ffc107;">
                                <i class="bi bi-truck fs-1 mb-2 text-warning"></i>
                                <h4 class="fw-bold text-dark">ลูกค้าเลือกเก็บเงินปลายทาง</h4>
                                <p class="fs-5 text-success fw-bold mb-3">" ไปส่งผ้าโลด! "</p>
                                <div class="badge bg-warning text-dark px-3 py-2">ยอดต้องเก็บ: ฿<?= number_format($order['total_amount'], 2) ?></div>

                                <form method="post" class="mt-3">
                                    <input type="hidden" name="amount_fixed" value="<?= $order['total_amount'] ?>">
                                    <input type="hidden" name="payment_method" value="cash">
                                    <button type="submit" name="process_payment" class="btn btn-success btn-sm w-100 rounded-pill shadow-sm">ยืนยันว่าได้รับเงินสดแล้ว</button>
                                </form>
                            </div>

                        <?php elseif ($payment['method'] === 'promptpay'): ?>
                            <div class="payment-status-box border-primary text-center py-3" style="border: 2px dashed #0d6efd;">
                                <h5 class="fw-bold text-primary mb-3">ลูกค้าแจ้งโอนเงินแล้ว</h5>
                                <?php if (!empty($payment['note'])): ?>
                                    <div class="mb-3">
                                        <label class="small text-muted d-block mb-2">หลักฐานการโอน:</label>
                                        <a href="../../../uploads/slips/<?= $payment['note'] ?>" target="_blank">
                                            <img src="../../../uploads/slips/<?= $payment['note'] ?>"
                                                class="img-fluid rounded-3 shadow-sm border"
                                                style="max-height: 200px; object-fit: contain;">
                                        </a>
                                    </div>
                                <?php endif; ?>
                                <div class="alert alert-info small py-2">ยอดโอน: ฿<?= number_format($payment['amount'], 2) ?></div>
                                <form method="post">
                                    <input type="hidden" name="amount_fixed" value="<?= $order['total_amount'] ?>">
                                    <input type="hidden" name="payment_method" value="qr_promptpay">
                                    <button type="submit" name="process_payment" class="btn btn-primary w-100 fw-bold rounded-pill">ยืนยันยอดเงินและรับชำระ</button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="action-bar">
        <div class="container d-flex justify-content-between align-items-center px-4">
            <a href="../../index.php?link=Home" class="btn btn-light border px-4 rounded-pill"><i class="bi bi-house"></i></a>
            <?php if ($order['status'] === 'created'): ?>
                <form method="post" class="d-flex gap-2">
                    <div class="input-group" style="width: 180px;">
                        <span class="input-group-text bg-white">฿</span>
                        <input type="number" name="total_amount" class="form-control fw-bold" placeholder="ราคา" required>
                    </div>
                    <button type="submit" name="set_price_and_accept" class="btn btn-warning px-4 fw-bold rounded-pill shadow">รับงาน & ลงราคา</button>
                </form>
            <?php elseif ($next = next_status($order['status'])): ?>
                <form method="post">
                    <input type="hidden" name="next_status" value="<?= $next ?>">
                    <button type="submit" class="btn btn-primary px-5 py-2 fw-bold rounded-pill shadow">
                        ขั้นตอนถัดไป: <?= label($next) ?> <i class="bi bi-arrow-right ms-2"></i>
                    </button>
                </form>
            <?php else: ?>
                <span class="text-success fw-bold"><i class="bi bi-check-all"></i> งานนี้เสร็จสมบูรณ์แล้ว</span>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function openMaps(lat, lng, addr) {
            let url = (lat && lng && lat !== 0) ?
                `https://www.google.com/maps?q=${lat},${lng}` :
                `https://www.google.com/maps?q=${encodeURIComponent(addr)}`;
            window.open(url, '_blank');
        }
    </script>
</body>

</html>