<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <link rel="stylesheet" href="../bootstrap-5.3.3-dist/css/bootstrap.min.css">
</head>
<body class="bg-light">

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
            <button class="btn btn-primary w-100">เข้าสู่ระบบ</button>
        </form>
    </div>
</div>

</body>
</html>
