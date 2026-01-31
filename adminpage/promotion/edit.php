<?php
require "../../ld_db.php";

// Logic PHP เดิมทั้งหมด (ห้ามแก้)
if (!isset($_GET['id'])) {
    die("ไม่พบ ID โปรโมชั่น");
}
$id = $_GET['id'];
$sql = "SELECT * FROM promotions WHERE id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);
$promotion = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$promotion) {
    die("ไม่พบโปรโมชั่นนี้");
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $title = $_POST["title"];
    $discount = $_POST["discount"];
    $start_date = $_POST["start_date"];
    $end_date = $_POST["end_date"];
    $status = $_POST["status"];
    $old_image = $promotion["image"];

    if (!empty($_FILES["image"]["name"])) {
        $fileName = time() . "_" . basename($_FILES["image"]["name"]);
        $targetPath = "uploads/" . $fileName;
        if (move_uploaded_file($_FILES["image"]["tmp_name"], $targetPath)) {
            $image = $fileName;
        } else {
            $image = $old_image;
        }
    } else {
        $image = $old_image;
    }

    $updateSql = "UPDATE promotions SET title=?, discount=?, start_date=?, end_date=?, image=?, status=? WHERE id=?";
    $stmt = $pdo->prepare($updateSql);
    $stmt->execute([$title, $discount, $start_date, $end_date, $image, $status, $id]);

    header("Location: ../sidebar/sidebar.php?link=promotion&updated=1");
    exit;
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="../../image/3.jpg">
    <title>แก้ไขโปรโมชั่น | Smart Editor</title>

    <link href="../../bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600&display=swap" rel="stylesheet">

    <style>
        :root {
            --accent-color: #4361ee;
            --bg-body: #f8f9fc;
        }

        body {
            font-family: 'Kanit', sans-serif;
            background-color: var(--bg-body);
            color: #333;
        }

        /* Hero Header */
        .edit-header {
            background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
            color: white;
            padding: 40px 0 100px 0;
            margin-bottom: -60px;
        }

        /* Glass Card */
        .main-card {
            border: none;
            border-radius: 24px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.08);
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            overflow: hidden;
        }

        .form-section-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--accent-color);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Form Styling */
        .form-label {
            font-weight: 500;
            font-size: 0.9rem;
            color: #64748b;
        }

        .form-control,
        .form-select {
            border-radius: 12px;
            padding: 12px 15px;
            border: 1px solid #e2e8f0;
            background-color: #fcfcfd;
            transition: all 0.2s;
        }

        .form-control:focus {
            box-shadow: 0 0 0 4px rgba(67, 97, 238, 0.1);
            border-color: var(--accent-color);
        }

        /* Image Preview Area */
        .image-upload-wrapper {
            position: relative;
            border: 2px dashed #cbd5e1;
            border-radius: 20px;
            padding: 20px;
            text-align: center;
            transition: 0.3s;
            background: #f8fafc;
        }

        .image-upload-wrapper:hover {
            border-color: var(--accent-color);
            background: #f1f5f9;
        }

        #preview-img {
            max-width: 100%;
            border-radius: 12px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
            margin-bottom: 15px;
        }

        /* Floating Buttons */
        .action-bar {
            background: white;
            padding: 20px;
            border-top: 1px solid #f1f5f9;
            display: flex;
            justify-content: flex-end;
            gap: 12px;
        }

        .btn-save {
            background: var(--accent-color);
            border: none;
            padding: 12px 35px;
            border-radius: 12px;
            font-weight: 600;
            box-shadow: 0 10px 20px rgba(67, 97, 238, 0.2);
            transition: 0.3s;
        }

        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 25px rgba(67, 97, 238, 0.3);
        }

        .input-group-text {
            background: white;
            border-radius: 12px 0 0 12px;
            border-right: none;
        }

        .has-icon .form-control {
            border-left: none;
        }
    </style>
</head>

<body>

    <div class="edit-header">
        <div class="container">
            <div class="d-flex align-items-center gap-3">
                <a href="../sidebar/sidebar.php?link=promotion" class="btn btn-outline-light rounded-circle p-2" style="width: 45px; height: 45px;">
                    <i class="bi bi-arrow-left"></i>
                </a>
                <div>
                    <h2 class="fw-bold mb-0">แก้ไขข้อมูลโปรโมชั่น</h2>
                    <p class="text-white-50 mb-0">ปรับแต่งรายละเอียดและตั้งค่าการแสดงผลของแคมเปญ</p>
                </div>
            </div>
        </div>
    </div>

    <div class="container mb-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <form action="" method="POST" enctype="multipart/form-data" class="main-card card">
                    <div class="card-body p-4 p-md-5">

                        <div class="row g-4">
                            <div class="col-md-7">
                                <div class="form-section-title">
                                    <i class="bi bi-info-circle-fill"></i> ข้อมูลพื้นฐาน
                                </div>

                                <div class="mb-4">
                                    <label class="form-label">ชื่อโปรโมชั่น / หัวข้อแคมเปญ</label>
                                    <input type="text" name="title" class="form-control form-control-lg"
                                        placeholder="เช่น ลดกระหน่ำซัมเมอร์เซฟ"
                                        value="<?= htmlspecialchars($promotion['title']) ?>" required>
                                </div>

                                <div class="row mb-4">
                                    <div class="col-sm-6">
                                        <label class="form-label">ส่วนลด (%)</label>
                                        <div class="input-group has-icon">
                                            <span class="input-group-text"><i class="bi bi-percent"></i></span>
                                            <input type="number" name="discount" class="form-control"
                                                value="<?= $promotion['discount'] ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="form-label">สถานะการใช้งาน</label>
                                        <select name="status" class="form-select">
                                            <option value="active" <?= $promotion['status'] === 'active' ? 'selected' : '' ?>>เปิดใช้งาน (Active)</option>
                                            <option value="draft" <?= $promotion['status'] === 'draft' ? 'selected' : '' ?>>ฉบับร่าง (Draft)</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="form-section-title mt-5">
                                    <i class="bi bi-calendar-event-fill"></i> กำหนดเวลาแคมเปญ
                                </div>
                                <div class="row">
                                    <div class="col-sm-6 mb-3">
                                        <label class="form-label">วันที่และเวลาเริ่ม</label>
                                        <input type="datetime-local" name="start_date" class="form-control"
                                            value="<?= $promotion['start_date'] ? date('Y-m-d\TH:i', strtotime($promotion['start_date'])) : '' ?>" required>
                                    </div>
                                    <div class="col-sm-6 mb-3">
                                        <label class="form-label">วันที่และเวลาสิ้นสุด</label>
                                        <input type="datetime-local" name="end_date" class="form-control"
                                            value="<?= $promotion['end_date'] ? date('Y-m-d\TH:i', strtotime($promotion['end_date'])) : '' ?>" required>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-5">
                                <div class="form-section-title">
                                    <i class="bi bi-image-fill"></i> ภาพหน้าปกโปรโมชั่น
                                </div>

                                <div class="image-upload-wrapper">
                                    <div id="image-container">
                                        <?php if (!empty($promotion['image'])): ?>
                                            <img src="../../<?= htmlspecialchars($promotion['image']) ?>" id="preview-img" alt="Current Image">
                                        <?php else: ?>
                                            <div class="py-5" id="no-image-placeholder">
                                                <i class="bi bi-cloud-arrow-up text-muted" style="font-size: 3rem;"></i>
                                                <p class="text-muted small">ยังไม่มีรูปภาพประกอบ</p>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="mt-3">
                                        <label for="image-input" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                            <i class="bi bi-camera me-1"></i> เปลี่ยนรูปภาพ
                                        </label>
                                        <input type="file" name="image" id="image-input" class="d-none" accept="image/*">
                                        <p class="text-muted mt-2" style="font-size: 0.75rem;">แนะนำขนาด 1200x600px (JPG, PNG)</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <div class="action-bar">
                        <a href="../sidebar/sidebar.php?link=promotion" class="btn btn-light text-muted px-4">ยกเลิก</a>
                        <button type="submit" class="btn btn-primary btn-save px-5">
                            <i class="bi bi-check2-circle me-2"></i> บันทึกการเปลี่ยนแปลง
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="../../bootstrap/js/bootstrap.bundle.min.js"></script>
    <script>
        // ระบบ Live Preview รูปภาพ
        const imageInput = document.getElementById('image-input');
        const previewImg = document.getElementById('preview-img');
        const container = document.getElementById('image-container');

        imageInput.onchange = evt => {
            const [file] = imageInput.files;
            if (file) {
                // สร้าง Element รูปใหม่ถ้าของเดิมไม่มี
                if (!document.getElementById('preview-img')) {
                    container.innerHTML = '<img src="" id="preview-img" alt="Preview">';
                }
                document.getElementById('preview-img').src = URL.createObjectURL(file);

                // ใส่ Animation เล็กน้อย
                document.getElementById('preview-img').style.animation = "fadeIn 0.5s";
            }
        }
    </script>

    <style>
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</body>

</html>