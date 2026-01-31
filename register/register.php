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

    <style>
        :root {
            --soft-blue: #e3f2fd;
            --main-blue: #4a90e2;
            --deep-blue: #2c5282;
            --white: #ffffff;
            --bg-gradient: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        }

        body {
            font-family: 'Kanit', sans-serif;
            background: var(--bg-gradient);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow-x: hidden;
            margin: 0;
        }

        /* --- Animated Laundry Bubbles --- */
        .bubble-container {
            position: fixed;
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
            animation: float 15s infinite ease-in;
        }

        @keyframes float {
            0% {
                transform: translateY(0) scale(1);
                opacity: 0;
            }

            20% {
                opacity: 0.8;
            }

            100% {
                transform: translateY(-120vh) scale(1.5);
                opacity: 0;
            }
        }

        /* --- Glassmorphism Card --- */
        .register-card {
            position: relative;
            z-index: 10;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(15px);
            border-radius: 30px;
            border: 1px solid rgba(255, 255, 255, 0.8);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.05);
            max-width: 480px;
            width: 95%;
            padding: 40px;
            transition: transform 0.3s ease;
        }

        .brand-logo {
            font-size: 2.5rem;
            color: var(--main-blue);
            margin-bottom: 5px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.05);
        }

        /* --- Profile Section --- */
        .profile-wrapper {
            position: relative;
            width: 110px;
            margin: 0 auto 25px;
        }

        .profile-preview {
            width: 110px;
            height: 110px;
            border-radius: 35% 65% 65% 35% / 30% 30% 70% 70%;
            /* Organic Water Drop Shape */
            background: var(--soft-blue);
            overflow: hidden;
            border: 3px solid var(--white);
            box-shadow: 0 10px 20px rgba(74, 144, 226, 0.2);
            cursor: pointer;
            transition: 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .profile-preview:hover {
            transform: scale(1.05) rotate(5deg);
            border-radius: 50%;
        }

        .profile-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* --- Form Elements --- */
        .form-control {
            border: 1px solid #e0e7ff;
            background: #fcfdff;
            border-radius: 15px;
            padding: 12px 15px;
            transition: all 0.3s;
        }

        .form-control:focus {
            box-shadow: 0 0 0 4px rgba(74, 144, 226, 0.1);
            border-color: var(--main-blue);
            background: var(--white);
        }

        .btn-register {
            background: var(--main-blue);
            color: white;
            border: none;
            border-radius: 15px;
            padding: 14px;
            font-weight: 500;
            letter-spacing: 0.5px;
            transition: all 0.3s;
            box-shadow: 0 8px 15px rgba(74, 144, 226, 0.3);
        }

        .btn-register:hover {
            background: var(--deep-blue);
            transform: translateY(-2px);
            box-shadow: 0 12px 20px rgba(74, 144, 226, 0.4);
        }

        /* --- Role Selection Modal --- */
        .role-box {
            border: 2px solid #f0f4f8;
            border-radius: 20px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: 0.3s;
            background: var(--white);
        }

        .role-box:hover {
            border-color: var(--main-blue);
            background: var(--soft-blue);
            transform: translateY(-5px);
        }

        .role-box i {
            font-size: 2rem;
            color: var(--main-blue);
            display: block;
            margin-bottom: 10px;
        }

        .text-soft {
            color: #7f8c8d;
        }
    </style>
</head>

<body>

    <div class="bubble-container" id="bubbleContainer"></div>

    <div class="register-card">
        <div class="text-center mb-4">
            <div class="brand-logo"><i class="bi bi-droplet-half"></i></div>
            <h3 class="fw-bold mb-1" style="color: var(--deep-blue);">สร้างบัญชีใหม่</h3>
            <p class="text-soft small">เข้าสู่แพลตฟอร์มซักอบรีดอัจฉริยะ</p>
        </div>

        <form id="registerForm" method="post" action="register_action.php" enctype="multipart/form-data">

            <div class="profile-wrapper" onclick="document.getElementById('fileInput').click()">
                <div class="profile-preview">
                    <img src="../image/images.png" id="previewImg" alt="Avatar">
                </div>
                <div class="position-absolute bottom-0 end-0 bg-white shadow-sm rounded-circle p-1" style="width:30px; height:30px; display:flex; align-items:center; justify-content:center;">
                    <i class="bi bi-plus-circle-fill text-primary"></i>
                </div>
            </div>
            <input type="file" name="profile_image" id="fileInput" hidden onchange="previewFile()">

            <div class="mb-3">
                <label class="form-label small fw-bold text-secondary">ชื่อผู้ใช้งาน</label>
                <div class="input-group">
                    <span class="input-group-text bg-transparent border-end-0"><i class="bi bi-person text-muted"></i></span>
                    <input type="text" class="form-control border-start-0" name="display_name" placeholder="ระบุชื่อของคุณ" required>
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label small fw-bold text-secondary">เบอร์โทรศัพท์</label>
                    <input type="tel" class="form-control" name="phone" placeholder="08x-xxx-xxxx">
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold text-secondary">อีเมล</label>
                    <input type="email" class="form-control" name="email" placeholder="ตัวอย่าง@mail.com" required>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label small fw-bold text-secondary">รหัสผ่าน</label>
                <input type="password" class="form-control" name="password" placeholder="••••••••" required>
            </div>

            <div class="mb-4">
                <label class="form-label small fw-bold text-secondary">ที่อยู่จัดส่ง / หมายเหตุ</label>
                <textarea class="form-control" name="detail" rows="2" placeholder="เช่น บ้านเลขที่, จุดสังเกต..."></textarea>
            </div>

            <input type="hidden" name="role" id="roleInput">

            <button type="button" class="btn btn-register w-100 mb-3" data-bs-toggle="modal" data-bs-target="#roleModal">
                เริ่มต้นใช้งาน <i class="bi bi-magic ms-2"></i>
            </button>

            <div class="text-center">
                <p class="small text-muted">เป็นสมาชิกอยู่แล้ว? <a href="../loginpage/login.php" class="text-decoration-none fw-bold" style="color: var(--main-blue);">เข้าสู่ระบบ</a></p>
            </div>
        </form>
    </div>

    <div class="modal fade" id="roleModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 25px;">
                <div class="modal-body p-5">
                    <h4 class="text-center fw-bold mb-4">คุณต้องการใช้งานในฐานะใด?</h4>
                    <div class="row g-3">
                        <div class="col-4">
                            <div class="role-box" onclick="submitRole('customer')">
                                <i class="bi bi-bag-heart"></i>
                                <span class="small fw-bold">ลูกค้า</span>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="role-box" onclick="submitRole('staff')">
                                <i class="bi bi-bicycle"></i>
                                <span class="small fw-bold">พนักงาน</span>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="role-box" onclick="submitRole('store_owner')">
                                <i class="bi bi-shop-window"></i>
                                <span class="small fw-bold">เจ้าของร้าน</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // สร้างฟองสบู่ Laundry Bubbles
        function createBubbles() {
            const container = document.getElementById('bubbleContainer');
            for (let i = 0; i < 15; i++) {
                const bubble = document.createElement('div');
                bubble.className = 'bubble';
                const size = Math.random() * 60 + 20 + 'px';
                bubble.style.width = size;
                bubble.style.height = size;
                bubble.style.left = Math.random() * 100 + '%';
                bubble.style.animationDelay = Math.random() * 10 + 's';
                bubble.style.animationDuration = Math.random() * 10 + 10 + 's';
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
            // เพิ่ม Animation เล็กน้อยก่อนส่ง
            document.querySelector('.modal-content').style.opacity = '0';
            setTimeout(() => form.submit(), 300);
        }
    </script>
</body>

</html>