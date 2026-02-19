<?php
session_start();
require_once "../ld_db.php";

// เช็คว่าเป็น Staff จริงไหม
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    header("Location: ../loginpage/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$error_msg = "";
$success_msg = "";

// 2. 🛡️ ด่านตรวจ: ถ้ามีสังกัดร้านที่สถานะ 'active' แล้ว ห้ามอยู่หน้านี้เด็ดขาด
$checkActive = $pdo->prepare("SELECT id FROM store_staff WHERE user_id = ? AND status = 'active' LIMIT 1");
$checkActive->execute([$user_id]);
$hasStore = $checkActive->fetch();

if ($hasStore) {
    // ถ้ามีร้านแล้ว ดีดไปหน้าหลักพนักงานทันที
    header("Location: index.php?link=Home");
    exit;
}


// 1. ตรวจสอบว่ามีร้านไหนกด "อนุมัติ" (active) แล้วหรือยัง
$stmtActive = $pdo->prepare("
    SELECT ss.*, s.name as store_name 
    FROM store_staff ss
    JOIN stores s ON ss.store_id = s.id
    WHERE ss.user_id = ? AND ss.status = 'active'
    LIMIT 1
");
$stmtActive->execute([$user_id]);
$approved = $stmtActive->fetch();

// ถ้าอนุมัติแล้ว ให้เซ็ต Session และส่งไปหน้าหลักทันที
if ($approved) {
    $_SESSION['store_id'] = $approved['store_id'];
    $_SESSION['store_name'] = $approved['store_name'];
    header("Location: index.php?link=Home");
    exit;
}

// 2. ประมวลผลการยกเลิกคำขอเฉพาะร้าน
if (isset($_GET['cancel_id'])) {
    $cancel_id = $_GET['cancel_id'];
    $del = $pdo->prepare("DELETE FROM store_staff WHERE id = ? AND user_id = ? AND status = 'pending'");
    $del->execute([$cancel_id, $user_id]);
    header("Location: waiting_approval.php");
    exit;
}

// 3. ดึงรายการคำขอที่ค้างอยู่ทั้งหมด (Pending)
$stmtPending = $pdo->prepare("
    SELECT ss.id as request_id, s.name as store_name, s.address, ss.requested_at
    FROM store_staff ss
    JOIN stores s ON ss.store_id = s.id
    WHERE ss.user_id = ? AND ss.status = 'pending'
    ORDER BY ss.requested_at DESC
");
$stmtPending->execute([$user_id]);
$pending_requests = $stmtPending->fetchAll();

// ถ้าไม่มีคำขอเหลือแล้ว ให้กลับไปหน้าเลือกร้าน
if (empty($pending_requests)) {
    header("Location: join_store.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Waiting for Approval | Staff</title>
    <link href="../../bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        body {
            background-color: #f8fafc;
            font-family: 'Kanit', sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
        }

        .status-wrapper {
            width: 100%;
            max-width: 500px;
            animation: slideUp 0.6s ease-out;
        }

        .main-card {
            background: white;
            padding: 40px 30px;
            border-radius: 30px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.05);
            text-align: center;
            margin-bottom: 20px;
        }

        .loader-wrapper {
            position: relative;
            width: 100px;
            height: 100px;
            margin: 0 auto 25px;
        }

        .loader-ring {
            position: absolute;
            width: 100%;
            height: 100%;
            border: 6px solid #f1f5f9;
            border-top: 6px solid #6366f1;
            border-radius: 50%;
            animation: spin 2s linear infinite;
        }

        .loader-icon {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 35px;
            color: #6366f1;
        }

        .pending-list {
            background: white;
            border-radius: 25px;
            padding: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.03);
        }

        .pending-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 15px 20px;
            border-bottom: 1px solid #f1f5f9;
        }

        .pending-item:last-child {
            border-bottom: none;
        }

        .store-info {
            text-align: left;
        }

        .store-name {
            font-weight: 600;
            color: #1e293b;
            margin: 0;
            font-size: 1rem;
        }

        .req-date {
            font-size: 0.75rem;
            color: #94a3b8;
        }

        .btn-cancel-mini {
            color: #94a3b8;
            font-size: 1.2rem;
            transition: 0.2s;
            border: none;
            background: none;
            padding: 5px;
        }

        .btn-cancel-mini:hover {
            color: #ef4444;
        }

        .btn-add-more {
            display: inline-block;
            margin-top: 20px;
            color: #6366f1;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.9rem;
        }

        @keyframes spin {
            100% {
                transform: rotate(360deg);
            }
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
    </style>
</head>

<body>

    <div class="status-wrapper">
        <div class="main-card">
            <div class="loader-wrapper">
                <div class="loader-ring"></div>
                <div class="loader-icon"><i class="bi bi-hourglass-split"></i></div>
            </div>

            <h4 class="fw-bold mb-2">กำลังรอการตอบรับ</h4>
            <p class="text-muted small mb-0">ระบบจะนำคุณเข้าสู่หน้าหลักอัตโนมัติ<br>เมื่อมีร้านใดร้านหนึ่งกดยืนยันคำขอของคุณ</p>

            <a href="join_store.php" class="btn-add-more">
                <i class="bi bi-plus-circle me-1"></i> สมัครร้านอื่นเพิ่มเติม
            </a>
        </div>

        <div class="pending-list">
            <div class="px-3 py-2 border-bottom">
                <small class="fw-bold text-uppercase text-muted" style="letter-spacing: 1px;">คำขอที่ส่งไปแล้ว (<?= count($pending_requests) ?>)</small>
            </div>
            <?php foreach ($pending_requests as $row): ?>
                <div class="pending-item">
                    <div class="store-info">
                        <p class="store-name"><?= htmlspecialchars($row['store_name']) ?></p>
                        <span class="req-date">
                            <i class="bi bi-calendar-check me-1"></i>
                            ส่งเมื่อ <?= date('d/m/Y H:i', strtotime($row['requested_at'])) ?>
                        </span>
                    </div>
                    <a href="?cancel_id=<?= $row['request_id'] ?>"
                        class="btn-cancel-mini"
                        onclick="return confirm('ยกเลิกคำขอของร้านนี้?')">
                        <i class="bi bi-x-circle-fill"></i>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="text-center mt-4">
            <button onclick="location.reload()" class="btn btn-sm btn-light rounded-pill px-4 shadow-sm text-muted">
                <i class="bi bi-arrow-clockwise me-1"></i> รีเฟรชสถานะ
            </button>
        </div>
    </div>

    <script>
        // Auto Refresh ทุก 20 วินาที เพื่อเช็คว่าโดนรับเข้าทำงานหรือยัง
        setTimeout(function() {
            location.reload();
        }, 20000);
    </script>

</body>

</html>