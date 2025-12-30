
<?php
if (isset($_GET['link'])) {
    $link = $_GET['link'];
} else {
    $link = "home";
}

if ($link == 'home') {
    include_once "index.php";
}
elseif ($link == 'Home') {
    include_once "home.php";
}
elseif ($link == 'Tasks') {
    include_once "menu/task/task.php";
}
elseif ($link == 'Scan') {
    include_once "menu/scan/staff_scan_start.php";
}
elseif ($link == 'Profile') {
    include_once "menu/profile/profile.php";
}


?>