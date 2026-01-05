<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    die('no permission');
}
?>
<!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<link href="../../bootstrap/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5 text-center">

<div class="card shadow-sm">
<div class="card-body py-5">

<h4 class="fw-bold mb-3">⏳ รอการยืนยันการชำระเงิน</h4>
<p class="text-muted">
ร้านกำลังตรวจสอบสลิปของคุณ<br>
เมื่อยืนยันแล้ว ระบบจะดำเนินการต่ออัตโนมัติ
</p>

<a href="../index.php" class="btn btn-outline-primary mt-3">
กลับหน้าหลัก
</a>

</div>
</div>

</div>
</body>
</html>
