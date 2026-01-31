<?php session_start(); ?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>เข้าสู่ระบบ | Laundry Platform</title>

    <link rel="stylesheet" href="../bootstrap/css/bootstrap.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" href="../image/3.jpg">

    <style>
        body {
            font-family: 'Kanit', sans-serif;
            background: linear-gradient(135deg, rgb(45, 182, 255), #2a5298);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }

        .login-card {
            background: rgba(255,255,255,0.95);
            border-radius: 16px;
            padding: 32px;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 20px 40px rgba(0,0,0,.15);
            animation: fadeUp .6s ease;
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .brand {
            text-align: center;
            margin-bottom: 20px;
        }

        .brand h4 {
            font-weight: 700;
            color: #2a5298;
        }

        .form-control {
            border-radius: 10px;
            padding: 10px 14px;
        }

        .form-control:focus {
            box-shadow: 0 0 0 0.2rem rgba(21, 197, 255, 0.25);
            border-color: #2a6898;
        }

        .btn-login {
            background: #2a5298;
            border-radius: 10px;
            font-weight: 500;
        }

        .btn-login:hover {
            background: #1e3c72;
        }

        .divider {
            text-align: center;
            margin: 16px 0;
            position: relative;
        }

        .divider::before {
            content: "";
            height: 1px;
            background: #ddd;
            width: 100%;
            position: absolute;
            top: 50%;
            left: 0;
        }

        .divider span {
            background: #fff;
            padding: 0 10px;
            position: relative;
            font-size: 13px;
            color: #777;
        }

        .google-btn {
            border-radius: 10px;
            font-weight: 500;
        }

        .small-link {
            font-size: 14px;
        }
    </style>
</head>
<body>

<div class="container d-flex justify-content-center">
    <div class="login-card">

        <div class="brand">
            <h4>ระบบซักอบรีดออนไลน์</h4>
            <small class="text-muted">Laundry Management Platform</small>
        </div>

        <?php if(isset($_SESSION['error'])): ?>
            <div class="alert alert-danger small">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <form action="login_process.php" method="POST" id="loginForm">
            <div class="mb-3">
                <label class="form-label">อีเมล</label>
                <input type="text" name="email" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="form-label">รหัสผ่าน</label>
                <input type="password" name="password" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="form-label">เบอร์โทร</label>
                <input type="text" name="phone" class="form-control" required>
            </div>

            <button class="btn btn-login text-white w-100" id="loginBtn">
                เข้าสู่ระบบ
            </button>
        </form>

        <div class="divider"><span>หรือ</span></div>

        <a href="google_login.php"
           class="btn btn-outline-secondary w-100 d-flex align-items-center justify-content-center gap-2 google-btn">
            <img src="../image/Google__G__logo.svg.png" style="width:18px">
            สมัคร / เข้าสู่ระบบด้วย Google
        </a>

        <div class="text-center mt-3 small-link">
            ยังไม่มีบัญชี?
            <a href="../register/register.php">สมัครสมาชิก</a>
        </div>
    </div>
</div>

<script>
    const form = document.getElementById('loginForm');
    const btn = document.getElementById('loginBtn');

    form.addEventListener('submit', () => {
        btn.innerHTML = 'กำลังเข้าสู่ระบบ...';
        btn.disabled = true;
    });
</script>

</body>
</html>
