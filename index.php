<?php
session_start();

if (isset($_SESSION['user_id'])) {
    // redirect ตาม role
    switch ($_SESSION['role']) {
        case 'customer':
            header("Location: userspage/index.php");
            break;

        case 'store_owner':
        case 'staff':
            header("Location: storepage/index.php?link=orders");
            break;

        case 'platform_admin':
            header("Location: adminpage/sidebar/sidebar.php?link=Dashboard");
            break;

        default:
            session_destroy();
            header("Location: index.php");
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>Laundry Delivery System</title>
<link href="bootstrap/css/bootstrap.min.css" rel="stylesheet">
<style>
.hero {
    min-height: 100vh;
    display: flex;
    align-items: center;
}
</style>
</head>
<body>

<div class="container hero">
    <div class="row w-100 align-items-center">
        <div class="col-md-6">
            <h1 class="fw-bold mb-3">🧺 ระบบซักอบรีดออนไลน์</h1>
            <p class="text-muted mb-4">
                สั่งซักผ้า ติดตามสถานะ แจ้งราคา และจัดการงานซักแบบครบวงจร
            </p>

            <div class="d-flex gap-3">
                <a href="register/register.php" class="btn btn-primary btn-lg">
                    สมัครสมาชิก
                </a>
                <a href="loginpage/login.php" class="btn btn-outline-secondary btn-lg">
                    เข้าสู่ระบบ
                </a>
            </div>
        </div>

        <div class="col-md-6 text-center">
            <img src="image/222.jpg" class="img-fluid" alt="Laundry">
        </div>
    </div>
</div>

</body>
</html>
