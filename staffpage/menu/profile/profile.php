<?php

$owner_id = $_SESSION['user_id']; // เจ้าของร้านที่ล็อกอินอยู่

$sql = $pdo->prepare("
    SELECT
        s.id AS store_id,
        s.name AS store_name,
        s.address,
        s.phone,
        s.status,
        s.timezone,
        s.created_at,

        u.id AS owner_id,
        u.email,
        u.display_name,
        u.phone AS owner_phone
    FROM stores s
    JOIN users u ON s.owner_id = u.id
    WHERE s.owner_id = ?
    LIMIT 1
");

$sql->execute([$owner_id]);
$store = $sql->fetch(PDO::FETCH_ASSOC);

if (!$store) {
    echo '<div class="alert alert-danger">ไม่พบข้อมูลร้าน</div>';
    return;
}
?>
<div class="container mt-4">
    <div class="card shadow">
  <div class="card-header bg-primary text-white">
  <h4>  🏪 ข้อมูลร้าน</h4>
    </div>
    </div>
<div class="container mt-4 md-4">
    <div class="card shadow">
  <div class="card-header bg-primary text-white">
<h4>ข้อมูลผู้ใช้</h4>
    </div>
    </div>
<div class="card-body">
    <div class="container ">

<p><strong>ชื่อ:</strong> <?= htmlspecialchars($store['display_name']) ?></p>
<p><strong>Email:</strong> <?= htmlspecialchars($store['email']) ?></p>
<p><strong>เบอร์โทร:</strong> <?= htmlspecialchars($store['owner_phone']) ?></p>
<div class="d-grid gap-2 d-md-flex justify-content-md-end">
<a href="index.php?link=editprofile" class="btn btn-warning ">แกไขข้อมูล</a>
</div>
</div>  </div>