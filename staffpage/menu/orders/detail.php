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

<h4><?= label($order['status']) ?> | <?= $order['order_number'] ?></h4>
<p>👤 ลูกค้า: <?= htmlspecialchars($order['customer_name']) ?></p>

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

<a href="../../index.php?link=orders">← กลับ</a>
</div>
</body>
</html>
