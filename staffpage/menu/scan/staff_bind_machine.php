<?php
session_start();
require_once "../../../ld_db.php";

if ($_SESSION['role'] !== 'staff') {
    die("ไม่มีสิทธิ์");
}

$machine_id = $_GET['machine_id'] ?? null;
if (!$machine_id) die("เครื่องไม่ถูกต้อง");

/* ดึง order ที่ยังไม่เสร็จ */
$stmt = $pdo->prepare("
    SELECT id, order_number
    FROM orders
    WHERE status NOT IN ('completed','cancelled')
    ORDER BY created_at DESC
");
$stmt->execute();
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>ผูกออเดอร์กับเครื่อง</title>
<link href="assets/bootstrap/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-4">
<h4>🧺 ผูกออเดอร์กับเครื่อง</h4>

<form method="post" action="staff_bind_machine_action.php">
    <input type="hidden" name="machine_id" value="<?= $machine_id ?>">

    <label class="form-label">เลือกออเดอร์</label>
    <select name="order_id" class="form-select mb-3" required>
        <option value="">-- เลือก --</option>
        <?php foreach ($orders as $o): ?>
            <option value="<?= $o['id'] ?>">
                <?= $o['order_number'] ?>
            </option>
        <?php endforeach; ?>
    </select>

    <button class="btn btn-success w-100">
        ✅ ผูกออเดอร์กับเครื่อง
    </button>
</form>
</div>

</body>
</html>
