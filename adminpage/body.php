<?php
$link = $_GET['link'] ?? 'Dashboard';
?>

<!-- ===== SIDEBAR (อยู่นอก content) ===== -->
<?php include_once "sidebar/sidebar.php"; ?>

<!-- ===== CONTENT AREA ===== -->
<div id="mainContent" class="main-content">
<?php

if ($link == 'Dashboard') {
    include_once "sidebar/Dashboard.php";
}
elseif ($link == 'promotion') {
    include_once "../promotion/index.php";
}
elseif ($link == 'allstore') {
    include_once "stores/index.php";
}
elseif ($link == 'setting') {
    include_once "billing/plan.php";
}
elseif ($link == 'payments') {
    include_once "billing/payments.php";
}
elseif ($link == 'overdue') {
    include_once "billing/overdue.php";
}
elseif ($link == 'transactions') {
    include_once "system/transactions.php";
}
elseif ($link == 'reports') {
    include_once "system/store_report.php";
}
else {
    include_once "sidebar/Dashboard.php";
}

?>
</div>
