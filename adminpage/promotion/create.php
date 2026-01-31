<?php
// --- คง Logic PHP เดิมไว้ทั้งหมด ---
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
    $title      = trim($_POST['title']);
    $summary    = trim($_POST['summary']);
    $message    = trim($_POST['message']);
    $discount   = (int)$_POST['discount'];
    $audience   = $_POST['audience'];
    $store_id   = $_POST['store_id'] ?: null;
    $status     = $_POST['status'];
    $start_date = $_POST['start_date'];
    $end_date   = $_POST['end_date'];

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

    $stmt = $pdo->prepare("INSERT INTO promotions (id, created_by, store_id, title, discount, summary, message, image, start_date, end_date, status, audience) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
    $stmt->execute([$id, $admin_id, $store_id, $title, $discount, $summary, $message, $imagePath, $start_date, $end_date, $status, $audience]);
    header("Location: ../sidebar/sidebar.php?link=promotion?success=1");
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
    <title>สร้างโปรโมชั่นระดับพรีเมียม</title>

    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --accent-color: #4cc9f0;
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

        /* Card Styling */
        .glass-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }

        .header-gradient {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            padding: 30px;
            color: white;
            border-bottom: none;
        }

        /* Form Elements */
        .form-label {
            font-weight: 500;
            color: #475569;
            margin-bottom: 8px;
            font-size: 0.95rem;
        }

        .form-control,
        .form-select {
            border-radius: 12px;
            padding: 12px 16px;
            border: 1.5px solid #e2e8f0;
            transition: all 0.3s ease;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(67, 97, 238, 0.1);
        }

        /* Image Upload Preview */
        .upload-zone {
            border: 2px dashed #cbd5e1;
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: 0.3s;
            background: #f1f5f9;
        }

        .upload-zone:hover {
            border-color: var(--primary-color);
            background: #fff;
        }

        #imagePreview {
            max-width: 100%;
            border-radius: 12px;
            display: none;
            margin-top: 15px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        /* Promotion Badge */
        .preview-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: var(--accent-color);
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        /* Action Buttons */
        .btn-save {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            padding: 12px 35px;
            border-radius: 12px;
            font-weight: 600;
            transition: transform 0.2s;
        }

        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.3);
            color: #fff;
        }

        /* Section Title */
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

        .hidden-select {
            display: none;
            transition: opacity 0.5s ease;
        }
    </style>
</head>

<body>

    <div class="promo-container py-5 px-3">
        <div class="glass-card">
            <div class="header-gradient d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="mb-1"><i class="bi bi-megaphone-fill me-2"></i> สร้างโปรโมชั่นใหม่</h3>
                    <p class="mb-0 opacity-75">ออกแบบข้อเสนอพิเศษเพื่อกระตุ้นยอดขายให้พาร์ทเนอร์ของคุณ</p>
                </div>
                <a href="../sidebar/sidebar.php?link=promotion" class="btn btn-light btn-sm rounded-pill px-3 text-primary fw-bold">กลับหน้าหลัก</a>
            </div>

            <div class="card-body p-4 p-lg-5">
                <form method="post" enctype="multipart/form-data" id="promoForm">

                    <div class="section-title"><i class="bi bi-info-circle"></i> ข้อมูลเบื้องต้น</div>
                    <div class="row g-4 mb-5">
                        <div class="col-lg-8">
                            <label class="form-label">ชื่อโปรโมชั่น (หัวข้อใหญ่)</label>
                            <input type="text" name="title" class="form-control form-control-lg" placeholder="เช่น ลดกระหน่ำรับหน้าฝน 50%" required>
                        </div>
                        <div class="col-lg-4">
                            <label class="form-label">ส่วนลด (%) หรือ มูลค่า</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="bi bi-tag"></i></span>
                                <input type="number" name="discount" class="form-control" value="0">
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">สรุปสั้นๆ (แสดงบนการ์ด)</label>
                            <input type="text" name="summary" class="form-control" placeholder="ข้อความดึงดูดสั้นๆ...">
                        </div>
                    </div>

                    <div class="section-title"><i class="bi bi-bullseye"></i> กลุ่มเป้าหมายและสถานะ</div>
                    <div class="row g-4 mb-5">
                        <div class="col-md-4">
                            <label class="form-label">กลุ่มร้านค้า</label>
                            <select name="audience" id="audienceSelect" class="form-select">
                                <option value="stores">ร้านค้าทั้งหมด (Public)</option>
                                <option value="store_specific">เฉพาะร้านที่กำหนด (Specific)</option>
                            </select>
                        </div>
                        <div id="storeSelectWrapper" class="col-md-4 hidden-select">
                            <label class="form-label">เลือกร้านค้าพาร์ทเนอร์</label>
                            <select name="store_id" class="form-select">
                                <option value="">-- ค้นหาร้านค้า --</option>
                                <?php foreach ($stores as $s): ?>
                                    <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">สถานะการแสดงผล</label>
                            <select name="status" class="form-select">
                                <option value="active">🟢 เปิดใช้งาน (Publish)</option>
                                <option value="inactive">🔴 ปิดชั่วคราว (Inactive)</option>
                                <option value="draft">🟡 ร่างไว้ก่อน (Draft)</option>
                            </select>
                        </div>
                    </div>

                    <div class="section-title"><i class="bi bi-calendar-range"></i> สื่อและระยะเวลา</div>
                    <div class="row g-4 mb-5">
                        <div class="col-lg-6">
                            <label class="form-label">รูปภาพโปรโมชั่น</label>
                            <div class="upload-zone" onclick="document.getElementById('imgInput').click()">
                                <i class="bi bi-cloud-arrow-up fs-1 text-primary"></i>
                                <p class="mb-0 mt-2 text-muted">คลิกเพื่ออัปโหลดรูปภาพ (16:9 แนะนำ)</p>
                                <input type="file" name="image" id="imgInput" class="d-none" accept="image/*">
                                <img id="imagePreview" src="#" alt="Preview">
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label">วันที่เริ่มโปรโมชั่น</label>
                                    <input type="datetime-local" name="start_date" class="form-control" required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">วันที่สิ้นสุด</label>
                                    <input type="datetime-local" name="end_date" class="form-control" required>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="section-title"><i class="bi bi-chat-left-text"></i> รายละเอียดเพิ่มเติม</div>
                    <div class="row g-4 mb-4">
                        <div class="col-12">
                            <label class="form-label">ข้อความถึงร้านค้า/ลูกค้า (Detailed Message)</label>
                            <textarea name="message" rows="5" class="form-control" placeholder="ระบุเงื่อนไข รายละเอียดสิทธิพิเศษต่างๆ..."></textarea>
                        </div>
                    </div>

                    <hr class="my-4">
                    <div class="d-flex justify-content-end gap-3">
                        <button type="button" onclick="window.location.href='../sidebar/sidebar.php?link=promotion'" class="btn btn-light rounded-pill px-4">ยกเลิก</button>
                        <button type="submit" class="btn btn-save text-white px-5 rounded-pill shadow-sm">
                            <i class="bi bi-check-circle me-2"></i> ยืนยันสร้างโปรโมชั่น
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>

    <script src="../../bootstrap/js/bootstrap.bundle.min.js"></script>
    <script>
        // 1. Dynamic Form: แสดง/ซ่อน ช่องเลือกร้านค้า
        const audienceSelect = document.getElementById('audienceSelect');
        const storeSelectWrapper = document.getElementById('storeSelectWrapper');

        audienceSelect.addEventListener('change', function() {
            if (this.value === 'store_specific') {
                storeSelectWrapper.style.display = 'block';
                setTimeout(() => storeSelectWrapper.style.opacity = '1', 10);
            } else {
                storeSelectWrapper.style.display = 'none';
                storeSelectWrapper.style.opacity = '0';
            }
        });

        // 2. Image Preview: ดูรูปก่อนโหลดจริง
        const imgInput = document.getElementById('imgInput');
        const imagePreview = document.getElementById('imagePreview');

        imgInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    imagePreview.src = e.target.result;
                    imagePreview.style.display = 'block';
                }
                reader.readAsDataURL(file);
            }
        });

        // 3. UX: เช็ควันที่สิ้นสุดต้องไม่น้อยกว่าวันที่เริ่ม
        document.querySelector('form').addEventListener('submit', function(e) {
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