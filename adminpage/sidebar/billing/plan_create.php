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
        // ปรับ Path ให้สัมพันธ์กับตำแหน่งไฟล์จริง
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
    <title>สร้างแพ็กเกจใหม่ | Management</title>

    <link href="../../../bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="../../../bootstrap/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Anuphan:wght@300;400;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #6366f1 0%, #4338ca 100%);
            --glass-bg: rgba(255, 255, 255, 0.95);
        }

        body {
            background-color: #f1f5f9;
            font-family: 'Anuphan', sans-serif;
            color: #1e293b;
        }

        .main-wrapper {
            max-width: 1000px;
            margin: 50px auto;
            padding: 0 20px;
        }

        /* Card Setup */
        .premium-card {
            background: var(--glass-bg);
            border: none;
            border-radius: 24px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }

        /* Sidebar in Card (Info) */
        .info-panel {
            background: var(--primary-gradient);
            color: white;
            padding: 40px;
            height: 100%;
        }

        /* Form Styling */
        .form-label {
            font-weight: 600;
            font-size: 0.9rem;
            color: #475569;
            margin-bottom: 8px;
        }

        .form-control {
            border-radius: 12px;
            padding: 12px 16px;
            border: 1px solid #e2e8f0;
            background-color: #f8fafc;
            transition: all 0.2s;
        }

        .form-control:focus {
            background-color: #fff;
            border-color: #6366f1;
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
        }

        /* QR Preview Container */
        .qr-upload-box {
            border: 2px dashed #cbd5e1;
            border-radius: 20px;
            padding: 20px;
            text-align: center;
            background: #fff;
            cursor: pointer;
            transition: all 0.3s;
            position: relative;
            min-height: 220px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .qr-upload-box:hover {
            border-color: #6366f1;
            background: #f5f7ff;
        }

        #qrPreview img {
            max-width: 100%;
            max-height: 180px;
            border-radius: 12px;
            box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1);
        }

        /* Floating Live Preview Card (Visual Only) */
        .live-preview-label {
            position: absolute;
            top: -12px;
            right: 20px;
            background: #10b981;
            color: white;
            padding: 2px 12px;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .btn-submit {
            background: var(--primary-gradient);
            border: none;
            padding: 14px 30px;
            border-radius: 14px;
            font-weight: 700;
            letter-spacing: 0.5px;
            transition: all 0.3s;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(99, 102, 241, 0.4);
        }

        .btn-cancel {
            border-radius: 14px;
            padding: 14px 20px;
            font-weight: 600;
            color: #64748b;
        }
    </style>
</head>
<body>

<div class="main-wrapper">
    <a href="../sidebar.php?link=setting" class="text-decoration-none text-muted mb-4 d-inline-block">
        <i class="bi bi-arrow-left-short fs-4"></i> ย้อนกลับไปหน้ารายการ
    </a>

    <div class="card premium-card">
        <div class="row g-0">
            <div class="col-lg-4 d-none d-lg-block">
                <div class="info-panel d-flex flex-column justify-content-between">
                    <div>
                        <div class="mb-4">
                            <i class="bi bi-rocket-takeoff-fill" style="font-size: 3rem;"></i>
                        </div>
                        <h3 class="fw-bold mb-3">สร้างแพ็กเกจใหม่</h3>

                    </div>
                    
                    <div class="mt-5">
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-white bg-opacity-25 rounded-circle p-2 me-3">
                                <i class="bi bi-shield-check"></i>
                            </div>
                            <small>ระบบตรวจสอบยอดอัตโนมัติ</small>
                        </div>
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-white bg-opacity-25 rounded-circle p-2 me-3">
                                <i class="bi bi-lightning"></i>
                            </div>
                            <small>เปิดใช้งานทันทีหลังโอน</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card-body p-4 p-md-5">
                    <form method="post" enctype="multipart/form-data">
                        
                        <div class="row g-4">
                            <div class="col-12">
                                <h4 class="fw-bold mb-1">รายละเอียดระบบ</h4>
                                <p class="text-muted small">กรอกข้อมูลแพ็กเกจที่คุณต้องการให้ลูกค้าเห็น</p>
                            </div>

                            <div class="col-12">
                                <label class="form-label">ชื่อแพ็กเกจ (เช่น Standard, Pro, Enterprise)</label>
                                <input type="text" name="name" class="form-control" placeholder="ระบุชื่อที่น่าดึงดูด..." required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">ราคาตลาด (บาท/เดือน)</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-tag"></i></span>
                                    <input type="number" name="price" class="form-control border-start-0" placeholder="0.00" required>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label text-danger">ยอดโอนจริง (บาท)</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-cash-coin"></i></span>
                                    <input type="number" name="amount" class="form-control border-start-0" placeholder="0.00" required>
                                </div>
                            </div>

                            <div class="col-12">
                                <label class="form-label">รายละเอียด / สิทธิประโยชน์</label>
                                <textarea name="duration" rows="3" class="form-control" 
                                    placeholder="ใส่รายการแยกทีละบรรทัด เช่น&#10;- ใช้งานได้ 30 วัน&#10;"></textarea>
                            </div>

                            <div class="col-12 mt-5">
                                <h4 class="fw-bold mb-1">ช่องทางชำระเงิน</h4>
                                <p class="text-muted small">อัปโหลดภาพ QR Code เพื่อให้ลูกค้าสแกนได้ทันที</p>
                            </div>

                            <div class="col-12">
                                <div class="row align-items-center">
                                    <div class="col-md-7">
                                        <div class="qr-upload-box" onclick="document.getElementById('qrInput').click()">
                                            <input type="file" name="qr_image" id="qrInput" class="d-none" accept="image/*" required>
                                            <div id="uploadPlaceholder">
                                                <i class="bi bi-cloud-arrow-up fs-1 text-primary"></i>
                                                <p class="mt-2 mb-0 fw-bold">คลิกเพื่ออัปโหลด QR Code</p>
                                                <small class="text-muted">รองรับไฟล์ JPG, PNG</small>
                                            </div>
                                            <div id="qrPreview" class="d-none"></div>
                                        </div>
                                    </div>
                                    <div class="col-md-5 mt-3 mt-md-0">
                                        <div class="p-3 rounded-4" style="background: #fffbeb; border: 1px border #fde68a;">
                                            <small class="text-warning-emphasis fw-bold d-block mb-1">
                                                <i class="bi bi-exclamation-triangle"></i> คำแนะนำ
                                            </small>
                                            <small class="text-muted d-block" style="font-size: 0.8rem;">
                                                ตรวจสอบภาพ QR ให้ชัดเจน และต้องมียอดโอนที่ตรงกับช่อง "ยอดโอนจริง" ด้านบน
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 mt-5 pt-3 border-top d-flex flex-column flex-md-row justify-content-end gap-3">
                                <a href="../sidebar.php?link=setting" class="btn btn-cancel">
                                    ยกเลิก
                                </a>
                                <button type="submit" class="btn btn-primary btn-submit px-5 text-white">
                                    <i class="bi bi-plus-circle me-2"></i> บันทึกและเปิดใช้งาน
                                </button>
                            </div>

                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // QR Preview Script
    const qrInput = document.getElementById('qrInput');
    const qrPreview = document.getElementById('qrPreview');
    const uploadPlaceholder = document.getElementById('uploadPlaceholder');

    qrInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(event) {
                qrPreview.innerHTML = `<img src="${event.target.result}" class="img-fluid">`;
                qrPreview.classList.remove('d-none');
                uploadPlaceholder.classList.add('d-none');
            }
            reader.readAsDataURL(file);
        }
    });
</script>

</body>
</html>