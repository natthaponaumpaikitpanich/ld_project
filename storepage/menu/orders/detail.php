<?php
session_start();
require_once "../../../ld_db.php";

/* ========= AUTH ========= */
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['store_owner','staff'])) {
    die('no permission');
}

$user_id  = $_SESSION['user_id'];
$order_id = $_GET['id'] ?? null;
if (!$order_id) die('no order');

/* ========= POST : รับเงินสด ========= */
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['cash_paid'])) {

    $pdo->beginTransaction();

    // ดึง order
    $stmt = $pdo->prepare("
        SELECT total_amount, payment_status
        FROM orders
        WHERE id=?
    ");
    $stmt->execute([$order_id]);
    $o = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($o && $o['payment_status']!=='paid') {

        // create payment (cash)
        $pdo->prepare("
            INSERT INTO payments
            (id, order_id, amount, method, status, confirmed_by, confirmed_at, created_at)
            VALUES (UUID(), ?, ?, 'cash', 'confirmed', ?, NOW(), NOW())
        ")->execute([
            $order_id,
            $o['total_amount'],
            $user_id
        ]);

        // update order
        $pdo->prepare("
            UPDATE orders
            SET payment_status='paid'
            WHERE id=?
        ")->execute([$order_id]);
    }

    $pdo->commit();
    header("Location: detail.php?id=".$order_id);
    exit;
}

/* ========= POST : เปลี่ยนสถานะ ========= */
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['next_status'])) {

    // HARD GATE
    $stmt = $pdo->prepare("
        SELECT payment_status, status
        FROM orders
        WHERE id=?
    ");
    $stmt->execute([$order_id]);
    $chk = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($chk['status']==='ready' && $chk['payment_status']!=='paid') {
        die('ยังไม่ได้ชำระเงิน');
    }

    $pdo->beginTransaction();

    $next = $_POST['next_status'];

    $pdo->prepare("
        UPDATE orders SET status=?
        WHERE id=?
    ")->execute([$next,$order_id]);

    $pdo->prepare("
        UPDATE pickups SET status=?
        WHERE order_id=?
    ")->execute([$next,$order_id]);

    $pdo->prepare("
        INSERT INTO order_status_logs
        (id,order_id,status,changed_by)
        VALUES (UUID(),?,?,?)
    ")->execute([$order_id,$next,$user_id]);

    $pdo->commit();
    header("Location: detail.php?id=".$order_id);
    exit;
}

/* ========= FETCH ORDER ========= */
$stmt = $pdo->prepare("
    SELECT o.*, u.display_name customer_name
    FROM orders o
    JOIN users u ON u.id=o.customer_id
    JOIN store_staff ss ON ss.store_id=o.store_id
    WHERE o.id=? AND ss.user_id=?
");
$stmt->execute([$order_id,$user_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$order) die('not found');

/* ========= FETCH PAYMENT ========= */
$stmt = $pdo->prepare("
    SELECT *
    FROM payments
    WHERE order_id=?
    ORDER BY created_at DESC
    LIMIT 1
");
$stmt->execute([$order_id]);
$payment = $stmt->fetch(PDO::FETCH_ASSOC);

/* ========= HELPERS ========= */
function label($s){
    return match($s){
        'created'=>'รอรับงาน',
        'picked_up'=>'รับผ้าแล้ว',
        'in_process'=>'กำลังซัก',
        'ready'=>'ซักเสร็จ',
        'out_for_delivery'=>'กำลังส่ง',
        'completed'=>'เสร็จงาน',
        default=>$s
    };
}
function next_status($s){
    return match($s){
        'created'=>'picked_up',
        'picked_up'=>'in_process',
        'in_process'=>'ready',
        'ready'=>'out_for_delivery',
        'out_for_delivery'=>'completed',
        default=>null
    };
}

$need_payment = ($order['status']==='ready' && $order['payment_status']!=='paid');
?>
<!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<link href="../../../bootstrap/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
<div class="container py-4">

<h4><?= label($order['status']) ?> | <?= $order['order_number'] ?></h4>
<p>👤 ลูกค้า: <?= htmlspecialchars($order['customer_name']) ?></p>

<!-- ===== PAYMENT STATUS ===== -->
<div class="card mb-3">
<div class="card-body">

<h6 class="fw-bold">💳 การชำระเงิน</h6>

<?php if ($order['payment_status']==='paid'): ?>
    <div class="alert alert-success mb-0">
        ✅ ชำระเงินเรียบร้อยแล้ว
    </div>

<?php elseif ($payment && $payment['status']==='pending'): ?>
    <div class="alert alert-warning mb-2">
        📄 ลูกค้าส่งสลิปแล้ว รอการตรวจสอบ
    </div>
    <a href="../payment_confirm.php?id=<?= $payment['id'] ?>"
       class="btn btn-success btn-sm">
        ตรวจสอบสลิป
    </a>

<?php else: ?>
    <div class="alert alert-secondary">
        ยังไม่ได้ชำระเงิน
    </div>

    <!-- รับเงินสด -->
    <form method="post">
        <button name="cash_paid"
                class="btn btn-outline-success">
            💵 รับเงินสดแล้ว
        </button>
    </form>
<?php endif; ?>

</div>
</div>

<!-- ===== WARNING ===== -->
<?php if ($need_payment): ?>
<div class="alert alert-warning">
⚠️ งานนี้ยังไม่ได้ชำระเงิน
</div>
<?php endif; ?>

<!-- ===== NEXT STATUS ===== -->
<?php if ($next = next_status($order['status'])): ?>
<form method="post">
    <input type="hidden" name="next_status" value="<?= $next ?>">
    <button class="btn btn-primary">
        ไปขั้นถัดไป
    </button>
</form>
<?php endif; ?>

<a href="../../index.php?link=orders" class="btn btn-outline-secondary mt-3">
← กลับ
</a>

</div>
</body>
</html>
