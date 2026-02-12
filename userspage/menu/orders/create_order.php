<?php
session_start();
require_once "../../../ld_db.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: ../../../loginpage/login.php");
    exit;
}

// UUID Function
function uuid()
{
    return sprintf(
        '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0xffff)
    );
}

$customer_id = $_SESSION['user_id'];
$errors = [];
$success = false;

// Fetch Active Stores
$stmt = $pdo->query("SELECT id, name, address FROM stores WHERE status='active'");
$stores = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $store_id = $_POST['store_id'] ?? '';
    $notes = trim($_POST['notes'] ?? '');
    $pickup_address = trim($_POST['pickup_address'] ?? '');
    $lat = $_POST['lat'] ?? null;
    $lng = $_POST['lng'] ?? null;

    if (!$store_id) $errors[] = "กรุณาเลือกร้านซักผ้า";
    if (!$pickup_address) $errors[] = "กรุณาระบุที่อยู่สำหรับรับผ้า";
    if (!$lat || !$lng) $errors[] = "กรุณากด 'ใช้ตำแหน่งปัจจุบัน' เพื่อปักหมุดรับผ้า";

    if (!$errors) {
        try {
            $pdo->beginTransaction();
            $order_id = uuid();
            $order_no = 'LD-' . date('ymd') . '-' . rand(1000, 9999);

            // Insert Orders
            $stmt = $pdo->prepare("INSERT INTO orders (id, customer_id, store_id, order_number, status, payment_status, notes) VALUES (?,?,?,?, 'created','pending',?)");
            $stmt->execute([$order_id, $customer_id, $store_id, $order_no, $notes]);

            // Insert Pickups
            $stmt = $pdo->prepare("INSERT INTO pickups (id, order_id, pickup_address, lat, lng, status) VALUES (?,?,?,?,?, 'scheduled')");
            $stmt->execute([uuid(), $order_id, $pickup_address, $lat, $lng]);

            // Status Log
            $stmt = $pdo->prepare("INSERT INTO order_status_logs (id, order_id, status, changed_by) VALUES (?,?, 'created',?)");
            $stmt->execute([uuid(), $order_id, $customer_id]);

            $pdo->commit();
            $success = true;
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = "ระบบขัดข้อง: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>เรียกพนักงานรับผ้า | Laundry</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600&display=swap" rel="stylesheet">

    <style>
        :root {
            --bg-body: #f0f7ff;
            --primary-blue: #3498db;
            --dark-blue: #2c3e50;
            --soft-white: #ffffff;
            --shadow-subtle: 0 10px 30px rgba(52, 152, 219, 0.1);
        }

        body {
            font-family: 'Kanit', sans-serif;
            background-color: var(--bg-body);
            color: var(--dark-blue);
        }

        /* Top Header */
        .top-nav {
            background: transparent;
            padding: 20px 0;
        }

        /* Progress Steps */
        .step-container {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            padding: 0 10px;
        }

        .step {
            text-align: center;
            flex: 1;
            position: relative;
        }

        .step-icon {
            width: 35px;
            height: 35px;
            background: #fff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 8px;
            font-size: 14px;
            border: 2px solid #e0e0e0;
            color: #b0b0b0;
            z-index: 2;
            position: relative;
        }

        .step.active .step-icon {
            background: var(--primary-blue);
            color: #fff;
            border-color: var(--primary-blue);
        }

        .step:not(:last-child):after {
            content: '';
            position: absolute;
            top: 17px;
            left: 50%;
            width: 100%;
            height: 2px;
            background: #e0e0e0;
            z-index: 1;
        }

        /* Main Card */
        .order-card {
            background: var(--soft-white);
            border-radius: 30px;
            border: none;
            box-shadow: var(--shadow-subtle);
            overflow: hidden;
            animation: slideUp 0.6s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .card-header-blue {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            padding: 40px 20px;
            text-align: center;
            color: white;
        }

        /* Form Styling */
        .form-label {
            font-weight: 500;
            font-size: 0.9rem;
            margin-bottom: 8px;
            color: #555;
        }

        .form-control,
        .form-select {
            border-radius: 15px;
            padding: 12px 18px;
            border: 1px solid #eef2f7;
            background: #fdfdfd;
            transition: 0.3s;
        }

        .form-control:focus {
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 4px rgba(52, 152, 219, 0.1);
        }

        /* GPS Button */
        .btn-gps {
            background: #e1f0ff;
            color: var(--primary-blue);
            border: none;
            border-radius: 12px;
            padding: 10px 20px;
            font-size: 0.85rem;
            font-weight: 500;
            transition: 0.3s;
        }

        .btn-gps:hover {
            background: #d0e7ff;
        }

        /* Submit Button */
        .btn-submit {
            background: var(--primary-blue);
            color: white;
            border-radius: 18px;
            padding: 15px;
            font-weight: 600;
            border: none;
            box-shadow: 0 10px 20px rgba(52, 152, 219, 0.3);
            transition: 0.3s;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            background: #2980b9;
        }

        /* Success Overlay */
        .success-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.95);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            flex-direction: column;
        }
        /* --- Dark Mode ปรับปรุงใหม่ (มืดเฉพาะโครงสร้าง) --- */
body.dark-mode {
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%) !important;
}

/* ส่วนหัวและ Header */
body.dark-mode .glass-header {
    background: rgba(30, 41, 59, 0.8) !important;
    border: 1px solid rgba(255, 255, 255, 0.1) !important;
}

body.dark-mode .glass-header h3, 
body.dark-mode .glass-header .text-muted {
    color: #ffffff !important;
}

/* สถิติด้านบน (Stat Cards) ให้มืดลงได้เพราะเป็นข้อมูลโชว์ */
body.dark-mode .stat-card:not([style*="linear-gradient"]) {
    background: rgba(30, 41, 59, 0.8) !important;
    border: 1px solid rgba(255, 255, 255, 0.1) !important;
    color: #ffffff !important;
}

/* --- จุดสำคัญ: ส่วน body.php --- */
/* เราจะให้พื้นหลังของกรอบ body มืด แต่ข้างในคงเดิม */
body.dark-mode #main-content {
    background: #1e293b !important; /* พื้นหลังกรอบใหญ่สีเข้ม */
    border: 1px solid rgba(255, 255, 255, 0.1) !important;
}

/* ยกเว้น (Reset) ทุกอย่างข้างใน #main-content ให้ใช้สีเดิมเหมือนโหมดสว่าง */
/* สิ่งนี้จะทำให้พวก Input, Table, Textbox ที่ถูก include มา ไม่โดนสีมืดกลืน */
body.dark-mode #main-content * {
    background-color: transparent; /* ให้โปร่งแสงเห็นพื้นหลังการ์ด */
}

/* ถ้าคุณอยากให้พวกช่อง Input ยังเป็นสีขาวเหมือนเดิมเป๊ะๆ ให้ล็อคไว้แบบนี้ */
body.dark-mode #main-content input, 
body.dark-mode #main-content select, 
body.dark-mode #main-content textarea,
body.dark-mode #main-content .table,
body.dark-mode #main-content .card {
    background-color: #ffffff !important;
    color: #212529 !important; /* ตัวหนังสือสีเข้มเหมือนเดิม */
}

/* ปุ่มเมนู Quick Buttons ให้คงสีขาวไว้จะได้เด่น */
body.dark-mode .quick-btn {
    background: rgba(255, 255, 255, 0.9) !important;
    color: var(--deep-blue) !important;
}

/* ปรับหัวข้อเมนูให้สว่างขึ้น */
body.dark-mode h5.fw-bold {
    color: #ffffff !important;
}
.dark-mode-toggle {
    position: fixed;
    bottom: 30px;
    right: 30px;
    width: 55px;
    height: 55px;
    border-radius: 50%;
    background: var(--primary-blue);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    cursor: pointer;
    box-shadow: 0 10px 25px rgba(0, 97, 255, 0.3);
    z-index: 100000;
    transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

.dark-mode-toggle:hover {
    transform: scale(1.1) rotate(15deg);
}
    </style>
</head>

<body>

    <?php if ($success): ?>
        <div class="success-overlay animate__animated animate__fadeIn">
            <div class="text-center">
                <div class="display-1 text-success mb-3"><i class="bi bi-check-circle-fill"></i></div>
                <h2 class="fw-bold">ส่งซักเรียบร้อย!</h2>
                <p class="text-muted">พนักงานจะติดต่อกลับหาคุณโดยเร็วที่สุด</p>
                <a href="../../index.php" class="btn btn-primary px-5 py-3 rounded-pill mt-3">กลับหน้าหลัก</a>
            </div>
        </div>
    <?php endif; ?>

    <div class="container py-4">
        <div class="top-nav d-flex align-items-center mb-4">
            <a href="../../index.php" class="text-dark fs-4"><i class="bi bi-chevron-left"></i></a>
            <h5 class="mb-0 ms-3 fw-bold">เรียกรับผ้า</h5>
        </div>

        <div class="step-container">
            <div class="step active">
                <div class="step-icon">1</div><small>ระบุข้อมูล</small>
            </div>
            <div class="step">
                <div class="step-icon">2</div><small>รอรับของ</small>
            </div>
            <div class="step">
                <div class="step-icon">3</div><small>ซักเสร็จสิ้น</small>
            </div>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-6">
                <div class="card order-card">
                    <div class="card-header-blue">
                        <i class="bi bi-truck-flatbed display-4 mb-2"></i>
                        <h4 class="fw-bold mb-0"> Laundry Pickup</h4>
                        <p class="small opacity-75 mb-0">บริการรับ-ส่งถึงหน้าบ้านคุณ</p>
                    </div>

                    <div class="card-body p-4 p-md-5">
                        <?php if ($errors): ?>
                            <div class="alert alert-danger border-0 rounded-4">
                                <?php foreach ($errors as $e): ?>
                                    <div class="small"><i class="bi bi-exclamation-circle me-2"></i><?= $e ?></div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <form method="post" id="orderForm">
                            <div class="mb-4">
                                <label class="form-label"><i class="bi bi-shop me-2"></i>เลือกร้านที่ให้บริการ</label>
                                <select name="store_id" class="form-select form-select-lg" required>
                                    <option value="">กรุณาเลือกสาขาใกล้บ้าน</option>
                                    <?php foreach ($stores as $s): ?>
                                        <option value="<?= $s['id'] ?>">
                                            📍 <?= htmlspecialchars($s['name']) ?> (<?= htmlspecialchars($s['address']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-4">
                                <label class="form-label"><i class="bi bi-geo-alt me-2"></i>ที่อยู่สำหรับรับผ้า</label>
                                <textarea name="pickup_address" id="pickup_address"
                                    class="form-control" rows="3"
                                    placeholder="บ้านเลขที่, ชื่อหมู่บ้าน, ห้องพัก..." required></textarea>

                                <div class="d-flex justify-content-between align-items-center mt-3 p-3 border rounded-4 bg-light">
                                    <div id="gpsStatus" class="small text-muted">
                                        <i class="bi bi-pin-map-fill"></i> ยังไม่ได้ปักหมุดพิกัด
                                    </div>
                                    <button type="button" onclick="getLocation()" class="btn-gps">
                                        <i class="bi bi-crosshair"></i> ใช้ตำแหน่งของฉัน
                                    </button>
                                </div>
                            </div>

                            <input type="hidden" name="lat" id="lat">
                            <input type="hidden" name="lng" id="lng">

                            <div class="mb-5">
                                <label class="form-label"><i class="bi bi-chat-dots me-2"></i>หมายเหตุเพิ่มเติม</label>
                                <input type="text" name="notes" class="form-control"
                                    placeholder="เช่น ฝากไว้ที่ป้อมยาม, ต้องการถุงเพิ่ม">
                            </div>

                            <button type="submit" class="btn btn-submit w-100 mb-3" id="submitBtn">
                                ยืนยันและเรียกรถรับผ้า
                            </button>

                            <p class="text-center text-muted small">
                                <i class="bi bi-shield-check"></i> ข้อมูลของคุณจะถูกเก็บเป็นความลับ
                            </p>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
<div class="dark-mode-toggle" onclick="toggleDarkMode()" title="สลับโหมดกลางคืน">
    <i class="bi bi-moon-stars-fill" id="dark-icon"></i>
</div>
    <script>
        function getLocation() {
            const status = document.getElementById('gpsStatus');
            if (!navigator.geolocation) {
                status.innerHTML = "❌ อุปกรณ์ไม่รองรับ GPS";
                return;
            }

            status.innerHTML = '<span class="spinner-border spinner-border-sm"></span> กำลังค้นหาตำแหน่ง...';

            navigator.geolocation.getCurrentPosition(
                pos => {
                    document.getElementById('lat').value = pos.coords.latitude;
                    document.getElementById('lng').value = pos.coords.longitude;
                    status.innerHTML = "✅ ปักหมุดพิกัดเรียบร้อย";
                    status.classList.add('text-success');
                },
                err => {
                    status.innerHTML = "❌ ไม่สามารถเข้าถึงพิกัดได้";
                    status.classList.add('text-danger');
                }
            );
        }

        const form = document.getElementById('orderForm');
        const btn = document.getElementById('submitBtn');
        form.addEventListener('submit', () => {
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> กำลังทำรายการ...';
            btn.disabled = true;
        });
           function toggleDarkMode() {
        const isDark = document.body.classList.toggle('dark-mode');
        
        // บันทึกค่าลง LocalStorage
        if (isDark) {
            localStorage.setItem('theme', 'dark');
            updateIcon(true);
        } else {
            localStorage.setItem('theme', 'light');
            updateIcon(false);
        }
    }

    function updateIcon(isDark) {
        const icon = document.getElementById('dark-icon');
        if (isDark) {
            icon.classList.replace('bi-moon-stars-fill', 'bi-sun-fill');
            icon.style.color = '#ffcc00';
        } else {
            icon.classList.replace('bi-sun-fill', 'bi-moon-stars-fill');
            icon.style.color = '#ffffff';
        }
    }
    </script>

</body>

</html>