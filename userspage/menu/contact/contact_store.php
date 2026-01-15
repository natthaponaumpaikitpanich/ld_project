<?php
session_start();
require_once "../../../ld_db.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: ../../../loginpage/login.php");
    exit;
}

$customer_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT DISTINCT
        s.id,
        s.name,
        s.phone,
        s.address
    FROM orders o
    JOIN stores s ON o.store_id = s.id
    WHERE o.customer_id = ?
    ORDER BY s.name
");
$stmt->execute([$customer_id]);
$stores = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>ติดต่อร้าน</title>
<link href="../../../bootstrap/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
<link rel="icon" href="../../../image/3.jpg">
<style>
.store-card {
    border-radius:16px;
    transition:.2s;
}
.store-card:hover {
    transform: translateY(-3px);
}
</style>
</head>

<body class="bg-light">

<div class="container py-4">

<h4 class="fw-bold mb-3">💬 ติดต่อร้านซัก</h4>

<?php if (!$stores): ?>
    <div class="alert alert-info">
        คุณยังไม่เคยใช้บริการร้านซัก
    </div>
<?php endif; ?>

<?php foreach ($stores as $s): ?>
<div class="card store-card shadow-sm mb-3">
<div class="card-body">

    <h6 class="fw-bold mb-1">
        🏪 <?= htmlspecialchars($s['name']) ?>
    </h6>
    <div class="text-muted small mb-3">
        <?= htmlspecialchars($s['address']) ?>
    </div>

    <div class="d-flex gap-2 flex-wrap">

        <?php if ($s['phone']): ?>
        <a href="tel:<?= $s['phone'] ?>"
           class="btn btn-outline-primary btn-sm">
           📞 โทร
        </a>
        <?php endif; ?>

        <?php if (!empty($s['line_id'])): ?>
        <a href="https://line.me/R/ti/p/~<?= htmlspecialchars($s['line_id']) ?>"
           target="_blank"
           class="btn btn-outline-success btn-sm">
           💬 LINE
        </a>
        <?php endif; ?>

        <a href="https://www.google.com/maps/search/?api=1&query=<?= urlencode($s['address']) ?>"
           target="_blank"
           class="btn btn-outline-secondary btn-sm">
           📍 แผนที่ร้าน
        </a>

    </div>

</div>
</div>
<?php endforeach; ?>

<a href="../../index.php" class="btn btn-outline-secondary mt-3">
← กลับ
</a>

</div>

<script src="../../../bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
