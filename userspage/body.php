<?php // ===== FORCE PAYMENT CHECK =====
$stmt = $pdo->prepare("
    SELECT id
    FROM orders
    WHERE customer_id = ?
      AND status = 'ready'
      AND payment_status != 'paid'
    ORDER BY created_at DESC
    LIMIT 1
");
$stmt->execute([$_SESSION['user_id']]);
$unpaidOrder = $stmt->fetch(PDO::FETCH_ASSOC);

if ($unpaidOrder) {
    // ถ้ายังไม่ได้อยู่หน้าชำระเงิน → เด้ง
    if (!str_contains($_SERVER['REQUEST_URI'], 'menu/payment_promptpay.php')) {
        header("Location: menu/payment_promptpay.php?id=".$unpaidOrder['id']);
        exit;
    }
}
 ?>
<?php
if (isset($_GET['link'])) {
    $link = $_GET['link'];
} else {
    $link = "home";
}

if ($link == 'home') {
    include_once "index.php";
}

elseif ($link == 'profile') {
    include_once "menu/profile/profile.php";
}
elseif ($link == 'orders') {
    include_once "menu/orders/my_orders.php";
}





?>