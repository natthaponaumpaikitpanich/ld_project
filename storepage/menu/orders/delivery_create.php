<?php
session_start();
require_once "../../../ld_db.php";

/* ========= AUTH ========= */
$store_id = $_SESSION['store_id'] ?? null;
if (!$store_id) {
    die("ไม่พบข้อมูลร้าน");
}

/* ========= INPUT ========= */
$order_id = $_GET['order_id'] ?? null;
if (!$order_id) {
    die("ไม่พบออเดอร์");
}

/* ========= VERIFY ORDER OWNERSHIP ========= */
$stmt = $pdo->prepare("
    SELECT id
    FROM orders
    WHERE id = ? AND store_id = ?
");
$stmt->execute([$order_id, $store_id]);
if (!$stmt->fetch()) {
    die("ออเดอร์ไม่ถูกต้อง");
}

/* ========= CHECK DUPLICATE PICKUP ========= */
$chk = $pdo->prepare("
    SELECT id FROM pickups WHERE order_id = ?
");
$chk->execute([$order_id]);
if ($chk->fetch()) {
    die("ออเดอร์นี้มีงานจัดส่งแล้ว");
}

/* ========= SUBMIT ========= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $pickup_address = trim($_POST['pickup_address'] ?? '');
    $scheduled_at   = $_POST['scheduled_at'] ?? null;

    if ($pickup_address === '') {
        die("กรุณากรอกที่อยู่รับผ้า");
    }

    try {
        $pdo->beginTransaction();

        /* CREATE PICKUP */
        $stmt = $pdo->prepare("
            INSERT INTO pickups
            (id, order_id, pickup_address, scheduled_at, status, created_at)
            VALUES (UUID(), ?, ?, ?, 'scheduled', NOW())
        ");
        $stmt->execute([
            $order_id,
            $pickup_address,
            $scheduled_at
        ]);

        /* LOG STATUS */
        $pdo->prepare("
            INSERT INTO order_status_logs
            (id, order_id, status, changed_by)
            VALUES (UUID(), ?, 'pickup_scheduled', ?)
        ")->execute([
            $order_id,
            $_SESSION['user_id']
        ]);

        $pdo->commit();

        header("Location: ../../index.php?link=delivery");
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        die("เกิดข้อผิดพลาด: " . $e->getMessage());
    }
}
?>

<!doctype html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <title>สร้างงานจัดส่ง</title>
    <link href="../../../bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" href="../../../image/3.jpg">
</head>

<body class="bg-light">
<div class="container py-4">

    <h4 class="mb-3">🚚 สร้างงานจัดส่ง</h4>

    <div class="card shadow-sm">
        <div class="card-body">

            <form method="post">

                <div class="mb-3">
                    <label class="form-label">ที่อยู่รับผ้า</label>
                    <textarea name="pickup_address"
                              class="form-control"
                              rows="3"
                              required></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">เวลานัดรับ</label>
                    <input type="datetime-local"
                           name="scheduled_at"
                           class="form-control">
                </div>

                <div class="text-end">
                    <a href="../../index.php?link=delivery"
                       class="btn btn-secondary">
                        ยกเลิก
                    </a>
                    <button class="btn btn-primary">
                        บันทึกงานจัดส่ง
                    </button>
                </div>

            </form>

        </div>
    </div>

</div>
</body>
</html>
