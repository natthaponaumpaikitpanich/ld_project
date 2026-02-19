<?php
session_start();
require_once "../../ld_db.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'platform_admin') {
    die('no permission');
}

$admin_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT id, name FROM stores WHERE status = 'active' ORDER BY name");
$stmt->execute();
$stores = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000, mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));
    $title           = trim($_POST['title']);
    $summary         = trim($_POST['summary']);
    $message         = trim($_POST['message']);
    $discount        = (float)$_POST['discount']; // เปลี่ยนเป็น float เพื่อรองรับทศนิยม
    $discount_type   = $_POST['discount_type'];   // เพิ่ม: 'percentage' หรือ 'fixed'
    $min_requirement = (int)$_POST['min_requirement']; // เพิ่ม: เงื่อนไขขั้นต่ำ
    $audience        = $_POST['audience'];
    $store_id        = $_POST['store_id'] ?: null;
    $status          = $_POST['status'];
    $start_date      = $_POST['start_date'];
    $end_date        = $_POST['end_date'];

    if ($audience === 'store_specific' && !$store_id) {
        die('กรุณาเลือกร้านสำหรับโปรโมชั่นร้านเฉพาะ');
    }

    $imagePath = null;
    if (!empty($_FILES['image']['name'])) {
        $dir = "../../uploads/promotion/";
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $filename = uniqid('promo_') . '.' . $ext;
        if (move_uploaded_file($_FILES['image']['tmp_name'], $dir . $filename)) {
            $imagePath = "uploads/promotion/" . $filename;
        }
    }

    // ปรับ Query เพิ่ม discount_type และ min_requirement
    $sql = "INSERT INTO promotions (id, created_by, store_id, title, discount, discount_type, min_requirement, summary, message, image, start_date, end_date, status, audience) 
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id, $admin_id, $store_id, $title, $discount, $discount_type, $min_requirement, $summary, $message, $imagePath, $start_date, $end_date, $status, $audience]);

    header("Location: ../sidebar/sidebar.php?link=promotion&success=1");
    exit;
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
    <title>สร้างโปรโมชั่น - แพลตฟอร์ม</title>
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
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            padding: 30px;
            color: white;
        }

        .form-label {
            font-weight: 500;
            color: #475569;
            margin-bottom: 8px;
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
            margin-bottom: 20px;
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
            border-radius: 12px;
            display: none;
            margin-top: 15px;
        }

        .btn-save {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            padding: 12px 35px;
            border-radius: 12px;
            font-weight: 600;
            color: white;
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
                    <h3 class="mb-1"><i class="bi bi-megaphone-fill me-2"></i> สร้างโปรโมชั่นใหม่</h3>
                    <p class="mb-0 opacity-75">ตั้งค่าส่วนลดแพ็กเกจสำหรับเจ้าของร้านซักอบรีด</p>
                </div>
                <a href="../sidebar/sidebar.php?link=promotion" class="btn btn-light btn-sm rounded-pill px-3 text-primary fw-bold">กลับ</a>
            </div>

            <div class="card-body p-4 p-lg-5">
                <form method="post" enctype="multipart/form-data" id="promoForm">

                    <div class="section-title"><i class="bi bi-info-circle"></i> ข้อมูลโปรโมชั่นและส่วนลด</div>
                    <div class="row g-4 mb-5">
                        <div class="col-lg-6">
                            <label class="form-label">ชื่อโปรโมชั่น</label>
                            <input type="text" name="title" class="form-control" placeholder="เช่น โปรโมชั่นลูกค้าเก่าลด 20%" required>
                        </div>
                        <div class="col-lg-3">
                            <label class="form-label">ประเภทส่วนลด</label>
                            <select name="discount_type" class="form-select">
                                <option value="percentage">เปอร์เซ็นต์ (%)</option>
                                <option value="fixed">จำนวนเงินคงที่ (บาท)</option>
                            </select>
                        </div>
                        <div class="col-lg-3">
                            <label class="form-label">ค่าส่วนลด</label>
                            <input type="number" step="0.01" name="discount" class="form-control" placeholder="0.00" required>
                        </div>
                        <div class="col-lg-6">
                            <label class="form-label">เงื่อนไข: ใช้บริการขั้นต่ำ (ครั้ง)</label>
                            <input type="number" name="min_requirement" class="form-control" value="0" placeholder="ระบุจำนวนครั้งที่เคยซื้อแพ็กเกจ">
                            <small class="text-muted">* ใส่ 0 หากต้องการให้ใช้ได้ทันทีทุกคน</small>
                        </div>
                        <div class="col-lg-6">
                            <label class="form-label">สรุปสั้นๆ</label>
                            <input type="text" name="summary" class="form-control" placeholder="คำโปรยบนหน้าบัตร...">
                        </div>
                    </div>

                    <div class="section-title"><i class="bi bi-bullseye"></i> กลุ่มเป้าหมายและสถานะ</div>
                    <div class="row g-4 mb-5">
                        <div class="col-md-4">
                            <label class="form-label">กลุ่มร้านค้า</label>
                            <select name="audience" id="audienceSelect" class="form-select">
                                <option value="stores">ทุกร้านค้า (Public)</option>
                                <option value="store_specific">เฉพาะบางร้าน (Specific)</option>
                            </select>
                        </div>
                        <div id="storeSelectWrapper" class="col-md-4 hidden-select">
                            <label class="form-label">เลือกพาร์ทเนอร์</label>
                            <select name="store_id" class="form-select">
                                <option value="">-- เลือกชื่อร้าน --</option>
                                <?php foreach ($stores as $s): ?>
                                    <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">สถานะ</label>
                            <select name="status" class="form-select">
                                <option value="active">เปิดใช้งาน</option>
                                <option value="inactive">ปิดใช้งาน</option>
                                <option value="draft">ร่าง (Draft)</option>
                            </select>
                        </div>
                    </div>

                    <div class="section-title"><i class="bi bi-calendar-range"></i> รูปภาพและระยะเวลา</div>
                    <div class="row g-4 mb-5">
                        <div class="col-lg-6">
                            <label class="form-label">แบนเนอร์โปรโมชั่น</label>
                            <div class="upload-zone" onclick="document.getElementById('imgInput').click()">
                                <i class="bi bi-cloud-arrow-up fs-1 text-primary"></i>
                                <p class="mb-0 mt-2 text-muted">คลิกเพื่ออัปโหลดรูปภาพ</p>
                                <input type="file" name="image" id="imgInput" class="d-none" accept="image/*">
                                <img id="imagePreview" src="#" alt="Preview">
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label">วันที่เริ่ม</label>
                                <input type="datetime-local" name="start_date" class="form-control" required>
                            </div>
                            <div>
                                <label class="form-label">วันที่สิ้นสุด</label>
                                <input type="datetime-local" name="end_date" class="form-control" required>
                            </div>
                        </div>
                    </div>

                    <div class="section-title"><i class="bi bi-chat-left-text"></i> รายละเอียดเงื่อนไขเพิ่มเติม</div>
                    <div class="mb-5">
                        <textarea name="message" rows="4" class="form-control" placeholder="ระบุรายละเอียด เช่น ลดเฉพาะแพ็กเกจรายปีเท่านั้น..."></textarea>
                    </div>

                    <div class="d-flex justify-content-end gap-3">
                        <button type="button" onclick="history.back()" class="btn btn-light rounded-pill px-4">ยกเลิก</button>
                        <button type="submit" class="btn btn-save px-5 rounded-pill shadow-sm">
                            <i class="bi bi-check-circle me-2"></i> บันทึกและสร้างโปรโมชั่น
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // การแสดงผลร้านค้าเฉพาะราย
        const audienceSelect = document.getElementById('audienceSelect');
        const storeSelectWrapper = document.getElementById('storeSelectWrapper');
        audienceSelect.addEventListener('change', function() {
            storeSelectWrapper.classList.toggle('hidden-select', this.value !== 'store_specific');
        });

        // Preview รูปภาพ
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

        // ตรวจสอบวันที่
        document.getElementById('promoForm').addEventListener('submit', function(e) {
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