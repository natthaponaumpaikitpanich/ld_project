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
<title>ติดต่อร้าน | Laundry Platform</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<link href="../../../bootstrap/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="icon" href="../../../image/3.jpg">

<style>
body{
    font-family:'Kanit',sans-serif;
    background:#f6f7fb;
}

/* CARD */
.store-card{
    border-radius:20px;
    border:none;
    box-shadow:0 12px 30px rgba(0,0,0,.08);
    transition:.25s ease;
}
.store-card:hover{
    transform:translateY(-4px);
    box-shadow:0 18px 40px rgba(0,0,0,.12);
}

/* STORE ICON */
.store-avatar{
    width:46px;
    height:46px;
    border-radius:12px;
    background:#2a5298;
    color:#fff;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:22px;
}
</style>
</head>

<body>

<div class="container py-4">

<h5 class="fw-semibold mb-4">💬 ติดต่อร้านซัก</h5>

<?php if (!$stores): ?>
<div class="alert alert-info text-center">
    คุณยังไม่เคยใช้บริการร้านซัก
</div>
<?php endif; ?>

<?php foreach ($stores as $s): ?>
<div class="card store-card mb-3">
<div class="card-body d-flex justify-content-between align-items-center">

    <div class="d-flex gap-3 align-items-center">
        <div class="store-avatar">
            🏪
        </div>
        <div>
            <div class="fw-semibold"><?= htmlspecialchars($s['name']) ?></div>
            <small class="text-muted">
                <?= htmlspecialchars($s['address']) ?>
            </small>
             <div class="d-flex gap-2 flex-wrap">

        <?php if ($s['phone']): ?>
        <a href="tel:<?= $s['phone'] ?>"
           class="btn btn-outline-primary btn-sm rounded-pill">
           📞 โทร
        </a>
        <?php endif; ?>

        <a href="https://www.google.com/maps/search/?api=1&query=<?= urlencode($s['address']) ?>"
           target="_blank"
           class="btn btn-outline-secondary btn-sm rounded-pill">
           📍 แผนที่
        </a>

    </div>
        </div>
    </div>

   

</div>
</div>
<?php endforeach; ?>

<a href="../../index.php" class="btn btn-outline-secondary rounded-pill w-100 mt-3">
← กลับหน้าหลัก
</a>

</div>

<script src="../../../bootstrap/js/bootstrap.bundle.min.js"></script>

<script>
/* tap animation */
document.querySelectorAll('.store-card').forEach(card=>{
    card.addEventListener('click',()=>{
        card.style.transform='scale(.98)';
        setTimeout(()=>card.style.transform='',120);
    });
});
</script>

</body>
</html>
