<?php
include_once '../../../ld_db.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name     = trim($_POST['name']);
    $price    = (float)$_POST['price'];
    $amount   = (float)$_POST['amount'];
    $duration = trim($_POST['duration']);

    // ===== upload qr =====
    $qr_path = null;
    if (!empty($_FILES['qr_image']['name'])) {

        $dir = __DIR__ . '/uploads/';
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $ext = pathinfo($_FILES['qr_image']['name'], PATHINFO_EXTENSION);
        $filename = 'qr_' . time() . '.' . $ext;
        $target = $dir . $filename;

        if (move_uploaded_file($_FILES['qr_image']['tmp_name'], $target)) {
            $qr_path = 'billing/uploads/' . $filename;
        }
    }

    $stmt = $pdo->prepare("
        INSERT INTO billing_plans (name, price, amount, duration, qr_image, status)
        VALUES (?, ?, ?, ?, ?, 'active')
    ");
    $stmt->execute([
        $name,
        $price,
        $amount,
        $duration,
        $qr_path
    ]);

    header("Location: ../sidebar.php?link=setting");
    exit;
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>สร้างแพ็กเกจรายเดือน</title>

<link href="../../../bootstrap/css/bootstrap.min.css" rel="stylesheet">
<link href="../../../bootstrap/bootstrap-icons.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;600&display=swap" rel="stylesheet">
<link rel="icon" href="../../../image/3.jpg">

<style>
body {
    background:#f4f6f9;
    font-family:'Kanit',sans-serif;
}

/* main layout */
.main-wrapper {
    max-width: 900px;
    margin: 40px auto;
}

/* card */
.card {
    border-radius: 18px;
}

/* section title */
.section-title {
    font-weight: 600;
    color: #0f172a;
    margin-bottom: 6px;
}

.section-desc {
    font-size: 14px;
    color: #64748b;
}

/* preview */
.qr-preview {
    width: 160px;
    height: 160px;
    border-radius: 12px;
    border: 2px dashed #cbd5e1;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #94a3b8;
    font-size: 14px;
    overflow: hidden;
}

.qr-preview img {
    width: 100%;
    height: 100%;
    object-fit: contain;
}

/* footer */
.form-footer {
    background: #f8fafc;
    padding: 16px;
    border-top: 1px solid #e5e7eb;
    border-radius: 0 0 18px 18px;
}
</style>
</head>

<body>

<div class="main-wrapper">

<div class="card shadow-sm border-0">

    <!-- HEADER -->
    <div class="card-header bg-primary text-white py-3">
        <h4 class="mb-0">💳 สร้างแพ็กเกจพรีเมียม</h4>
        <small>สำหรับร้านซักอบรีดที่เช่าระบบของแพลตฟอร์ม</small>
    </div>

    <!-- BODY -->
    <div class="card-body px-4 py-4">

        <form method="post" enctype="multipart/form-data" class="row g-4">

            <!-- BASIC INFO -->
            <div class="col-12">
                <div class="section-title">ข้อมูลแพ็กเกจ</div>
                <div class="section-desc">ข้อมูลพื้นฐานที่ร้านค้าจะเห็น</div>
            </div>

            <div class="col-md-6">
                <label class="form-label">ชื่อแพ็กเกจ</label>
                <input type="text" name="name" class="form-control" required>
            </div>

            <div class="col-md-3">
                <label class="form-label">ราคาแสดง (บาท)</label>
                <input type="number" name="price" class="form-control" required>
            </div>

            <div class="col-md-3">
                <label class="form-label">ยอดโอนจริง (บาท)</label>
                <input type="number" name="amount" class="form-control" required>
            </div>

            <div class="col-12">
                <label class="form-label">รายละเอียด / สิทธิ์ที่ได้รับ</label>
                <textarea name="duration" rows="3" class="form-control"
                    placeholder="เช่น ใช้งานระบบได้ 30 วัน, รองรับ 5 เครื่อง, มีรายงาน"></textarea>
            </div>

            <!-- QR -->
            <div class="col-12">
                <div class="section-title">การชำระเงิน</div>
                <div class="section-desc">QR สำหรับร้านค้าใช้โอนค่าสมัคร</div>
            </div>

            <div class="col-md-6">
                <label class="form-label">อัปโหลด QR Code</label>
                <input type="file" name="qr_image" class="form-control" accept="image/*" required>
            </div>

            <div class="col-md-6 d-flex align-items-end">
                <div class="qr-preview" id="qrPreview">
                    ตัวอย่าง QR
                </div>
            </div>

            <!-- FOOTER -->
            <div class="col-12 form-footer d-flex justify-content-between align-items-center mt-4">
                <a href="../sidebar.php?link=setting" class="btn btn-outline-secondary">
                    ยกเลิก
                </a>
                <button class="btn btn-success px-4">
                    <i class="bi bi-check-circle me-1"></i> บันทึกแพ็กเกจ
                </button>
            </div>

        </form>

    </div>
</div>

</div>

<script>
// QR preview
const input = document.querySelector('input[name="qr_image"]');
const preview = document.getElementById('qrPreview');

input.addEventListener('change', e => {
    const file = e.target.files[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = () => {
        preview.innerHTML = `<img src="${reader.result}">`;
    };
    reader.readAsDataURL(file);
});
</script>

<script src="../../../bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
