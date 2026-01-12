<?php
session_start();
require_once "../../../ld_db.php";

/* ========= AUTH ========= */
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['store_owner', 'staff'])) {
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

        $pdo->prepare("
            INSERT INTO payments
            (id, order_id, amount, provider, status, confirmed_by, confirmed_at, created_at)
            VALUES (UUID(), ?, ?, 'cash', 'confirmed', ?, NOW(), NOW())
        ")->execute([
            $order_id,
            $o['total_amount'],
            $user_id
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

    $pdo->prepare("
        UPDATE orders SET status=?
        WHERE id=?
    ")->execute([$next, $order_id]);

    $pdo->prepare("
        UPDATE pickups SET status=?
        WHERE order_id=?
    ")->execute([$next, $order_id]);

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
    SELECT o.*, u.display_name customer_name
    FROM orders o
    JOIN users u ON u.id=o.customer_id
    JOIN store_staff ss ON ss.store_id=o.store_id
    WHERE o.id=? AND ss.user_id=?
");
$stmt->execute([$order_id, $user_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$order) die('not found');

/* ========= FETCH PICKUP (ที่อยู่ / พิกัด) ========= */
$stmt = $pdo->prepare("
    SELECT pickup_address, lat, lng
    FROM pickups
    WHERE order_id=?
    LIMIT 1
");
$stmt->execute([$order_id]);
$pickup = $stmt->fetch(PDO::FETCH_ASSOC);

/* ========= FETCH PAYMENT (promptpay ล่าสุด) ========= */
$stmt = $pdo->prepare("
    SELECT *
    FROM payments
    WHERE order_id=?
      AND provider='promptpay'
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

<h4><?= label($order['status']) ?> | <?= htmlspecialchars($order['order_number']) ?></h4>
<p>👤 ลูกค้า: <?= htmlspecialchars($order['customer_name']) ?></p>

<!-- ===== PAYMENT ===== -->
<div class="card mb-3">
<div class="card-body">

<h6 class="fw-bold">💳 การชำระเงิน</h6>

<?php if ($order['payment_status']==='paid'): ?>
    <div class="alert alert-success mb-0">✅ ชำระเงินเรียบร้อยแล้ว</div>

<?php elseif ($payment): ?>
    <div class="alert alert-warning mb-2">
        📄 ลูกค้าส่งสลิปแล้ว (<?= strtoupper($payment['status']) ?>)
    </div>

    <?php if (!empty($payment['note'])): ?>
        <div class="text-center mb-2">
            <img src="../../../<?= htmlspecialchars($payment['note']) ?>"
                 class="img-fluid rounded shadow"
                 style="max-height:300px">
        </div>
    <?php endif; ?>

<?php else: ?>
    <div class="alert alert-secondary">ยังไม่ได้ชำระเงิน</div>
    <form method="post">
        <button name="cash_paid" class="btn btn-outline-success">
            💵 รับเงินสดแล้ว
        </button>
    </form>
<?php endif; ?>

</div>
</div>

<!-- ===== NAVIGATION (เฉพาะกำลังส่ง) ===== -->
<?php if ($order['status']==='out_for_delivery' && $pickup): ?>
<div class="card mb-3">
<div class="card-body text-center">

<h6 class="fw-bold mb-2">🗺️ นำทางไปยังลูกค้า</h6>

<button class="btn btn-success w-100"
    onclick="navigateTo(
        <?= $pickup['lat'] ?? 'null' ?>,
        <?= $pickup['lng'] ?? 'null' ?>,
        '<?= htmlspecialchars($pickup['pickup_address'],ENT_QUOTES) ?>'
    )">
    🚗 เปิดนำทาง Google Maps
</button>

<div class="small text-muted mt-2">
    <?= htmlspecialchars($pickup['pickup_address']) ?>
</div>

</div>
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

<a href="../../index.php?link=Home" class="btn btn-outline-secondary mt-3">
← กลับ
</a>

</div>

<script>
function navigateTo(lat, lng, address) {

    if (!navigator.geolocation) {
        alert("อุปกรณ์ไม่รองรับ GPS");
        return;
    }

    navigator.geolocation.getCurrentPosition(
        function(pos){
            const originLat = pos.coords.latitude;
            const originLng = pos.coords.longitude;

            let destination = '';
            if (lat && lng) {
                destination = lat + ',' + lng;
            } else {
                destination = encodeURIComponent(address);
            }

            const url =
                `https://www.google.com/maps/dir/?api=1` +
                `&origin=${originLat},${originLng}` +
                `&destination=${destination}` +
                `&travelmode=driving`;

            window.open(url,'_blank');
        },
        function(){
            alert("ไม่สามารถเข้าถึงตำแหน่งปัจจุบันได้");
        }
    );
}
</script>

</body>
</html>
