<?php
session_start();
require_once "../ld_db.php";

/* AUTH */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'store_owner') {
    die("no permission");
}

$user_id = $_SESSION['user_id'];

/* check existing */
$stmt = $pdo->prepare("SELECT id FROM stores WHERE owner_id=? LIMIT 1");
$stmt->execute([$user_id]);
$exists = $stmt->fetchColumn();

if ($exists) {
    header("Location: index.php");
    exit;
}

/* submit */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name    = trim($_POST['name'] ?? '');
    $phone   = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');

    if (!$name || !$phone) {
        $error = "กรุณากรอกข้อมูลให้ครบ";
    } else {
        $store_id = $pdo->query("SELECT UUID()")->fetchColumn();

        $stmt = $pdo->prepare("
            INSERT INTO stores
            (id, owner_id, name, phone, address, status)
            VALUES (?, ?, ?, ?, ?, 'pending')
        ");
        $stmt->execute([$store_id,$user_id,$name,$phone,$address]);

        $_SESSION['store_id']   = $store_id;
        $_SESSION['store_name'] = $name;

        header("Location: index.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>สร้างร้านซักอบรีด | Laundry Admin</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<link rel="icon" href="../image/3.jpg">
<link href="../bootstrap/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<style>
    :root {
        --primary-color: #3b82f6;
        --secondary-color: #1e3c72;
        --glass-bg: rgba(255, 255, 255, 0.9);
    }

    body {
        font-family: 'Kanit', sans-serif;
        background: linear-gradient(-45deg, #1e3c72, #2a5298, #2193b0, #6dd5ed);
        background-size: 400% 400%;
        animation: gradientBG 15s ease infinite;
        min-height: 100vh;
        display: flex;
        align-items: center;
        overflow-x: hidden;
    }

    @keyframes gradientBG {
        0% { background-position: 0% 50%; }
        50% { background-position: 100% 50%; }
        100% { background-position: 0% 50%; }
    }

    .setup-container {
        perspective: 1000px;
    }

    .card {
        border: none;
        border-radius: 30px;
        background: var(--glass-bg);
        backdrop-filter: blur(10px);
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        border: 1px solid rgba(255, 255, 255, 0.3);
        padding: 40px;
        transform-style: preserve-3d;
        animation: cardEntry 0.8s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }

    @keyframes cardEntry {
        from { opacity: 0; transform: translateY(30px) rotateX(-10deg); }
        to { opacity: 1; transform: translateY(0) rotateX(0deg); }
    }

    .brand-icon {
        width: 80px;
        height: 80px;
        background: white;
        border-radius: 22px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2.5rem;
        margin: -80px auto 20px;
        box-shadow: 0 15px 35px rgba(0,0,0,0.1);
        animation: float 3s ease-in-out infinite;
    }

    @keyframes float {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-10px); }
    }

    .form-label {
        font-weight: 600;
        color: #475569;
        margin-left: 5px;
        font-size: 0.9rem;
    }

    .input-group-text {
        background: transparent;
        border: 2px solid #e2e8f0;
        border-right: none;
        border-radius: 15px 0 0 15px;
        color: #64748b;
    }

    .form-control {
        border-radius: 15px;
        padding: 12px 18px;
        border: 2px solid #e2e8f0;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        font-size: 1rem;
    }

    .form-control:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
        transform: translateX(5px);
    }

    /* สำหรับ Input Group ที่มี Icon */
    .input-group .form-control {
        border-radius: 0 15px 15px 0;
        border-left: none;
    }

    .btn-main {
        background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
        border: none;
        border-radius: 18px;
        padding: 15px;
        font-weight: 700;
        letter-spacing: 0.5px;
        text-transform: uppercase;
        transition: all 0.4s;
        position: relative;
        overflow: hidden;
        color: white;
    }

    .btn-main:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 20px rgba(30, 60, 114, 0.3);
        color: white;
        filter: brightness(1.1);
    }

    .btn-main::after {
        content: '';
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: rgba(255,255,255,0.1);
        transform: rotate(45deg);
        transition: 0.5s;
    }

    .btn-main:hover::after {
        left: 120%;
    }

    .progress-dots {
        display: flex;
        justify-content: center;
        gap: 10px;
        margin-bottom: 30px;
    }

    .dot {
        width: 30px;
        height: 6px;
        border-radius: 10px;
        background: #e2e8f0;
    }

    .dot.active {
        background: var(--primary-color);
        width: 50px;
    }

    .step-text {
        font-size: 0.8rem;
        color: #94a3b8;
        text-transform: uppercase;
        font-weight: 700;
    }
</style>
</head>

<body>

<div class="container setup-container">
    <div class="row justify-content-center">
        <div class="col-md-5">

            <div class="card shadow-lg">
                <div class="brand-icon">
                    <i class="bi bi-shop text-primary"></i>
                </div>

                <div class="text-center mb-4">
                    <span class="step-text">Setup Process</span>
                    <h3 class="fw-bold text-dark mt-1">เริ่มต้นสร้างร้าน</h3>
                    <p class="text-muted small">บันทึกข้อมูลเบื้องต้นเพื่อเข้าสู่ระบบหลังร้าน</p>
                </div>

                <div class="progress-dots">
                    <div class="dot active"></div>
                    <div class="dot"></div>
                    <div class="dot"></div>
                </div>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger border-0 rounded-4 text-center small animate__animated animate__shakeX">
                        <i class="bi bi-exclamation-circle-fill me-2"></i> <?= $error ?>
                    </div>
                <?php endif; ?>

                <form method="post" id="storeForm">

                    <div class="mb-3">
                        <label class="form-label"><i class="bi bi-tag-fill me-1"></i> ชื่อร้าน</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-house-door"></i></span>
                            <input type="text" name="name"
                                   class="form-control"
                                   placeholder="เช่น สะดวกซัก สาขา 1"
                                   required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><i class="bi bi-telephone-fill me-1"></i> เบอร์โทรร้าน</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-phone"></i></span>
                            <input type="text" name="phone"
                                   class="form-control"
                                   placeholder="เช่น 089xxxxxxx"
                                   required>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label"><i class="bi bi-geo-alt-fill me-1"></i> ที่อยู่ร้าน</label>
                        <textarea name="address"
                                  class="form-control"
                                  rows="3"
                                  placeholder="บ้านเลขที่, ถนน, แขวง/ตำบล..."></textarea>
                    </div>

                    <button type="submit" class="btn btn-main w-100 py-3" id="submitBtn">
                        <i class="bi bi-plus-circle-dotted me-2"></i> สร้างร้านค้าตอนนี้
                    </button>

                </form>

                <div class="text-center mt-4">
                    <small class="text-muted">Laundry Management System v2.0</small>
                </div>

            </div>

        </div>
    </div>
</div>

<script src="../bootstrap/js/bootstrap.bundle.min.js"></script>

<script>
    const form = document.getElementById('storeForm');
    const btn = document.getElementById('submitBtn');

    form.addEventListener('submit', () => {
        // เอฟเฟกต์ตอนกดส่ง
        btn.disabled = true;
        btn.innerHTML = `
            <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
            กำลังจัดเตรียมระบบ...
        `;
        
        // เพิ่มลูกเล่นเขย่าปุ่มเบาๆ
        btn.style.transform = "scale(0.95)";
    });

    // ลูกเล่นตอน Focus Input ให้ Card มีการเอียงตามเล็กน้อย (Micro-interaction)
    const inputs = document.querySelectorAll('.form-control');
    const card = document.querySelector('.card');

    inputs.forEach(input => {
        input.addEventListener('focus', () => {
            card.style.transition = "0.5s";
            card.style.transform = "translateY(-5px) scale(1.01)";
        });
        input.addEventListener('blur', () => {
            card.style.transform = "translateY(0) scale(1)";
        });
    });
</script>

</body>
</html>