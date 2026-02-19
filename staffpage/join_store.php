<?php
session_start();
require_once "../ld_db.php";

// 1. เช็คสิทธิ์เบื้องต้นว่าเป็น Staff หรือไม่
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

// 3. ประมวลผลเมื่อกดสมัคร
if (isset($_POST['apply_store'])) {
    $store_id = $_POST['store_id'];

    // เช็คอีกรอบเพื่อความปลอดภัย (Double Check)
    if ($hasStore) {
        $error_msg = "คุณมีสังกัดร้านอยู่แล้ว ไม่สามารถสมัครเพิ่มได้";
    } else {
        // เช็คว่าเคยสมัคร "ร้านนี้" ไปหรือยัง (ป้องกันการส่ง Request ซ้ำร้านเดิม)
        $checkDuplicate = $pdo->prepare("SELECT id FROM store_staff WHERE user_id = ? AND store_id = ?");
        $checkDuplicate->execute([$user_id, $store_id]);

        if ($checkDuplicate->fetch()) {
            $error_msg = "คุณได้ส่งคำขอไปยังร้านนี้แล้ว กรุณารอการตรวจสอบ";
        } else {
            // บันทึกคำขอใหม่ (ใช้ UUID ตามโครงสร้างเดิมของคุณ)
            $stmt = $pdo->prepare("INSERT INTO store_staff (id, user_id, store_id, status, requested_at) VALUES (UUID(), ?, ?, 'pending', NOW())");
            if ($stmt->execute([$user_id, $store_id])) {
                $success_msg = "ส่งคำขอเข้าร่วมร้านสำเร็จ!";
            } else {
                $error_msg = "เกิดข้อผิดพลาด กรุณาลองใหม่ในภายหลัง";
            }
        }
    }
}

// 4. ดึงรายชื่อร้านค้าทั้งหมด
$stores = $pdo->query("SELECT id, name, address FROM stores")->fetchAll();

// 5. ดึงรายการ ID ร้านที่เคยสมัครไปแล้ว (pending) เพื่อแสดงสถานะบนปุ่ม
$stmtPending = $pdo->prepare("SELECT store_id FROM store_staff WHERE user_id = ? AND status = 'pending'");
$stmtPending->execute([$user_id]);
$pendingIds = $stmtPending->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Join Store | Staff</title>
    <link href="../../bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        body {
            background-color: #f8fafc;
            font-family: 'Kanit', sans-serif;
            color: #334155;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
        }

        .main-wrapper {
            width: 100%;
            max-width: 600px;
            animation: fadeIn 0.6s ease-out;
        }

        .header-content {
            text-align: center;
            margin-bottom: 2rem;
        }

        .header-content i {
            font-size: 3.5rem;
            color: #6366f1;
            margin-bottom: 1rem;
            display: block;
        }

        .search-container {
            position: relative;
            margin-bottom: 1.5rem;
        }

        .search-container input {
            padding: 15px 20px 15px 50px;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            background: white;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
            transition: 0.3s;
        }

        .search-container input:focus {
            border-color: #6366f1;
            box-shadow: 0 10px 20px rgba(99, 102, 241, 0.1);
            outline: none;
        }

        .search-container i {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
        }

        .store-list {
            background: white;
            border-radius: 25px;
            overflow: hidden;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.05);
            max-height: 450px;
            overflow-y: auto;
            border: 1px solid #f1f5f9;
        }

        .store-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 20px 25px;
            border-bottom: 1px solid #f1f5f9;
            transition: 0.2s;
        }

        .store-item:last-child {
            border-bottom: none;
        }

        .store-item:hover {
            background-color: #f8fafc;
        }

        .store-info h6 {
            margin: 0;
            font-weight: 600;
            color: #1e293b;
        }

        .store-info p {
            margin: 0;
            font-size: 0.85rem;
            color: #64748b;
        }

        .btn-join {
            background: #6366f1;
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 12px;
            font-size: 0.9rem;
            font-weight: 500;
            transition: 0.3s;
        }

        .btn-join:hover:not(:disabled) {
            background: #4f46e5;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(99, 102, 241, 0.3);
        }

        .btn-pending {
            background: #fef3c7;
            color: #d97706;
            cursor: pointer;
        }

        .btn-pending:hover {
            background: #fde68a !important;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(15px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        #no-results {
            padding: 40px;
            text-align: center;
            color: #94a3b8;
            display: none;
        }
    </style>
</head>

<body>

    <div class="main-wrapper">
        <div class="header-content">
            <i class="bi bi-shop-window"></i>
            <h3 class="fw-bold">ค้นหาสาขาที่ต้องการร่วมงาน</h3>
            <p class="text-muted">เมื่อกดสมัครแล้ว กรุณารอเจ้าของร้านอนุมัติคำขอของคุณ</p>
        </div>

        <?php if ($error_msg): ?>
            <div class="alert alert-danger rounded-4 text-center mb-4 border-0 shadow-sm"><?= $error_msg ?></div>
        <?php endif; ?>
        <?php if ($success_msg): ?>
            <div class="alert alert-success rounded-4 text-center mb-4 border-0 shadow-sm"><?= $success_msg ?></div>
        <?php endif; ?>

        <?php if (!empty($pendingIds)): ?>
            <div class="text-center mb-3">
                <a href="waiting_approval.php" class="btn btn-sm btn-outline-primary rounded-pill px-4">
                    <i class="bi bi-clock-history me-1"></i> ติดตามคำขอของคุณ (<?= count($pendingIds) ?> ร้าน)
                </a>
            </div>
        <?php endif; ?>

        <div class="search-container">
            <i class="bi bi-search"></i>
            <input type="text" id="storeSearch" class="form-control" placeholder="พิมพ์ชื่อร้านเพื่อค้นหา...">
        </div>

        <div class="store-list" id="storeContainer">
            <?php foreach ($stores as $s):
                $isPending = in_array($s['id'], $pendingIds);
            ?>
                <div class="store-item" data-name="<?= strtolower($s['name']) ?>">
                    <div class="store-info">
                        <h6><?= htmlspecialchars($s['name']) ?></h6>
                        <p><i class="bi bi-geo-alt-fill me-1 text-danger"></i><?= htmlspecialchars($s['address']) ?></p>
                    </div>

                    <form method="post">
                        <input type="hidden" name="store_id" value="<?= $s['id'] ?>">
                        <?php if ($isPending): ?>
                            <button type="button" class="btn-join btn-pending shadow-sm" onclick="location.href='waiting_approval.php'">
                                <i class="bi bi-hourglass-split"></i> รอรับ
                            </button>
                        <?php else: ?>
                            <button type="submit" name="apply_store" class="btn-join shadow-sm" onclick="return confirm('ส่งคำขอสมัครเข้าร่วมร้าน <?= $s['name'] ?>?')">
                                สมัครงาน
                            </button>
                        <?php endif; ?>
                    </form>
                </div>
            <?php endforeach; ?>

            <div id="no-results">
                <i class="bi bi-search fs-2 mb-2 d-block"></i>
                ไม่พบร้านค้าที่คุณกำลังมองหา
            </div>
        </div>
    </div>

    <script>
        // ระบบค้นหาร้านค้าแบบ Real-time
        document.getElementById('storeSearch').addEventListener('input', function(e) {
            const term = e.target.value.toLowerCase().trim();
            const items = document.querySelectorAll('.store-item');
            let found = false;

            items.forEach(item => {
                const name = item.getAttribute('data-name');
                if (name.includes(term)) {
                    item.style.display = 'flex';
                    found = true;
                } else {
                    item.style.display = 'none';
                }
            });
            document.getElementById('no-results').style.display = found ? 'none' : 'block';
        });
    </script>

</body>

</html>