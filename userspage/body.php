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
elseif ($link == 'tracking') {
    include_once "menu/map/track.php";
}





?>