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

/* ---------- logs (ทีเดียว) ---------- */
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

/* ---------- helpers ---------- */
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
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
<link href="../../../bootstrap/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
<div class="container py-5">

<h3 class="fw-bold mb-4">🧺 คำสั่งซักของฉัน</h3>

<?php foreach ($orders as $order): ?>
<?php
$shown = $logsByOrder[$order['id']] ?? [];
$all_status = ['created','picked_up','in_process','ready','out_for_delivery','completed'];
?>

<div class="card mb-4 shadow-sm">
<div class="card-body">

<div class="d-flex justify-content-between mb-3">
    <div>
        <h5 class="fw-bold"><?= htmlspecialchars($order['store_name']) ?></h5>
        <small class="text-muted"><?= $order['order_number'] ?></small>
    </div>
    <span class="badge bg-primary">
        <?= status_label($order['status']) ?>
    </span>
</div>

<?php foreach ($all_status as $st): ?>
<div class="d-flex align-items-center mb-2 <?= in_array($st,$shown)?'':'text-muted' ?>">
    <i class="bi <?= status_icon($st) ?> me-2"></i>
    <?= status_label($st) ?>
</div>
<?php endforeach; ?>

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
