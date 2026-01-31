<?php
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: ../../../loginpage/login.php");
    exit;
}

$customer_id = $_SESSION['user_id'];

/* ---------- orders ---------- */
$stmt = $pdo->prepare("
    SELECT o.*, s.name AS store_name
    FROM orders o
    JOIN stores s ON o.store_id = s.id
    WHERE o.customer_id = :customer_id
      AND o.status != 'completed'
    ORDER BY o.created_at DESC
");
$stmt->execute([':customer_id' => $customer_id]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ---------- logs ---------- */
$orderIds = array_column($orders, 'id');
$logsByOrder = [];

if ($orderIds) {
    $in  = str_repeat('?,', count($orderIds) - 1) . '?';
    $stmt = $pdo->prepare("
        SELECT order_id, status
        FROM order_status_logs
        WHERE order_id IN ($in)
        ORDER BY created_at ASC
    ");
    $stmt->execute($orderIds);

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $log) {
        $logsByOrder[$log['order_id']][] = $log['status'];
    }
}

/* helpers */
function status_label($status) {
    return match($status) {
        'created'=>'รอร้านรับงาน',
        'picked_up'=>'รับผ้าแล้ว',
        'in_process'=>'กำลังซัก',
        'ready'=>'ซักเสร็จ',
        'out_for_delivery'=>'กำลังนำส่ง',
        'completed'=>'ส่งคืนแล้ว',
        default=>$status
    };
}
function status_icon($status) {
    return match($status) {
        'created'=>'bi-receipt',
        'picked_up'=>'bi-box-seam',
        'in_process'=>'bi-arrow-repeat',
        'ready'=>'bi-check-circle',
        'out_for_delivery'=>'bi-truck',
        'completed'=>'bi-house-check',
        default=>'bi-clock'
    };
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>คำสั่งซักของฉัน</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<link href="../../../bootstrap/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<style>
body{
    font-family:'Kanit',sans-serif;
    background:#f6f7fb;
}
.step .label{
    margin-left: 30px;
    padding-top: 2px;
    font-size: 15px;
}
.order-card{
    border-radius:20px;
    border:none;
    box-shadow:0 12px 30px rgba(0,0,0,.08);
}

.timeline{
    position:relative;
    padding-left:30px;
}

.timeline::before{
    content:'';
    position:absolute;
    left:10px;
    top:0;
    bottom:0;
    width:2px;
    background:#dee2e6;
}

.step{
    position:relative;
    margin-bottom:16px;
}

.step i{
    position:absolute;
    left:-2px;
    top:2px;
    font-size:18px;
}

.step.done i{
    color:#2a5298;
}

.step.done .label{
    font-weight:500;
}

.step.pending{
    color:#adb5bd;
}
</style>
</head>

<body>

<div class="container py-4">

<h4 class="fw-bold mb-4">🧺 คำสั่งซักของฉัน</h4>

<?php foreach ($orders as $order): ?>
<?php
$shown = $logsByOrder[$order['id']] ?? [];
$all_status = ['created','picked_up','in_process','ready','out_for_delivery','completed'];
?>

<div class="card order-card mb-4">
<div class="card-body p-4">

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <div class="fw-semibold"><?= htmlspecialchars($order['store_name']) ?></div>
        <small class="text-muted"><?= $order['order_number'] ?></small>
    </div>
    <span class="badge bg-primary rounded-pill md-3">
        <?= status_label($order['status']) ?>
    </span>
</div>

<div class="timeline">
<?php foreach ($all_status as $st): 
    $done = in_array($st,$shown);
?>
    <div class="step <?= $done?'done':'pending' ?>">
        <i class="bi <?= status_icon($st) ?>"></i>
        <div class="label"><?= status_label($st) ?></div>
    </div>
<?php endforeach; ?>
</div>

<div class="text-end mt-3">
    <a href="menu/orders/order_detail.php?id=<?= $order['id'] ?>"
       class="btn btn-outline-primary rounded-pill">
        ดูรายละเอียด
    </a>
</div>

</div>
</div>

<?php endforeach; ?>

</div>

</body>
</html>
 