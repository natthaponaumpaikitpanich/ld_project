<?php session_start(); ?>
 
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <link rel="stylesheet" href="../bootstrap/css/bootstrap.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Kanit:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
 <link rel="icon" href="../image/3.jpg">
    <style>
        body {
            font-family: 'Kanit', sans-serif;
            background: linear-gradient(135deg, #4eaadfff, #1cc88a);
            min-height: 100vh;
    display: flex;
    align-items: center;
        }
    </style>
</head>
<body>

<div class="container d-flex justify-content-center align-items-center vh-100">
    <div class="card shadow p-4" style="width: 380px;">
        <h3 class="text-center mb-4">เข้าสู่ระบบ</h3>

        <?php if(isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?php echo $_SESSION['error']; ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <form action="login_process.php" method="POST">
            <div class="mb-3">
                <label>Email</label>
                <input type="text" name="email" class="form-control" required>
            </div>


            <div class="mb-3">
                <label>รหัสผ่าน</label>
                <input type="password" name="password" class="form-control" required>
            </div>
<div class="mb-3">
                <label>เบอร์โทร</label>
                <input type="text" name="phone" class="form-control" required>
            </div>
            <div class="text-center">
                ยังไม่มีบัญชีใช่ไหม
            <a href="../register/register.php">สมัครนี่เลย</a>
            </div>
            <button class="btn btn-primary w-100">เข้าสู่ระบบ</button>
        </form>
    </div>
</div>

</body>
</html>
