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

/* ========= POST ========= */

// เปลี่ยนสถานะ
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['next_status'])) {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("UPDATE orders SET status=? WHERE id=?");
    $stmt->execute([$_POST['next_status'], $order_id]);

    $stmt = $pdo->prepare("
        INSERT INTO order_status_logs (id, order_id, status, changed_by)
        VALUES (UUID(),?,?,?)
    ");
    $stmt->execute([$order_id, $_POST['next_status'], $user_id]);

    $pdo->commit();
    header("Location: detail.php?id=".$order_id);
    exit;
}

// ตั้งราคา
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['set_price'])) {
    $stmt = $pdo->prepare("
        UPDATE orders SET total_amount=?, payment_status='pending'
        WHERE id=?
    ");
    $stmt->execute([$_POST['total_amount'], $order_id]);
    header("Location: detail.php?id=".$order_id);
    exit;
}

// รับเงิน
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_payment'])) {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        INSERT INTO payments
        (id, order_id, amount, provider, provider_txn_id, status, paid_at)
        VALUES (UUID(),?,?,?,?, 'success', NOW())
    ");
    $stmt->execute([
        $order_id,
        $_POST['amount'],
        $_POST['provider'],
        $_POST['txn']
    ]);

    $stmt = $pdo->prepare("UPDATE orders SET payment_status='paid' WHERE id=?");
    $stmt->execute([$order_id]);

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

function label($s){
    return match($s){
        'created'=>'รอรับงาน','picked_up'=>'รับผ้าแล้ว',
        'in_process'=>'กำลังซัก','ready'=>'ซักเสร็จ',
        'out_for_delivery'=>'กำลังส่ง','completed'=>'เสร็จงาน'
    };
}
function next_status($s){
    return match($s){
        'created'=>'picked_up','picked_up'=>'in_process',
        'in_process'=>'ready','ready'=>'out_for_delivery',
        'out_for_delivery'=>'completed', default=>null
    };
}
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

<p>👤 ลูกค้า: <?= $order['customer_name'] ?></p>

<?php if ($next = next_status($order['status'])): ?>
<form method="post">
    <input type="hidden" name="next_status" value="<?= $next ?>">
    <button class="btn btn-primary mb-3">ไปขั้นถัดไป</button>
</form>
<?php endif; ?>

<?php if ($order['status']==='ready'): ?>
<div class="card mb-3 p-3">
    <h6>💰 ตั้งราคา</h6>
    <?php if ($order['total_amount']>0): ?>
        <b><?= number_format($order['total_amount'],2) ?> บาท</b>
    <?php else: ?>
    <form method="post">
        <input name="total_amount" type="number" class="form-control mb-2" required>
        <button name="set_price" class="btn btn-success">บันทึกราคา</button>
    </form>
    <?php endif; ?>
</div>
<?php endif; ?>

<div class="card p-3 mb-3">
<h6>💳 สถานะชำระเงิน</h6>
<b><?= $order['payment_status'] ?></b>

<?php if ($order['payment_status']==='pending' && $order['total_amount']>0): ?>
<form method="post" class="mt-2">
    <input type="hidden" name="amount" value="<?= $order['total_amount'] ?>">
    <input name="txn" class="form-control mb-2" placeholder="เลขอ้างอิง (ถ้ามี)">
    <select name="provider" class="form-select mb-2">
        <option value="cash">เงินสด</option>
        <option value="transfer">โอน</option>
    </select>
    <button name="confirm_payment" class="btn btn-success">
        รับเงินแล้ว
    </button>
</form>
<?php endif; ?>
</div>

<a href="../../index.php?link=orders">← กลับ</a>
</div>
</body>
</html>
