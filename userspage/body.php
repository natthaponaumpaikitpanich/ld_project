
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