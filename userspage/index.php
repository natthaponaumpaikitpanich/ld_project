<?php
session_start();
require_once "../ld_db.php";
include_once "assets/head.php";
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: ../loginpage/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("
    SELECT 
        p.*,
        s.name AS store_name
    FROM promotions p
    INNER JOIN stores s ON p.store_id = s.id
    WHERE p.status = 'active'
      AND p.store_id IS NOT NULL
      AND p.audience IN ('all','customers','store_specific')
      AND p.start_date <= NOW()
      AND p.end_date >= NOW()
    ORDER BY p.created_at DESC
    LIMIT 10
");
$stmt->execute();
$promotions = $stmt->fetchAll(PDO::FETCH_ASSOC);
$stmt = $pdo->prepare("
    SELECT display_name, email, phone, profile_image
    FROM users
    WHERE id = ?
");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>หน้าหลักลูกค้า</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="../bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;600&display=swap" rel="stylesheet">
    <title>ระบบร้านซักอบรีด</title>
</head>

<body>
    <div class="container py-4">

        <!-- PROFILE -->
        <nav class="navbar navbar-expand-lg bg-white sticky-top">
    <div class="container">
        <span class="navbar-brand fw-bold">🧺 Laundry System</span>

        <div class="ms-auto d-flex align-items-center gap-3">
            <div class="text-end">
                <div class="fw-semibold"><?= htmlspecialchars($user['display_name']) ?></div>
                <small class="text-muted">ลูกค้า</small>
            </div>
            <img src="../<?= $user['profile_image'] ?: 'assets/default-user.png' ?>" class="profile-img">
            <a href="../loginpage/logout.php" class="btn btn-outline-danger btn-sm">
                ออกจากระบบ
            </a>
        </div>
    </div>
</nav>
        <?php if ($promotions): ?>
            <div id="promoCarousel" class="carousel slide" data-bs-ride="carousel">
                <div class="carousel-inner">

                    <?php foreach ($promotions as $index => $p): ?>
                        <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
                            <div class="card shadow-sm border-0">

                                <?php if (!empty($p['image'])): ?>
                                    <img src="../<?= htmlspecialchars($p['image']) ?>"
                                        class="d-block w-100 promo-img">
                                <?php endif; ?>
                                <div class="card-body">

                                </div>

                            </div>
                        </div>
                    <?php endforeach; ?>

                </div>

                <!-- controls -->
                <button class="carousel-control-prev" type="button" data-bs-target="#promoCarousel" data-bs-slide="prev">
                    <span class="carousel-control-prev-icon"></span>
                </button>

                <button class="carousel-control-next" type="button" data-bs-target="#promoCarousel" data-bs-slide="next">
                    <span class="carousel-control-next-icon"></span>
                </button>
            </div>
        <?php else: ?>
            <div class="alert alert-light text-center">
                ยังไม่มีโปรโมชั่นตอนนี้
            </div>
        <?php endif; ?>
        <!-- MENU -->
        <div class="row g-3">

            <div class="col-6">
                <a href="menu/orders/create_order.php" class="text-decoration-none text-dark">
                    <div class="card card-menu text-center p-3">
                        <i class="bi bi-basket fs-1 text-primary"></i>
                        <div class="fw-semibold mt-2">ส่งซักผ้า</div>
                    </div>
                </a>
            </div>

            <div class="col-6">
                <a href="index.php?link=orders" class="text-decoration-none text-dark">
                    <div class="card card-menu text-center p-3">
                        <i class="bi bi-clock-history fs-1 text-success"></i>
                        <div class="fw-semibold mt-2">ติดตามสถานะ</div>
                    </div>
                </a>
            </div>

            <div class="col-6">
                <a href="payments.php" class="text-decoration-none text-dark">
                    <div class="card card-menu text-center p-3">
                        <i class="bi bi-credit-card fs-1 text-warning"></i>
                        <div class="fw-semibold mt-2">ประวัติการชำระเงิน</div>
                    </div>
                </a>
            </div>

            <div class="col-6">
                <a href="index.php?link=profile" class="text-decoration-none text-dark">
                    <div class="card card-menu text-center p-3">
                        <i class="bi bi-person-circle fs-1 text-info"></i>
                        <div class="fw-semibold mt-2">โปรไฟล์</div>
                    </div>
                </a>
            </div>
<?php include_once "body.php"; ?>
        </div>

    </div>

    <script src="../bootstrap/js/bootstrap.bundle.min.js"></script>
</body>

</html>