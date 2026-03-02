<?php session_start(); ?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>เข้าสู่ระบบ | Laundry Platform</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="../image/3.jpg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600&display=swap" rel="stylesheet">

    <style>
        :root {
            --bg-light: #eef5ff;
            --primary-soft: #60a5fa;
            --primary-deep: #2563eb;
            --accent-soft: #bfdbfe;
            --text-dark: #1e293b;
        }

        body {
            font-family: 'Kanit', sans-serif;
            background-color: var(--bg-light);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow-x: hidden;
            margin: 0;
            position: relative;
        }

        /* --- Background Animation --- */
        .laundry-bg-animation {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            pointer-events: none;
        }

        .bubble {
            position: absolute;
            background: rgba(255, 255, 255, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.8);
            border-radius: 50%;
            bottom: -100px;
            animation: floatUp 15s infinite ease-in-out;
        }

        .floating-cloth {
            position: absolute;
            font-size: 2rem;
            color: var(--accent-soft);
            opacity: 0.3;
            animation: swing 6s infinite ease-in-out;
        }

        @keyframes floatUp {
            0% {
                transform: translateY(0) scale(1);
                opacity: 0.8;
            }

            100% {
                transform: translateY(-110vh) scale(1.5);
                opacity: 0;
            }
        }

        @keyframes swing {

            0%,
            100% {
                transform: rotate(-10deg) translateY(0);
            }

            50% {
                transform: rotate(10deg) translateY(-20px);
            }
        }

        /* --- Login Card --- */
        .login-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border: 2px solid #ffffff;
            border-radius: 30px;
            padding: 40px;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 20px 40px rgba(37, 99, 235, 0.1);
            z-index: 10;
        }

        /* --- Professional Washer Logo (No Shake on Container) --- */
        .brand-logo {
            width: 85px;
            height: 85px;
            background: #ffffff;
            border-radius: 22px;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            box-shadow: 0 10px 25px rgba(37, 99, 235, 0.08);
            overflow: hidden;
            /* เก็บส่วนประกอบไว้ในกรอบ */
        }

        /* ให้ไอคอนข้างในสั่นแทน กรอบข้างนอกจะได้นิ่ง */
        .washer-wrapper {
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            animation: machineVibrate 0.4s infinite linear;
        }

        .washer-body {
            font-size: 55px;
            color: var(--primary-deep);
            z-index: 1;
        }

        .washer-window {
            position: absolute;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: #f1f5f9;
            border: 2px solid var(--primary-soft);
            z-index: 2;
            top: 55%;
            /* ปรับให้ตรงกลางประตูเครื่องซักผ้า */
            left: 50%;
            transform: translate(-50%, -50%);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .washer-window::after {
            content: '🫧';
            font-size: 14px;
            animation: bubbleSpin 2s infinite linear;
        }

        @keyframes machineVibrate {
            0% {
                transform: translate(0, 0);
            }

            25% {
                transform: translate(1px, -1px);
            }

            50% {
                transform: translate(-1px, 1px);
            }

            75% {
                transform: translate(1px, 1px);
            }

            100% {
                transform: translate(0, 0);
            }
        }

        @keyframes bubbleSpin {
            from {
                transform: rotate(0deg);
            }

            to {
                transform: rotate(360deg);
            }
        }

        h4 {
            font-weight: 700;
            color: var(--text-dark);
            text-align: center;
            margin-bottom: 5px;
        }

        .subtitle {
            color: #64748b;
            text-align: center;
            font-size: 0.9rem;
            margin-bottom: 30px;
        }

        /* --- Form Elements --- */
        .form-floating>.form-control {
            border: 2px solid #f1f5f9;
            border-radius: 15px;
            background-color: #f8fafc;
        }

        .form-floating>.form-control:focus {
            border-color: var(--primary-soft);
            box-shadow: 0 0 0 4px rgba(96, 165, 250, 0.1);
        }

        .btn-submit {
            background: linear-gradient(135deg, var(--primary-soft), var(--primary-deep));
            border: none;
            border-radius: 15px;
            padding: 12px;
            font-weight: 600;
            color: white;
            transition: all 0.3s;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            filter: brightness(1.1);
        }

        .google-btn {
            border: 2px solid #f1f5f9;
            border-radius: 15px;
            padding: 10px;
            font-weight: 500;
            color: #475569;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .divider {
            display: flex;
            align-items: center;
            margin: 25px 0;
            color: #94a3b8;
            font-size: 0.8rem;
        }

        .divider::before,
        .divider::after {
            content: "";
            flex: 1;
            height: 1px;
            background: #e2e8f0;
        }

        .divider span {
            padding: 0 10px;
        }

        .toggle-pw {
            position: absolute;
            right: 15px;
            top: 18px;
            cursor: pointer;
            color: #94a3b8;
            z-index: 5;
        }
    </style>
</head>

<body>

    <div class="laundry-bg-animation">
        <div class="bubble" style="left: 10%; width: 40px; height: 40px; animation-delay: 0s;"></div>
        <div class="bubble" style="left: 25%; width: 20px; height: 20px; animation-delay: 2s;"></div>
        <div class="bubble" style="left: 60%; width: 60px; height: 60px; animation-delay: 4s;"></div>
        <div class="bubble" style="left: 80%; width: 30px; height: 30px; animation-delay: 1s;"></div>
        <i class="bi bi-tsunami floating-cloth" style="top: 15%; left: 10%;"></i>
        <i class="bi bi-tag floating-cloth" style="top: 70%; left: 85%; animation-delay: 1s;"></i>
        <i class="bi bi-wind floating-cloth" style="top: 20%; left: 80%; animation-delay: 2s;"></i>
    </div>

    <div class="login-card">
        <div class="brand-logo">
            <div class="washer-wrapper">
                <i class="bi bi-door-closed-fill washer-body"></i>
                <div class="washer-window"></div>
            </div>
        </div>

        <h4>Laundry Service</h4>
        <p class="subtitle">ความสะอาดที่ส่งตรงถึงหน้าบ้านคุณ</p>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger py-2 border-0 rounded-4 small mb-4">
                <i class="bi bi-exclamation-circle me-2"></i><?php echo $_SESSION['error'];
                                                                unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <form action="login_process.php" method="POST" id="loginForm">
            <div class="form-floating mb-3">
                <input type="email" name="email" class="form-control" id="fEmail" placeholder="Email" required>
                <label for="fEmail">อีเมลผู้ใช้งาน</label>
            </div>

            <div class="form-floating mb-4" style="position: relative;">
                <input type="password" name="password" class="form-control" id="fPassword" placeholder="Password" required>
                <label for="fPassword">รหัสผ่าน</label>
                <i class="bi bi-eye toggle-pw" id="toggleIcon" onclick="togglePassword()"></i>
            </div>

            <button type="submit" class="btn btn-submit w-100 mb-3" id="submitBtn">
                <span id="btnText">เข้าสู่ระบบ</span>
                <span id="loader" class="spinner-border spinner-border-sm d-none"></span>
            </button>
        </form>

        <div class="divider"><span>หรือเข้าสู่ระบบด้วย</span></div>

        <a href="google_login.php" class="google-btn mb-4">
            <img src="https://www.gstatic.com/images/branding/product/1x/gsa_512dp.png" width="20">
            ดำเนินการต่อด้วย Google
        </a>

        <div class="text-center">
            <span class="text-muted small">ยังไม่มีบัญชี?</span>
            <a href="../register/register.php" class="text-decoration-none fw-bold ms-1" style="color: var(--primary-deep);">สมัครสมาชิกฟรี</a>
        </div>
    </div>

    <script>
        function togglePassword() {
            const pwd = document.getElementById('fPassword');
            const icon = document.getElementById('toggleIcon');
            if (pwd.type === 'password') {
                pwd.type = 'text';
                icon.classList.replace('bi-eye', 'bi-eye-slash');
            } else {
                pwd.type = 'password';
                icon.classList.replace('bi-eye-slash', 'bi-eye');
            }
        }

        document.getElementById('loginForm').onsubmit = function() {
            document.getElementById('btnText').innerText = 'กำลังประมวลผล...';
            document.getElementById('loader').classList.remove('d-none');
            document.getElementById('submitBtn').disabled = true;
        };
    </script>
</body>

</html>