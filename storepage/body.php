
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

?>