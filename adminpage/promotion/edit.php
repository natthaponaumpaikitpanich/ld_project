<?php
session_start();
require_once "../../ld_db.php";

// ตรวจสอบสิทธิ์ (อิงตามหน้า Create)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'platform_admin') {
    die('ไม่มีสิทธิ์เข้าถึง');
}

// 1. ดึงข้อมูลโปรโมชั่นเดิม
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: ../sidebar/sidebar.php?link=promotion");
    exit;
}

$id = $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM promotions WHERE id = ?");
$stmt->execute([$id]);
$promotion = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$promotion) {
    die("ไม่พบข้อมูลโปรโมชั่น");
}

// 2. ดึงรายชื่อร้านค้าสำหรับตัวเลือก Specific
$stmtStores = $pdo->prepare("SELECT id, name FROM stores WHERE status = 'active' ORDER BY name");
$stmtStores->execute();
$stores = $stmtStores->fetchAll(PDO::FETCH_ASSOC);

// 3. Logic การอัปเดตข้อมูล
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $title           = trim($_POST['title']);
    $summary         = trim($_POST['summary']);
    $message         = trim($_POST['message']);
    $discount        = (float)$_POST['discount'];
    $discount_type   = $_POST['discount_type'];
    $min_requirement = (int)$_POST['min_requirement'];
    $audience        = $_POST['audience'];
    $store_id        = ($audience === 'store_specific') ? $_POST['store_id'] : null;
    $status          = $_POST['status'];
    $start_date      = $_POST['start_date'];
    $end_date        = $_POST['end_date'];
    $imagePath       = $promotion['image'];

    // จัดการรูปภาพ
    if (!empty($_FILES['image']['name'])) {
        $dir = "../../uploads/promotion/";
        if (!is_dir($dir)) mkdir($dir, 0777, true);

        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $filename = uniqid('promo_') . '.' . $ext;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $dir . $filename)) {
            // ลบรูปเก่าถ้ามี
            if (!empty($promotion['image']) && file_exists("../../" . $promotion['image'])) {
                unlink("../../" . $promotion['image']);
            }
            $imagePath = "uploads/promotion/" . $filename;
        }
    }

    try {
        $sql = "UPDATE promotions SET 
                title=?, discount=?, discount_type=?, min_requirement=?, 
                summary=?, message=?, audience=?, store_id=?, 
                start_date=?, end_date=?, image=?, status=?, updated_at=NOW() 
                WHERE id=?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $title,
            $discount,
            $discount_type,
            $min_requirement,
            $summary,
            $message,
            $audience,
            $store_id,
            $start_date,
            $end_date,
            $imagePath,
            $status,
            $id
        ]);

        header("Location: ../sidebar/sidebar.php?link=promotion&updated=1");
        exit;
    } catch (PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="../../bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600&display=swap" rel="stylesheet">
    <title>แก้ไขโปรโมชั่น - แพลตฟอร์ม</title>
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --bg-body: #f8fafc;
        }

        body {
            font-family: 'Kanit', sans-serif;
            background-color: var(--bg-body);
            color: #1e293b;
        }

        .promo-container {
            max-width: 1100px;
            margin: 0 auto;
        }

        .glass-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }

        .header-gradient {
            background: linear-gradient(135deg, #1e293b, #334155);
            padding: 30px;
            color: white;
        }

        .form-label {
            font-weight: 500;
            color: #475569;
        }

        .form-control,
        .form-select {
            border-radius: 12px;
            padding: 12px;
            border: 1.5px solid #e2e8f0;
        }

        .section-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--primary-color);
            margin: 25px 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-title::after {
            content: "";
            flex: 1;
            height: 1px;
            background: #e2e8f0;
        }

        .upload-zone {
            border: 2px dashed #cbd5e1;
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            background: #f1f5f9;
        }

        #imagePreview {
            max-width: 100%;
            max-height: 200px;
            border-radius: 12px;
            margin-top: 15px;
        }

        .hidden-select {
            display: none;
        }
    </style>
</head>

<body>

    <div class="promo-container py-5 px-3">
        <div class="glass-card">
            <div class="header-gradient d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="mb-1"><i class="bi bi-pencil-square me-2"></i> แก้ไขโปรโมชั่น</h3>
                    <p class="mb-0 opacity-75">ID: <?= htmlspecialchars($id) ?></p>
                </div>
                <a href="../sidebar/sidebar.php?link=promotion" class="btn btn-light btn-sm rounded-pill px-3 fw-bold">กลับ</a>
            </div>

            <div class="card-body p-4 p-lg-5">
                <form method="post" enctype="multipart/form-data" id="editForm">

                    <div class="section-title"><i class="bi bi-info-circle"></i> ข้อมูลโปรโมชั่นและส่วนลด</div>
                    <div class="row g-4 mb-4">
                        <div class="col-lg-6">
                            <label class="form-label">ชื่อโปรโมชั่น</label>
                            <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($promotion['title']) ?>" required>
                        </div>
                        <div class="col-lg-3">
                            <label class="form-label">ประเภทส่วนลด</label>
                            <select name="discount_type" class="form-select">
                                <option value="percentage" <?= $promotion['discount_type'] == 'percentage' ? 'selected' : '' ?>>เปอร์เซ็นต์ (%)</option>
                                <option value="fixed" <?= $promotion['discount_type'] == 'fixed' ? 'selected' : '' ?>>จำนวนเงินคงที่ (บาท)</option>
                            </select>
                        </div>
                        <div class="col-lg-3">
                            <label class="form-label">ค่าส่วนลด</label>
                            <input type="number" step="0.01" name="discount" class="form-control" value="<?= $promotion['discount'] ?>" required>
                        </div>
                        <div class="col-lg-6">
                            <label class="form-label">เงื่อนไข: ใช้บริการขั้นต่ำ (ครั้ง)</label>
                            <input type="number" name="min_requirement" class="form-control" value="<?= $promotion['min_requirement'] ?>">
                        </div>
                        <div class="col-lg-6">
                            <label class="form-label">สรุปสั้นๆ (Summary)</label>
                            <input type="text" name="summary" class="form-control" value="<?= htmlspecialchars($promotion['summary']) ?>">
                        </div>
                    </div>

                    <div class="section-title"><i class="bi bi-bullseye"></i> กลุ่มเป้าหมายและสถานะ</div>
                    <div class="row g-4 mb-4">
                        <div class="col-md-4">
                            <label class="form-label">กลุ่มร้านค้า</label>
                            <select name="audience" id="audienceSelect" class="form-select">
                                <option value="stores" <?= $promotion['audience'] == 'stores' ? 'selected' : '' ?>>ทุกร้านค้า (Public)</option>
                                <option value="store_specific" <?= $promotion['audience'] == 'store_specific' ? 'selected' : '' ?>>เฉพาะบางร้าน (Specific)</option>
                            </select>
                        </div>
                        <div id="storeSelectWrapper" class="col-md-4 <?= $promotion['audience'] !== 'store_specific' ? 'hidden-select' : '' ?>">
                            <label class="form-label">เลือกพาร์ทเนอร์</label>
                            <select name="store_id" class="form-select">
                                <option value="">-- เลือกชื่อร้าน --</option>
                                <?php foreach ($stores as $s): ?>
                                    <option value="<?= $s['id'] ?>" <?= $promotion['store_id'] == $s['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($s['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">สถานะ</label>
                            <select name="status" class="form-select">
                                <option value="active" <?= $promotion['status'] == 'active' ? 'selected' : '' ?>>เปิดใช้งาน</option>
                                <option value="inactive" <?= $promotion['status'] == 'inactive' ? 'selected' : '' ?>>ปิดใช้งาน</option>
                                <option value="draft" <?= $promotion['status'] == 'draft' ? 'selected' : '' ?>>ร่าง (Draft)</option>
                            </select>
                        </div>
                    </div>

                    <div class="section-title"><i class="bi bi-calendar-range"></i> รูปภาพและระยะเวลา</div>
                    <div class="row g-4 mb-4">
                        <div class="col-lg-6">
                            <label class="form-label">แบนเนอร์โปรโมชั่น (ปล่อยว่างหากไม่เปลี่ยน)</label>
                            <div class="upload-zone" onclick="document.getElementById('imgInput').click()">
                                <i class="bi bi-image fs-2 text-muted"></i>
                                <p class="mb-0 text-muted small">คลิกเพื่อเปลี่ยนรูปภาพ</p>
                                <input type="file" name="image" id="imgInput" class="d-none" accept="image/*">
                                <?php if ($promotion['image']): ?>
                                    <img id="imagePreview" src="../../<?= $promotion['image'] ?>" alt="Preview" style="display:block; margin: 10px auto;">
                                <?php else: ?>
                                    <img id="imagePreview" src="#" alt="Preview">
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label">วันที่เริ่ม</label>
                                <input type="datetime-local" name="start_date" class="form-control" value="<?= date('Y-m-d\TH:i', strtotime($promotion['start_date'])) ?>" required>
                            </div>
                            <div>
                                <label class="form-label">วันที่สิ้นสุด</label>
                                <input type="datetime-local" name="end_date" class="form-control" value="<?= date('Y-m-d\TH:i', strtotime($promotion['end_date'])) ?>" required>
                            </div>
                        </div>
                    </div>

                    <div class="section-title"><i class="bi bi-chat-left-text"></i> รายละเอียดเงื่อนไขเพิ่มเติม</div>
                    <div class="mb-5">
                        <textarea name="message" rows="4" class="form-control"><?= htmlspecialchars($promotion['message']) ?></textarea>
                    </div>

                    <div class="d-flex justify-content-end gap-3">
                        <button type="button" onclick="history.back()" class="btn btn-light rounded-pill px-4">ยกเลิก</button>
                        <button type="submit" class="btn btn-primary px-5 rounded-pill shadow-sm fw-bold" style="background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); border:none;">
                            <i class="bi bi-save me-2"></i> บันทึกการแก้ไข
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // การแสดงผลร้านค้าเฉพาะราย (อิงตาม Create)
        const audienceSelect = document.getElementById('audienceSelect');
        const storeSelectWrapper = document.getElementById('storeSelectWrapper');
        audienceSelect.addEventListener('change', function() {
            storeSelectWrapper.classList.toggle('hidden-select', this.value !== 'store_specific');
        });

        // Preview รูปภาพ (อิงตาม Create)
        const imgInput = document.getElementById('imgInput');
        const imagePreview = document.getElementById('imagePreview');
        imgInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = e => {
                    imagePreview.src = e.target.result;
                    imagePreview.style.display = 'block';
                }
                reader.readAsDataURL(file);
            }
        });

        // ตรวจสอบวันที่ (อิงตาม Create)
        document.getElementById('editForm').addEventListener('submit', function(e) {
            const start = new Date(document.querySelector('[name="start_date"]').value);
            const end = new Date(document.querySelector('[name="end_date"]').value);
            if (end <= start) {
                e.preventDefault();
                alert('❌ วันที่สิ้นสุดต้องอยู่หลังจากวันที่เริ่มเสมอครับ');
            }
        });
    </script>
</body>

</html>