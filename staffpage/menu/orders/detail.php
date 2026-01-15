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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cash_paid'])) {

    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        SELECT total_amount, payment_status
        FROM orders
        WHERE id=?
    ");
    $stmt->execute([$order_id]);
    $o = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($o && $o['payment_status'] !== 'paid') {

        $payment_id = sprintf(
    '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
    mt_rand(0,0xffff), mt_rand(0,0xffff),
    mt_rand(0,0xffff),
    mt_rand(0,0x0fff) | 0x4000,
    mt_rand(0,0x3fff) | 0x8000,
    mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0xffff)
);
$stmt = $pdo->prepare("
    INSERT INTO payments
    (id, order_id, amount, provider, status, confirmed_by, confirmed_at, created_at)
    VALUES (:id, :order_id, :amount, 'cash', 'confirmed', :user, NOW(), NOW())
");

$stmt->execute([
    ':id'        => $payment_id,
    ':order_id' => $order_id,
    ':amount'   => $o['total_amount'],
    ':user'     => $user_id
]);
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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['next_status'])) {

    $stmt = $pdo->prepare("
        SELECT payment_status, status
        FROM orders
        WHERE id=?
    ");
    $stmt->execute([$order_id]);
    $chk = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($chk['status'] === 'ready' && $chk['payment_status'] !== 'paid') {
        die('ยังไม่ได้ชำระเงิน');
    }

    $pdo->beginTransaction();

    $next = $_POST['next_status'];

    $pdo->prepare("UPDATE orders SET status=? WHERE id=?")
        ->execute([$next, $order_id]);

    $pdo->prepare("UPDATE pickups SET status=? WHERE order_id=?")
        ->execute([$next, $order_id]);

    $pdo->prepare("
        INSERT INTO order_status_logs
        (id,order_id,status,changed_by)
        VALUES (UUID(),?,?,?)
    ")->execute([$order_id, $next, $user_id]);

    $pdo->commit();
    header("Location: detail.php?id=".$order_id);
    exit;
}

/* ========= FETCH ORDER ========= */
$stmt = $pdo->prepare("
    SELECT DISTINCT
        o.*,
        u.display_name AS customer_name
    FROM orders o
    JOIN users u ON u.id = o.customer_id
    JOIN stores s ON s.id = o.store_id
    LEFT JOIN store_staff ss 
        ON ss.store_id = o.store_id
        AND ss.user_id = :staff_user
    WHERE o.id = :order_id
      AND (
            s.owner_id = :owner_user
            OR ss.user_id IS NOT NULL
          )
");

/* 🔥 ตรงนี้คือหัวใจ */
$params = [
    ':order_id'   => $order_id,
    ':staff_user' => $user_id,
    ':owner_user' => $user_id
];

$stmt->execute($params);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    die('not found');
}


/* ========= PICKUP ========= */
$stmt = $pdo->prepare("
    SELECT pickup_address, lat, lng
    FROM pickups
    WHERE order_id=?
    LIMIT 1
");
$stmt->execute([$order_id]);
$pickup = $stmt->fetch(PDO::FETCH_ASSOC);

/* ========= PAYMENT ========= */
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
?>
<!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<title>รายละเอียดงานซัก</title>
<link href="../../../bootstrap/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
<link rel="icon" href="../../../image/3.jpg">

<style>
body{background:#f5f7fb}
.card{border-radius:14px}
.status-step{display:flex;gap:8px;align-items:center}
.status-dot{width:14px;height:14px;border-radius:50%}
.active-step{background:#0d6efd}
.done-step{background:#198754}
.wait-step{background:#adb5bd}
</style>
</head>

<body>
<div class="container py-4">

<!-- ===== HEADER ===== -->
<div class="card mb-3 shadow-sm">
<div class="card-body">
<h5 class="fw-bold mb-1">📦 <?= htmlspecialchars($order['order_number']) ?></h5>
<div class="text-muted mb-3">👤 ลูกค้า: <?= htmlspecialchars($order['customer_name']) ?></div>

<div class="d-flex gap-4 flex-wrap">
<?php
$steps = ['created','picked_up','in_process','ready','out_for_delivery','completed'];
foreach ($steps as $s):
    $cls = $s == $order['status']
        ? 'active-step'
        : (array_search($s,$steps) < array_search($order['status'],$steps)
            ? 'done-step' : 'wait-step');
?>
<div class="status-step">
    <div class="status-dot <?= $cls ?>"></div>
    <small><?= label($s) ?></small>
</div>
<?php endforeach ?>
</div>
</div>
</div>

<!-- ===== PAYMENT ===== -->
<div class="card mb-3 shadow-sm">
<div class="card-body">
<h6 class="fw-bold mb-2">💳 การชำระเงิน</h6>

<?php if ($order['payment_status']==='paid'): ?>
<div class="alert alert-success mb-0">✅ ชำระเงินแล้ว</div>

<?php elseif ($payment): ?>
<div class="alert alert-warning">📄 มีข้อมูลการชำระเงิน</div>
<?php else: ?>
<div class="alert alert-secondary mb-2">ยังไม่ได้ชำระเงิน</div>
<form method="post" onsubmit="return confirm('ยืนยันรับเงินสดแล้ว?')">
<button name="cash_paid" class="btn btn-success">💵 รับเงินสด</button>
</form>
<?php endif ?>
</div>
</div>

<!-- ===== MAP ===== -->
<?php if ($order['status']==='out_for_delivery' && $pickup): ?>
<div class="card mb-3 shadow-sm">
<div class="card-body text-center">
<h6 class="fw-bold mb-2">🗺️ นำทางไปยังลูกค้า</h6>
<button class="btn btn-success w-100"
onclick="navigateTo(
<?= $pickup['lat'] ?? 'null' ?>,
<?= $pickup['lng'] ?? 'null' ?>,
'<?= htmlspecialchars($pickup['pickup_address'],ENT_QUOTES) ?>'
)">
🚗 เปิด Google Maps
</button>
<div class="text-muted small mt-2">
<?= htmlspecialchars($pickup['pickup_address']) ?>
</div>
</div>
</div>
<?php endif ?>

<!-- ===== NEXT STATUS ===== -->
<?php if ($next = next_status($order['status'])): ?>
<form method="post" onsubmit="return confirm('ไปขั้นถัดไป?')">
<input type="hidden" name="next_status" value="<?= $next ?>">
<button class="btn btn-primary w-100 py-2">
➡️ ไปขั้นถัดไป (<?= label($next) ?>)
</button>
</form>
<?php endif ?>

<a href="../../index.php?link=orders" class="btn btn-outline-secondary mt-3">
← กลับหน้าออเดอร์
</a>

</div>

<script src="../../../bootstrap/js/bootstrap.bundle.min.js"></script>
<script>
function navigateTo(lat, lng, address){
    let dest = (lat && lng) ? `${lat},${lng}` : encodeURIComponent(address);
    window.open(
        `https://www.google.com/maps/dir/?api=1&destination=${dest}&travelmode=driving`,
        '_blank'
    );
}
</script>
</body>
</html>
