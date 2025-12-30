
<?php
if (isset($_GET['link'])) {
    $link = $_GET['link'];
} else {
    $link = "home";
}

if ($link == 'home') {
    include_once "index.php";
}
elseif ($link == 'orders') {
    include_once "menu/orders/index.php";
}
elseif ($link == 'profile') {
    include_once "profile/profile.php";
}
elseif ($link == 'editprofile') {
    include_once "profile/edit.php";
}
elseif ($link == 'delivery') {
    include_once "menu/delivery/index.php";
}
elseif ($link == 'revenue') {
    include_once "menu/revenue/index.php";
}
elseif ($link == 'management') {
    include_once "menu/staff_edit/staff_index.php";
}
elseif ($link == 'qrcode') {
    include_once "menu/qrgen/machines_index.php";
}

?>