<?php
require_once "../../../ld_db.php";

$sql = "DELETE FROM reports";
$pdo->exec($sql);

header("Location: ../sidebar.php?link=reports");
exit;