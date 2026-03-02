<?php session_start(); ?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>Join Us | Laundry Platform</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="icon" href="../image/3.jpg">

    <style>
        :root {
            --bg-light: #eef5ff;
            --primary-soft: #60a5fa;
            --primary-deep: #2563eb;
            --accent-soft: #bfdbfe;
            --text-dark: #1e293b;
            --white: #ffffff;
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

        /* --- Animated Laundry Bubbles --- */
        .bubble-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
            pointer-events: none;
        }

        .bubble {
            position: absolute;
            background: rgba(255, 255, 255, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            bottom: -100px;
            animation: floatUp 15s infinite ease-in-out;
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

        /* --- Register Card (Glassmorphism) --- */
        .register-card {
            position: relative;
            z-index: 10;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(15px);
            border-radius: 30px;
            border: 2px solid #ffffff;
            box-shadow: 0 20px 40px rgba(37, 99, 235, 0.1);
            max-width: 480px;
            width: 95%;
            padding: 40px;
            margin: 20px 0;
        }

        /* --- Washer Logo Style (ที่คุณชอบ) --- */
        .brand-logo-container {
            width: 85px;
            height: 85px;
            background: #ffffff;
            border-radius: 22px;
            margin: 0 auto 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            box-shadow: 0 10px 25px rgba(37, 99, 235, 0.08);
            overflow: hidden;
        }

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

        /* --- Profile Section --- */
        .profile-wrapper {
            position: relative;
            width: 100px;
            margin: 0 auto 25px;
        }

        .profile-preview {
            width: 100px;
            height: 100px;
            border-radius: 30px;
            background: var(--bg-light);
            overflow: hidden;
            border: 3px solid var(--white);
            box-shadow: 0 10px 20px rgba(37, 99, 235, 0.1);
            cursor: pointer;
            transition: 0.3s ease;
        }

        .profile-preview:hover {
            transform: scale(1.05);
        }

        .profile-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* --- Form Elements --- */
        .form-control {
            border: 2px solid #f1f5f9;
            background: #f8fafc;
            border-radius: 15px;
            padding: 12px 15px;
            transition: all 0.3s;
        }

        .form-control:focus {
            box-shadow: 0 0 0 4px rgba(96, 165, 250, 0.1);
            border-color: var(--primary-soft);
            background: var(--white);
        }

        .btn-register {
            background: linear-gradient(135deg, var(--primary-soft), var(--primary-deep));
            color: white;
            border: none;
            border-radius: 15px;
            padding: 14px;
            font-weight: 600;
            transition: all 0.3s;
            box-shadow: 0 8px 15px rgba(37, 99, 235, 0.2);
        }

        .btn-register:hover {
            transform: translateY(-2px);
            filter: brightness(1.1);
            box-shadow: 0 12px 20px rgba(37, 99, 235, 0.3);
            color: white;
        }

        /* --- Role Selection Modal --- */
        .role-box {
            border: 2px solid #f1f5f9;
            border-radius: 20px;
            padding: 15px;
            text-align: center;
            cursor: pointer;
            transition: 0.3s;
            background: var(--white);
        }

        .role-box:hover {
            border-color: var(--primary-soft);
            background: var(--bg-light);
            transform: translateY(-5px);
        }

        .role-box i {
            font-size: 1.8rem;
            color: var(--primary-deep);
            display: block;
            margin-bottom: 5px;
        }

        .text-soft {
            color: #64748b;
        }
    </style>
</head>

<body>
    <div class="bubble-container" id="bubbleContainer"></div>

    <div class="register-card">
        <div class="text-center mb-4">
            <div class="brand-logo-container">
                <div class="washer-wrapper">
                    <i class="bi bi-door-closed-fill washer-body"></i>
                    <div class="washer-window"></div>
                </div>
            </div>
            <h3 class="fw-bold mb-1" style="color: var(--primary-deep);">สร้างบัญชีใหม่</h3>
            <p class="text-soft small">เข้าร่วมแพลตฟอร์มซักอบรีดอัจฉริยะ</p>
        </div>

        <form id="registerForm" method="post" action="register_action.php" enctype="multipart/form-data">

            <div class="profile-wrapper" onclick="document.getElementById('fileInput').click()">
                <div class="profile-preview">
                    <img src="../image/images.png" id="previewImg" alt="Avatar">
                </div>
                <div class="position-absolute bottom-0 end-0 bg-white shadow-sm rounded-circle p-1" style="width:30px; height:30px; display:flex; align-items:center; justify-content:center; border: 2px solid var(--primary-soft);">
                    <i class="bi bi-camera-fill text-primary" style="font-size: 0.8rem;"></i>
                </div>
            </div>
            <input type="file" name="profile_image" id="fileInput" hidden onchange="previewFile()">

            <div class="mb-3">
                <label class="form-label small fw-bold text-secondary">ชื่อผู้ใช้งาน</label>
                <div class="input-group">
                    <span class="input-group-text bg-transparent border-end-0"><i class="bi bi-person text-muted"></i></span>
                    <input type="text" class="form-control border-start-0" name="display_name" placeholder="ชื่อ-นามสกุล" required>
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label small fw-bold text-secondary">เบอร์โทรศัพท์</label>
                    <input type="tel" class="form-control" name="phone" placeholder="08x-xxx-xxxx">
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold text-secondary">อีเมล</label>
                    <input type="email" class="form-control" name="email" placeholder="example@mail.com" required>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label small fw-bold text-secondary">รหัสผ่าน</label>
                <input type="password" class="form-control" name="password" placeholder="••••••••" required>
            </div>

            <div class="mb-4">
                <label class="form-label small fw-bold text-secondary">ที่อยู่จัดส่ง / หมายเหตุ</label>
                <textarea class="form-control" name="detail" rows="2" placeholder="ระบุที่อยู่ปัจจุบันของคุณ..."></textarea>
            </div>

            <input type="hidden" name="role" id="roleInput">

            <button type="button" class="btn btn-register w-100 mb-3" data-bs-toggle="modal" data-bs-target="#roleModal">
                ถัดไป <i class="bi bi-arrow-right-short ms-1"></i>
            </button>

            <div class="text-center">
                <p class="small text-muted">มีบัญชีอยู่แล้ว? <a href="../loginpage/login.php" class="text-decoration-none fw-bold" style="color: var(--primary-deep);">เข้าสู่ระบบ</a></p>
            </div>
        </form>
    </div>

    <div class="modal fade" id="roleModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 25px;">
                <div class="modal-body p-4 p-md-5">
                    <h4 class="text-center fw-bold mb-4">คุณต้องการสมัครสมาชิกในฐานะใด?</h4>
                    <div class="row g-3">
                        <div class="col-4">
                            <div class="role-box" onclick="submitRole('customer')">
                                <i class="bi bi-person-heart"></i>
                                <span class="small fw-bold d-block">ลูกค้า</span>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="role-box" onclick="submitRole('staff')">
                                <i class="bi bi-bicycle"></i>
                                <span class="small fw-bold d-block">พนักงาน</span>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="role-box" onclick="submitRole('store_owner')">
                                <i class="bi bi-shop"></i>
                                <span class="small fw-bold d-block">เจ้าของร้าน</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // สร้างฟองสบู่
        function createBubbles() {
            const container = document.getElementById('bubbleContainer');
            for (let i = 0; i < 15; i++) {
                const bubble = document.createElement('div');
                bubble.className = 'bubble';
                const size = Math.random() * 50 + 20 + 'px';
                bubble.style.width = size;
                bubble.style.height = size;
                bubble.style.left = Math.random() * 100 + '%';
                bubble.style.animationDelay = Math.random() * 8 + 's';
                container.appendChild(bubble);
            }
        }
        createBubbles();

        function previewFile() {
            const preview = document.getElementById('previewImg');
            const file = document.querySelector('#fileInput').files[0];
            const reader = new FileReader();
            reader.onloadend = () => preview.src = reader.result;
            if (file) reader.readAsDataURL(file);
        }

        function submitRole(role) {
            document.getElementById('roleInput').value = role;
            const form = document.getElementById('registerForm');
            document.querySelector('.modal-content').style.opacity = '0.5';
            form.submit();
        }
    </script>
</body>

</html>