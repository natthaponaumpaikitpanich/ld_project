
<?php
if(isset($_GET['link'])){
    $link = $_GET['link'];
}else{
    $link="home";
}

if($link=='home'){
include_once "sidebar/sidebar.php";
}

if($link=='home'){

}elseif($link=='Dashboard'){
    include_once "sidebar/Dashboard.php";
}

if($link=='home'){

}elseif($link=='allstore'){
    include_once "stores/index.php";
}
if($link=='home'){

}elseif($link=='create'){
    include_once "sidebar/stores/create.php";
}
if($link=='home'){

}elseif($link=='setting'){
    include_once "billing/plan.php";
}
if($link=='home'){

}elseif($link=='payments'){
    include_once "billing/payments.php";
}
elseif($link=='overdue'){
    include_once "billing/overdue.php";
}
elseif($link=='transactions'){
    include_once "system/transactions.php";
}
elseif($link=='reports'){
    include_once "system/store_report.php";
}


?>