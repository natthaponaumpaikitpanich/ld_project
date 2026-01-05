<?php
session_start();
require_once "../../../ld_db.php";


if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: ../loginpage/login.php");
    exit;
}
function generate_uuid_v4() {
    return sprintf(
        '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}
$customer_id = $_SESSION['user_id'];
$errors = [];
$success = false;

/* ===============================
   ดึงร้านที่เปิดใช้งาน
================================ */
$stmt = $pdo->prepare("
    SELECT id, name, address
    FROM stores
    WHERE status = 'active'
");
$stmt->execute();
$stores = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ===============================
   เมื่อกดยืนยันสร้าง Order
================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $store_id = $_POST['store_id'] ?? null;
    $notes    = trim($_POST['notes'] ?? '');

    if (!$store_id) {
        $errors[] = "กรุณาเลือกร้านซัก";
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            // สร้าง order id และเลข order
            $order_id  = generate_uuid_v4();
            $order_number = 'LD-' . date('ymd') . '-' . rand(1000, 9999);

            /* ---------- INSERT orders ---------- */
            $stmt = $pdo->prepare("
                INSERT INTO orders (
                    id,
                    customer_id,
                    store_id,
                    order_number,
                    status,
                    payment_status,
                    notes
                ) VALUES (
                    :id,
                    :customer_id,
                    :store_id,
                    :order_number,
                    'created',
                    'pending',
                    :notes
                )
            ");
            $stmt->execute([
                ':id' => $order_id,
                ':customer_id' => $customer_id,
                ':store_id' => $store_id,
                ':order_number' => $order_number,
                ':notes' => $notes
            ]);

            /* ---------- ดึงที่อยู่ลูกค้า ---------- */
            $stmt = $pdo->prepare("SELECT detail FROM users WHERE id = :id");
            $stmt->execute([':id' => $customer_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            $pickup_address = $user['detail'] ?? '';

            /* ---------- INSERT pickups ---------- */
            $pickup_id = generate_uuid_v4();
            $stmt = $pdo->prepare("
                INSERT INTO pickups (
                    id,
                    order_id,
                    pickup_address,
                    status
                ) VALUES (
                    :id,
                    :order_id,
                    :pickup_address,
                    'scheduled'
                )
            ");
            $stmt->execute([
                ':id' => $pickup_id,
                ':order_id' => $order_id,
                ':pickup_address' => $pickup_address
            ]);

            /* ---------- INSERT order_status_logs ---------- */
            $log_id    = generate_uuid_v4();
            $stmt = $pdo->prepare("
                INSERT INTO order_status_logs (
                    id,
                    order_id,
                    status,
                    changed_by
                ) VALUES (
                    :id,
                    :order_id,
                    'created',
                    :changed_by
                )
            ");
            $stmt->execute([
                ':id' => $log_id,
                ':order_id' => $order_id,
                ':changed_by' => $customer_id
            ]);

            $pdo->commit();
            $success = true;

        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = "เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>สร้างคำสั่งซัก</title>
    <link href="../../../bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="icon" href="../image/3.jpg">
    <style>
         body {
            font-family: 'Kanit', sans-serif;
        }

        .card-menu {
            border-radius: 16px;
            transition: .2s;
        }

        .card-menu:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, .1);
        }

        .profile-img {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            object-fit: cover;
        }
    </style>
</head>
<body class="bg-light">

<div class="container d-flex justify-content-center align-items-center" style="min-height: 100vh;">
    <div class="col-lg-6 col-md-8">

        <div class="card shadow-lg border-0 rounded-4">
            <div class="card-body p-4 p-md-5">

                <div class="text-center mb-4">
                    <i class="bi bi-basket-fill text-primary" style="font-size:48px;"></i>
                    <h3 class="fw-bold mt-3">สั่งให้มารับผ้า</h3>
                    <p class="text-muted mb-0">
                        เลือกร้านซัก ระบบจะจัดการทุกขั้นตอนให้คุณอัตโนมัติ
                    </p>
                </div>

                <?php if ($success): ?>
                    <div class="alert alert-success text-center">
                        <h5 class="mb-2">🎉 สร้างคำสั่งซักสำเร็จ</h5>
                    </div>
                <?php endif; ?>

                <?php if ($errors): ?>
                    <div class="alert alert-danger">
                        <?php foreach ($errors as $err): ?>
                            <div><?= htmlspecialchars($err) ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <form method="post">

                    <div class="mb-4">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-shop"></i> เลือกร้านซัก
                        </label>
                        <select name="store_id" class="form-select form-select-lg" required>
                            <option value="">เลือกร้านที่ต้องการ</option>
                            <?php foreach ($stores as $store): ?>
                                <option value="<?= $store['id'] ?>">
                                    <?= htmlspecialchars($store['name']) ?>
                                    — <?= htmlspecialchars($store['address']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-chat-dots"></i> หมายเหตุถึงร้าน (ไม่บังคับ)
                        </label>
                        <textarea
                            name="notes"
                            class="form-control"
                            rows="3"
                            placeholder="เช่น มีผ้าขาวปน, ติด Airtag, ขอซักแยก ฯลฯ"
                        ></textarea>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg rounded-pill">
                            ยืนยันคำสั่งซัก
                        </button>
                    </div>
                    
 
                </form>
<div class="mt-3 text-end">
    <a href="../../index.php?link=home">
                        <button class="btn btn-warning btn-lg rounded-pill">
                            กลับ
                        </button>
                    </div></a>
            </div>
        </div>

        <div class="text-center text-muted mt-4 small">
            ระบบจะแจ้งสถานะการซักให้คุณทราบอัตโนมัติทุกขั้นตอน
        </div>

    </div>
</div>

</body>
</html>
