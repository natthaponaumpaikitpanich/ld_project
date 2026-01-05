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

/* ========= POST : เปลี่ยนสถานะ ========= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['next_status'])) {

    // 🔒 HARD GATE : ถ้ายังไม่จ่าย ห้ามไปต่อ
    $stmt = $pdo->prepare("
        SELECT payment_status, status
        FROM orders
        WHERE id = ?
    ");
    $stmt->execute([$order_id]);
    $chk = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($chk['status']==='ready' && $chk['payment_status']!=='paid') {
        die('ยังไม่ได้ชำระเงิน');
    }

    $pdo->beginTransaction();

    $next = $_POST['next_status'];

    // update orders
    $pdo->prepare("
        UPDATE orders SET status=?
        WHERE id=?
    ")->execute([$next,$order_id]);

    // update pickups
    $pdo->prepare("
        UPDATE pickups SET status=?
        WHERE order_id=?
    ")->execute([$next,$order_id]);

    // log
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

/* ========= FETCH PAYMENT (ล่าสุด) ========= */
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
        default => $s
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

// 🔒 เช็กว่าสามารถไปต่อได้ไหม
$can_next = !(
    $order['status']==='ready'
    && $order['payment_status']!=='paid'
);
?>
<!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<link href="../../../bootstrap/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
<div class="container py-4">

<h4 class="mb-2">
    <?= label($order['status']) ?> | <?= htmlspecialchars($order['order_number']) ?>
</h4>
<p class="mb-3">👤 ลูกค้า: <?= htmlspecialchars($order['customer_name']) ?></p>

<!-- ================= PAYMENT STATUS ================= -->
<div class="card mb-3">
<div class="card-body">
<h6 class="fw-bold mb-2">💳 การชำระเงิน</h6>

<?php if (!$payment): ?>
    <div class="alert alert-secondary mb-0">
        ยังไม่มีการแจ้งชำระเงิน
    </div>

<?php elseif ($payment['status']==='pending'): ?>
    <div class="alert alert-warning mb-2">
        ลูกค้าส่งสลิปแล้ว รอการตรวจสอบ
    </div>
    <a href="../payment_confirm.php?id=<?= $payment['id'] ?>"
       class="btn btn-success btn-sm">
        ✅ ตรวจสอบสลิป
    </a>

<?php elseif ($payment['status']==='confirmed'): ?>
    <div class="alert alert-success mb-0">
        ชำระเงินเรียบร้อยแล้ว
    </div>

<?php elseif ($payment['status']==='rejected'): ?>
    <div class="alert alert-danger mb-0">
        การชำระเงินถูกปฏิเสธ
    </div>
<?php endif; ?>

</div>
</div>

<!-- ================= NEXT STATUS ================= -->
<?php if ($next = next_status($order['status'])): ?>
    <?php if ($can_next): ?>
        <form method="post">
            <input type="hidden" name="next_status" value="<?= $next ?>">
            <button class="btn btn-primary mb-3">
                ไปขั้นถัดไป
            </button>
        </form>
    <?php else: ?>
        <div class="alert alert-warning">
            ⚠️ รอลูกค้าชำระเงินก่อนจึงจะไปต่อได้
        </div>
    <?php endif; ?>
<?php endif; ?>

<a href="../../index.php?link=orders" class="btn btn-outline-secondary">
    ← กลับ
</a>

</div>
</body>
</html>
