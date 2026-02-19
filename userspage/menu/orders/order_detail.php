<?php
session_start();
require_once "../../../ld_db.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    die('Access Denied');
}

$order_id = $_GET['id'] ?? null;
$customer_id = $_SESSION['user_id'];

/* ========= 1. FETCH INITIAL DATA ========= */
$stmt = $pdo->prepare("
    SELECT o.*, s.name AS store_name, s.promptpay_number, s.qr_image, s.address AS store_addr
    FROM orders o
    JOIN stores s ON o.store_id = s.id
    WHERE o.id = ? AND o.customer_id = ?
");
$stmt->execute([$order_id, $customer_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) die('ไม่พบข้อมูลคำสั่งซื้อ');

/* ========= 2. LOGIC การใช้ส่วนลด & ชำระเงิน ========= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // กรณีใช้ส่วนลด
    if (isset($_POST['apply_promo'])) {
        $promo_id = $_POST['promo_id'];

        $stmtP = $pdo->prepare("SELECT * FROM promotions WHERE id = ? AND status = 'active'");
        $stmtP->execute([$promo_id]);
        $promo = $stmtP->fetch();

        if ($promo) {
            try {
                $pdo->beginTransaction();

                $current_total = (float)$order['total_amount'];
                $discount = 0;

                // เช็คชื่อคอลัมน์ที่อาจจะเป็นไปได้ในตาราง promotions
                $promo_val = (float)($promo['discount_value'] ?? $promo['amount'] ?? $promo['discount'] ?? 0);

                if (($promo['discount_type'] ?? '') === 'fixed') {
                    $discount = $promo_val;
                } else {
                    $discount = ($current_total * $promo_val) / 100;
                }

                $new_total = max(0, $current_total - $discount);

                $stmtU = $pdo->prepare("UPDATE orders SET promotion_id = ?, discount_amount = ?, total_amount = ? WHERE id = ?");
                $result = $stmtU->execute([$promo_id, $discount, $new_total, $order_id]);

                if ($result) {
                    $pdo->commit();
                    echo "<script>window.location.href='order_detail.php?id=$order_id&msg=success';</script>";
                    exit;
                }
            } catch (Exception $e) {
                $pdo->rollBack();
                die("Error: " . $e->getMessage());
            }
        }
    }

    // กรณีอัปโหลดสลิป
    if (isset($_POST['upload_slip'])) {
        if (isset($_FILES['slip_image']) && $_FILES['slip_image']['error'] == 0) {
            $upload_dir = "../../../uploads/slips/";
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            $ext = pathinfo($_FILES['slip_image']['name'], PATHINFO_EXTENSION);
            $filename = "cust_slip_" . time() . "_" . uniqid() . "." . $ext;

            if (move_uploaded_file($_FILES['slip_image']['tmp_name'], $upload_dir . $filename)) {
                try {
                    $pdo->beginTransaction();
                    $stmtPay = $pdo->prepare("INSERT INTO payments (id, order_id, amount, method, status, note, created_at) VALUES (UUID(), ?, ?, 'promptpay', 'pending', ?, NOW())");
                    $stmtPay->execute([$order_id, $order['total_amount'], $filename]);
                    $pdo->commit();
                    $success_msg = "ส่งหลักฐานการโอนเรียบร้อย รอร้านตรวจสอบครับ";
                } catch (Exception $e) {
                    $pdo->rollBack();
                }
            }
        }
    }

    // กรณีเลือก COD
    if (isset($_POST['choose_cod'])) {
        try {
            $pdo->beginTransaction();
            $stmtCOD = $pdo->prepare("INSERT INTO payments (id, order_id, amount, method, status, note, created_at) VALUES (UUID(), ?, ?, 'cash', 'pending', 'เก็บเงินปลายทาง (COD)', NOW())");
            $stmtCOD->execute([$order_id, $order['total_amount']]);
            $pdo->commit();
            $success_msg = "บันทึกการเลือกเก็บเงินปลายทางเรียบร้อยครับ";
        } catch (Exception $e) {
            $pdo->rollBack();
        }
    }
}

/* ========= 3. RE-FETCH DATA FOR UI ========= */
$stmt = $pdo->prepare("SELECT o.*, s.name AS store_name, s.promptpay_number, s.qr_image FROM orders o JOIN stores s ON o.store_id = s.id WHERE o.id = ?");
$stmt->execute([$order_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

$stmtAvail = $pdo->prepare("SELECT * FROM promotions WHERE store_id = ? AND status = 'active' AND end_date >= NOW()");
$stmtAvail->execute([$order['store_id']]);
$available_promos = $stmtAvail->fetchAll();

$stmt = $pdo->prepare("SELECT * FROM payments WHERE order_id = ? ORDER BY created_at DESC LIMIT 1");
$stmt->execute([$order_id]);
$payment = $stmt->fetch();

function get_status_badge($s)
{
    return match ($s) {
        'created' => 'bg-secondary',
        'picked_up' => 'bg-info',
        'in_process' => 'bg-primary',
        'ready' => 'bg-success',
        'out_for_delivery' => 'bg-warning text-dark',
        'completed' => 'bg-dark',
        default => 'bg-light'
    };
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Detail #<?= $order['order_number'] ?></title>
    <link href="../../../bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        body {
            background-color: #f0f4f8;
            font-family: 'Kanit', sans-serif;
        }

        .detail-card {
            border: none;
            border-radius: 25px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }

        .store-banner {
            background: linear-gradient(135deg, #6c5ce7, #a29bfe);
            color: white;
            padding: 40px 20px;
        }

        .info-label {
            font-size: 0.8rem;
            color: #b2bec3;
            text-transform: uppercase;
        }

        .payment-section {
            background: #fff;
            border-radius: 25px;
            padding: 25px;
            margin-bottom: 30px;
        }

        .method-btn {
            border: 2px solid #f1f2f6;
            border-radius: 15px;
            padding: 15px;
            cursor: pointer;
            transition: 0.3s;
            width: 100%;
            background: none;
        }

        .method-btn.active {
            border-color: #6c5ce7;
            background: #f9f8ff;
            color: #6c5ce7;
        }

        .promo-btn {
            border: 2px dashed #6c5ce7;
            color: #6c5ce7;
            background: #f9f8ff;
        }
    </style>
</head>

<body>

    <div class="container py-4">
        <div class="d-flex align-items-center mb-4">
            <a href="../../index.php?link=orders" class="btn btn-white shadow-sm rounded-circle me-3"><i class="bi bi-chevron-left"></i></a>
            <h5 class="mb-0 fw-bold">ข้อมูลการสั่งซัก</h5>
        </div>

        <?php if (isset($success_msg) || isset($_GET['msg'])): ?>
            <div class="alert alert-success border-0 rounded-4 shadow-sm mb-4">
                <i class="bi bi-check-circle me-2"></i><?= $success_msg ?? 'ดำเนินการเรียบร้อยแล้ว' ?>
            </div>
        <?php endif; ?>

        <div class="card detail-card mb-4">
            <div class="store-banner text-center">
                <div class="bg-white d-inline-block p-3 rounded-circle mb-3 shadow-sm">
                    <i class="bi bi-shop fs-2 text-primary"></i>
                </div>
                <h4 class="fw-bold mb-1"><?= htmlspecialchars($order['store_name']) ?></h4>
                <p class="small mb-0 opacity-75">#<?= $order['order_number'] ?></p>
            </div>
            <div class="card-body p-4 text-center">
                <span class="info-label">สถานะปัจจุบัน</span><br>
                <span class="badge <?= get_status_badge($order['status']) ?> rounded-pill px-4 py-2 mt-2"><?= $order['status'] ?></span>
            </div>
        </div>

        <div class="payment-section shadow-sm">
            <h6 class="fw-bold mb-4"><i class="bi bi-cash-stack me-2 text-primary"></i>สรุปค่าใช้จ่าย</h6>

            <?php if ($order['total_amount'] <= 0 && empty($order['promotion_id'])): ?>
                <div class="text-center py-5 text-muted">ร้านกำลังประเมินราคาผ้าของคุณ...</div>
            <?php else: ?>

                <?php if ($order['payment_status'] != 'paid' && empty($order['promotion_id'])): ?>
                    <button class="btn promo-btn w-100 rounded-4 py-3 mb-4 fw-bold" data-bs-toggle="modal" data-bs-target="#promoModal">
                        <i class="bi bi-ticket-perforated me-2"></i> เลือกใช้ส่วนลดร้านค้า
                    </button>
                <?php endif; ?>

                <div class="p-3 bg-light rounded-4 mb-4">
                    <?php if (($order['discount_amount'] ?? 0) > 0): ?>
                        <div class="d-flex justify-content-between small text-muted mb-1">
                            <span>ราคาปกติ</span>
                            <span class="text-decoration-line-through">฿<?= number_format($order['total_amount'] + $order['discount_amount'], 2) ?></span>
                        </div>
                        <div class="d-flex justify-content-between small text-danger mb-2">
                            <span>ส่วนลดโปรโมชั่น</span>
                            <span>- ฿<?= number_format($order['discount_amount'], 2) ?></span>
                        </div>
                    <?php endif; ?>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fw-bold">ยอดสุทธิที่ต้องชำระ</span>
                        <span class="h3 mb-0 fw-bold text-primary">฿<?= number_format($order['total_amount'], 2) ?></span>
                    </div>
                </div>

                <?php if ($order['payment_status'] == 'paid'): ?>
                    <div class="alert alert-success text-center border-0 rounded-4 py-3 shadow-sm">
                        <i class="bi bi-check-circle-fill fs-4 d-block mb-2"></i><b>ชำระเงินเรียบร้อยแล้ว</b>
                    </div>
                <?php elseif ($payment && $payment['method'] == 'cash'): ?>
                    <div class="alert alert-warning border-0 rounded-4 p-3 shadow-sm text-center">
                        <i class="bi bi-truck fs-2 d-block mb-2"></i><b>เก็บเงินปลายทาง</b><br>เตรียมเงินสดจำนวน ฿<?= number_format($order['total_amount'], 2) ?>
                    </div>
                <?php elseif ($payment && $payment['status'] == 'pending'): ?>
                    <div class="alert alert-info text-center border-0 rounded-4 shadow-sm">รอร้านค้าตรวจสอบสลิป</div>
                <?php else: ?>
                    <div class="row g-3 mb-4">
                        <div class="col-6"><button class="method-btn active"><i class="bi bi-qr-code-scan d-block fs-3 mb-1"></i><span class="small fw-bold">สแกนจ่าย</span></button></div>
                        <div class="col-6">
                            <form method="post">
                                <button type="submit" name="choose_cod" class="method-btn" onclick="return confirm('ยืนยันเก็บเงินปลายทาง?')"><i class="bi bi-truck d-block fs-3 mb-1"></i><span class="small fw-bold">จ่ายปลายทาง</span></button>
                            </form>
                        </div>
                    </div>

                    <div class="qr-card text-center mb-4 bg-light p-4 rounded-4 border-dashed">
                        <?php if (!empty($order['qr_image'])): ?>
                            <img src="../../../uploads/stores/<?= $order['qr_image'] ?>" class="img-fluid rounded-4 mb-3 shadow-sm" style="max-height: 200px;">
                        <?php endif; ?>
                        <div class="fw-bold text-dark fs-5"><?= $order['promptpay_number'] ?></div>
                        <div class="small text-muted">ชื่อบัญชี: <?= $order['store_name'] ?></div>
                    </div>

                    <form method="post" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label class="info-label mb-2">อัปโหลดสลิป</label>
                            <input type="file" name="slip_image" class="form-control rounded-3" accept="image/*" required>
                        </div>
                        <button type="submit" name="upload_slip" class="btn btn-primary w-100 py-3 rounded-4 fw-bold shadow-lg">แจ้งโอนเงิน</button>
                    </form>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="modal fade" id="promoModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-5 border-0 shadow">
                <div class="modal-header border-0 px-4 pt-4">
                    <h5 class="fw-bold mb-0">โปรโมชั่นของทางร้าน</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <?php if (empty($available_promos)): ?>
                        <p class="text-center text-muted py-3">ไม่มีโปรโมชั่นให้เลือกในขณะนี้</p>
                    <?php else: ?>
                        <?php foreach ($available_promos as $p): ?>
                            <?php
                            // เช็คค่าส่วนลดจากคอลัมน์ต่างๆ
                            $p_val = $p['discount_value'] ?? $p['amount'] ?? $p['discount'] ?? 0;
                            ?>
                            <div class="card mb-3 border-0 shadow-sm rounded-4">
                                <div class="d-flex align-items-center">
                                    <div class="bg-primary p-4 text-white text-center rounded-start" style="min-width: 90px;">
                                        <div class="h4 fw-bold mb-0"><?= $p_val ?><?= (($p['discount_type'] ?? '') == 'percent') ? '%' : '' ?></div>
                                        <div class="small">ลด</div>
                                    </div>
                                    <div class="p-3">
                                        <h6 class="fw-bold mb-1"><?= htmlspecialchars($p['title'] ?? 'ส่วนลดพิเศษ') ?></h6>
                                        <form method="post">
                                            <input type="hidden" name="promo_id" value="<?= $p['id'] ?>">
                                            <button type="submit" name="apply_promo" class="btn btn-sm btn-outline-primary rounded-pill mt-1">กดใช้ส่วนลด</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
    <script src="../../../bootstrap/js/bootstrap.bundle.min.js"></script>
</body>

</html>