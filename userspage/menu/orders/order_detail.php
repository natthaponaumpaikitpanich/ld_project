<?php
date_default_timezone_set('Asia/Bangkok');
session_start();
require_once "../../../ld_db.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    die('Access Denied');
}

$order_id = $_GET['id'] ?? null;
$customer_id = $_SESSION['user_id'];

/* ========= 1. FETCH INITIAL DATA (รวม Query ให้สมบูรณ์ในรอบเดียว) ========= */
$stmt = $pdo->prepare("
    SELECT o.*, 
           s.name AS store_name, 
           s.promptpay_number, 
           s.promptpay_qr, 
           s.qr_image, 
           s.address AS store_addr 
    FROM orders o
    LEFT JOIN stores s ON o.store_id = s.id
    WHERE o.id = ? AND o.customer_id = ?
");
$stmt->execute([$order_id, $customer_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) die('ไม่พบข้อมูลคำสั่งซื้อ');

/* ========= 2. LOGIC การใช้ส่วนลด & ชำระเงิน (ชุดเดิมของคุณ) ========= */
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
                $base_total = (float)$order['total_amount'] + (float)($order['discount_amount'] ?? 0);
                $discount = 0;
                $promo_val = (float)($promo['discount_value'] ?? $promo['amount'] ?? $promo['discount'] ?? 0);
                $type = $promo['discount_type'] ?? '';

                if ($type === 'percentage' || $type === 'percent') {
                    $discount = ($base_total * $promo_val) / 100;
                } else {
                    $discount = $promo_val;
                }

                $new_total = max(0, $base_total - $discount);
                $stmtU = $pdo->prepare("UPDATE orders SET promotion_id = ?, discount_amount = ?, total_amount = ? WHERE id = ?");
                $stmtU->execute([$promo_id, $discount, $new_total, $order_id]);

                $pdo->commit();
                header("Location: order_detail.php?id=$order_id&msg=success");
                exit;
            } catch (Exception $e) {
                $pdo->rollBack();
            }
        }
    }

    // กรณีอัปโหลดสลิป
    if (isset($_POST['upload_slip']) && isset($_FILES['slip_image']) && $_FILES['slip_image']['error'] == 0) {
        $upload_dir = "../../../uploads/slips/";
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        $ext = pathinfo($_FILES['slip_image']['name'], PATHINFO_EXTENSION);
        $filename = "cust_slip_" . time() . "_" . uniqid() . "." . $ext;

        if (move_uploaded_file($_FILES['slip_image']['tmp_name'], $upload_dir . $filename)) {
            try {
                $pdo->beginTransaction();
                // บันทึก path สลิปลงใน note ของ table payments
                $stmtPay = $pdo->prepare("INSERT INTO payments (id, order_id, amount, method, status, note, created_at) VALUES (UUID(), ?, ?, 'promptpay', 'pending', ?, NOW())");
                $stmtPay->execute([$order_id, $order['total_amount'], "uploads/slips/" . $filename]);
                $pdo->commit();
                header("Location: order_detail.php?id=$order_id&msg=pay_success");
                exit;
            } catch (Exception $e) {
                $pdo->rollBack();
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
            header("Location: order_detail.php?id=$order_id&msg=cod_success");
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
        }
    }
}

/* ========= 3. RE-FETCH DATA FOR UI (ดึงข้อมูลล่าสุด) ========= */
$stmt = $pdo->prepare("
    SELECT o.*, s.name AS store_name, s.promptpay_number, s.promptpay_qr, s.qr_image 
    FROM orders o 
    LEFT JOIN stores s ON o.store_id = s.id 
    WHERE o.id = ?
");
$stmt->execute([$order_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

$stmtAvail = $pdo->prepare("SELECT * FROM promotions WHERE store_id = ? AND status = 'active' AND end_date >= NOW() ORDER BY is_flash_sale DESC, created_at DESC");
$stmtAvail->execute([$order['store_id'] ?? '']);
$available_promos = $stmtAvail->fetchAll();

$stmtPayCheck = $pdo->prepare("SELECT * FROM payments WHERE order_id = ? ORDER BY created_at DESC LIMIT 1");
$stmtPayCheck->execute([$order_id]);
$payment = $stmtPayCheck->fetch();

function get_status_badge($s)
{
    return match ($s) {
        'created' => 'bg-secondary',
        'picked_up' => 'bg-info',
        'in_process' => 'bg-primary',
        'ready' => 'bg-success',
        'out_for_delivery' => 'bg-warning text-dark',
        'completed' => 'bg-dark',
        default => 'bg-light text-dark'
    };
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Detail #<?= $order['order_number'] ?></title>
    <link rel="icon" href="../../../image/3.jpg">
    <link href="../../../bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --flash-orange: #ff4757;
            --main-purple: #6c5ce7;
            --success-green: #2ecc71;
        }

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
            background: linear-gradient(135deg, var(--main-purple), #a29bfe);
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
            border-color: var(--main-purple);
            background: #f9f8ff;
            color: var(--main-purple);
        }

        .promo-btn {
            border: 2px dashed var(--main-purple);
            color: var(--main-purple);
            background: #f9f8ff;
            position: relative;
        }

        /* Receipt Styles */
        .receipt-card {
            background: #fff;
            border-radius: 30px;
            position: relative;
            overflow: hidden;
        }

        .receipt-line {
            border-top: 2px dashed #e0e0e0;
            margin: 20px 0;
            position: relative;
        }

        .receipt-line::before,
        .receipt-line::after {
            content: '';
            width: 20px;
            height: 20px;
            background: #f0f4f8;
            border-radius: 50%;
            position: absolute;
            top: -11px;
        }

        .receipt-line::before {
            left: -35px;
        }

        .receipt-line::after {
            right: -35px;
        }

        .proof-img {
            width: 100%;
            height: 150px;
            object-fit: cover;
            border-radius: 15px;
            border: 1px solid #eee;
        }

        .status-stamp {
            border: 3px solid var(--success-green);
            color: var(--success-green);
            padding: 5px 15px;
            border-radius: 10px;
            display: inline-block;
            transform: rotate(-10deg);
            font-weight: bold;
            margin-bottom: 15px;
        }

        @keyframes pulse-flash {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: 0.7;
            }
        }

        .flash-badge-mini {
            background: var(--flash-orange);
            color: white;
            font-size: 0.65rem;
            padding: 2px 8px;
            border-radius: 10px;
            font-weight: bold;
            display: inline-block;
            margin-bottom: 5px;
            animation: pulse-flash 1.5s infinite;
        }

        .promo-card-flash {
            border: 1px solid rgba(255, 71, 87, 0.3) !important;
            background: linear-gradient(to right, #ffffff, #fff5f5) !important;
        }
    </style>
</head>

<body>

    <div class="container py-4">
        <div class="d-flex align-items-center mb-4">
            <a href="../../index.php?link=orders" class="btn btn-white shadow-sm rounded-circle me-3"><i class="bi bi-chevron-left"></i></a>
            <h5 class="mb-0 fw-bold">รายละเอียดออเดอร์</h5>
        </div>

        <?php if ($order['status'] === 'completed'): ?>
            <div class="receipt-card shadow-lg p-4 p-md-5 mx-auto" style="max-width: 550px;">
                <div class="text-center">
                    <div class="status-stamp">COMPLETED</div>
                    <h4 class="fw-bold mb-1"><?= htmlspecialchars($order['store_name']) ?></h4>
                    <p class="text-muted small">ขอบคุณที่ใช้บริการซักอบรีดกับเรา</p>
                </div>

                <div class="receipt-line"></div>

                <div class="row g-3 mb-4">
                    <div class="col-6">
                        <span class="info-label">เลขที่ออเดอร์</span>
                        <div class="fw-bold text-dark">#<?= $order['order_number'] ?></div>
                    </div>
                    <div class="col-6 text-end">
                        <span class="info-label">วันที่เสร็จสิ้น</span>
                        <div class="fw-bold text-dark"><?= date('d/m/Y H:i') ?></div>
                    </div>
                </div>

                <h6 class="fw-bold mb-3"><i class="bi bi-receipt me-2"></i>สรุปค่าบริการ</h6>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">ค่าบริการซักอบรีด</span>
                    <span>฿<?= number_format($order['total_amount'] + ($order['discount_amount'] ?? 0), 2) ?></span>
                </div>
                <?php if ($order['discount_amount'] > 0): ?>
                    <div class="d-flex justify-content-between mb-2 text-danger">
                        <span>ส่วนลดร้านค้า</span>
                        <span>-฿<?= number_format($order['discount_amount'], 2) ?></span>
                    </div>
                <?php endif; ?>
                <div class="d-flex justify-content-between mt-3 pt-3 border-top">
                    <span class="fw-bold h5">ยอดรวมสุทธิ</span>
                    <span class="fw-bold h5 text-primary">฿<?= number_format($order['total_amount'], 2) ?></span>
                </div>

                <div class="receipt-line"></div>

                <h6 class="fw-bold mb-3"><i class="bi bi-images me-2"></i>หลักฐานการดำเนินการ</h6>
                <div class="row g-3">
                    <div class="col-6">
                        <small class="text-muted d-block mb-2">สลิปการโอนเงิน</small>
                        <?php if ($payment && $payment['method'] !== 'cash' && !empty($payment['note'])): ?>
                            <img src="../../../<?= $payment['note'] ?>" class="proof-img shadow-sm" onclick="window.open(this.src)">
                        <?php else: ?>
                            <div class="bg-light rounded-3 text-center py-4 small text-muted border">ชำระด้วยเงินสด</div>
                        <?php endif; ?>
                    </div>
                    <div class="col-6">
                        <small class="text-muted d-block mb-2">รูปภาพส่งงาน</small>
                        <?php if (!empty($order['delivery_image'])): ?>
                            <img src="../../../<?= $order['delivery_image'] ?>" class="proof-img shadow-sm" onclick="window.open(this.src)">
                        <?php else: ?>
                            <div class="bg-light rounded-3 text-center py-4 small text-muted border">ไม่มีรูปภาพส่งงาน</div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="text-center mt-5">
                    <button class="btn btn-outline-dark rounded-pill px-4 btn-sm" onclick="window.print()">
                        <i class="bi bi-printer me-2"></i>พิมพ์ใบเสร็จ
                    </button>
                </div>
            </div>

        <?php else: ?>
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

                <?php
                $base_price_check = (float)$order['total_amount'] + (float)($order['discount_amount'] ?? 0);
                if ($base_price_check <= 0): ?>
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-hourglass-split d-block fs-1 mb-2"></i> ร้านกำลังประเมินราคาผ้าของคุณ...
                    </div>
                <?php else: ?>
                    <?php if ($order['payment_status'] != 'paid' && empty($order['promotion_id'])): ?>
                        <button type="button" class="btn promo-btn w-100 rounded-4 py-3 mb-4 fw-bold" data-bs-toggle="modal" data-bs-target="#promoModal">
                            <i class="bi bi-ticket-perforated me-2"></i> เลือกใช้ส่วนลดร้านค้า
                        </button>
                    <?php endif; ?>

                    <div class="p-3 bg-light rounded-4 mb-4">
                        <?php if (($order['discount_amount'] ?? 0) > 0): ?>
                            <div class="d-flex justify-content-between small text-muted mb-1">
                                <span>ราคาปกติ</span>
                                <span class="text-decoration-line-through">฿<?= number_format($base_price_check, 2) ?></span>
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
                            <i class="bi bi-truck fs-2 d-block mb-2"></i><b>เลือกเก็บเงินปลายทางแล้ว</b>
                        </div>
                    <?php elseif ($payment && $payment['status'] == 'pending'): ?>
                        <div class="alert alert-info text-center border-0 rounded-4 shadow-sm">
                            <i class="bi bi-clock-history d-block fs-3 mb-2"></i>รอร้านค้าตรวจสอบสลิป
                        </div>
                    <?php else: ?>
                        <div class="row g-3 mb-4">
                            <div class="col-6"><button type="button" class="method-btn active"><i class="bi bi-qr-code-scan d-block fs-3 mb-1"></i>สแกนจ่าย</button></div>
                            <div class="col-6">
                                <form method="post">
                                    <button type="submit" name="choose_cod" class="method-btn" onclick="return confirm('ยืนยันเก็บเงินปลายทาง?')"><i class="bi bi-truck d-block fs-3 mb-1"></i>จ่ายปลายทาง</button>
                                </form>
                            </div>
                        </div>
                        <div class="qr-card text-center mb-4 bg-light p-4 rounded-4">
    <?php if (!empty($order['promptpay_qr'])): 
        // 1. จัดการ Path: ใน DB คุณมี ../ ติดมา เราต้องถอยจากจุดที่ไฟล์นี้อยู่ (userspage/menu/orders) 
        // ถอย 3 ชั้นจะไปหยุดที่ ld_project (Root)
        // แล้วเดินหน้าเข้า storepage/menu/ และตามด้วยสิ่งที่อยู่ใน DB
        
        $relative_path = ltrim($order['promptpay_qr'], './'); // ลบ ../ หรือ ./ ออกจากค่าใน DB
        $full_image_path = "../../../storepage/menu/" . $relative_path;
    ?>
        <img src="<?= htmlspecialchars($full_image_path) ?>" 
             class="img-fluid rounded-4 mb-3 shadow-sm" 
             style="max-height: 250px;"
             onerror="this.src='https://via.placeholder.com/250?text=QR+Not+Found';">
        <p class="small text-muted">สแกนเพื่อชำระเงินผ่าน PromptPay</p>
    <?php else: ?>
        <div class="py-3">
            <i class="bi bi-qr-code text-muted fs-1 d-block mb-2"></i>
            <p class="small text-muted">ร้านค้ายังไม่ได้ตั้งค่า QR Code<br>กรุณาติดต่อเจ้าหน้าที่</p>
        </div>
    <?php endif; ?>
</div>
                        <form method="post" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label class="info-label mb-2">อัปโหลดสลิป</label>
                                <input type="file" name="slip_image" class="form-control" accept="image/*" required>
                            </div>
                            <button type="submit" name="upload_slip" class="btn btn-primary w-100 py-3 rounded-4 fw-bold shadow-lg">แจ้งโอนเงิน</button>
                        </form>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>
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
                        <?php foreach ($available_promos as $p):
                            $p_val = (float)($p['discount_value'] ?? $p['amount'] ?? $p['discount'] ?? 0);
                            $is_flash = $p['is_flash_sale'] ?? 0;
                            $p_type = $p['discount_type'] ?? '';
                        ?>
                            <div class="card mb-3 border-0 shadow-sm rounded-4 <?= $is_flash ? 'promo-card-flash' : '' ?>">
                                <div class="d-flex align-items-center">
                                    <div class="<?= $is_flash ? 'bg-danger' : 'bg-primary' ?> p-4 text-white text-center rounded-start" style="min-width: 90px;">
                                        <div class="h4 fw-bold mb-0"><?= $p_val ?><?= ($p_type === 'percentage' || $p_type === 'percent') ? '%' : '' ?></div>
                                        <div class="small">ลด</div>
                                    </div>
                                    <div class="p-3 w-100">
                                        <?php if ($is_flash): ?><div class="flash-badge-mini">FLASH SALE</div><?php endif; ?>
                                        <h6 class="fw-bold mb-1"><?= htmlspecialchars($p['title'] ?? 'ส่วนลดพิเศษ') ?></h6>
                                        <form method="post">
                                            <input type="hidden" name="promo_id" value="<?= $p['id'] ?>">
                                            <button type="submit" name="apply_promo" class="btn btn-sm btn-outline-primary w-100 rounded-pill mt-1">ใช้ส่วนลดนี้</button>
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

    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.min.js"></script>
</body>

</html>