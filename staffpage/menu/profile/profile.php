<?php


if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    die("ไม่มีสิทธิ์เข้าถึง");
}

$staff_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT 
        display_name,
        phone,
        email
    FROM users
    WHERE id = ?
      AND role = 'staff'
");
$stmt->execute([$staff_id]);
$staff = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$staff) {
    die("ไม่พบข้อมูลพนักงาน");
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>โปรไฟล์พนักงาน</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

</head>
<body">

<div class="container mt-4 mb-5">

<h4 class="fw-bold mb-3">👤 แก้ไขโปรไฟล์พนักงาน</h4>

<div class="card shadow-sm">
<div class="card-body">

<form method="post" action="menu/profile/edit.php">

    <div class="mb-3">
        <label class="form-label">ชื่อที่แสดง</label>
        <input type="text" name="display_name"
               class="form-control" required
               value="<?= htmlspecialchars($staff['display_name']) ?>">
    </div>

    <div class="mb-3">
        <label class="form-label">เบอร์โทร</label>
        <input type="text" name="phone"
               class="form-control"
               value="<?= htmlspecialchars($staff['phone']) ?>">
    </div>

    <div class="mb-3">
        <label class="form-label">อีเมล</label>
        <input type="email" name="email"
               class="form-control"
               value="<?= htmlspecialchars($staff['email']) ?>">
    </div>

    <hr>

 

    <button class="btn btn-success w-100">
        💾 บันทึกข้อมูล
    </button>

</form>

</div>
</div>

</div>

<script src="../../assets/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
