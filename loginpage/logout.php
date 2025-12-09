<?php
session_start();
// ถ้าต้องการลบเฉพาะ session บางค่า ให้ unset เฉพาะ
// unset($_SESSION['user_id'], $_SESSION['role'], $_SESSION['user_name']);
session_destroy();
header("Location: login.php");
exit;
?>
