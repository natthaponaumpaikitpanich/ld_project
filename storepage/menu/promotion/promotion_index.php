<?php
// เริ่มต้น Session เพื่อดึงค่า user_id และ store_id
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ตั้งค่า Timezone
date_default_timezone_set('Asia/Bangkok');

// ดึงค่าจาก Session (แก้ปัญหา Undefined variable)
$user_id = $_SESSION['user_id'] ?? null;
$store_id = $_SESSION['store_id'] ?? null;

/* =========================
    HANDLE POST ACTIONS
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // --- เพิ่ม / แก้ไข โปรโมชั่น ---
    if (isset($_POST['save_promotion'])) {
        $id              = $_POST['promo_id'] ?: null;
        $title           = $_POST['title'];
        $discount        = (float)$_POST['discount'];
        $discount_type   = $_POST['discount_type'];
        $min_requirement = (int)$_POST['min_requirement'];
        $summary         = $_POST['summary'];
        $message         = $_POST['message'];
        $start_date      = $_POST['start_date'];
        $end_date        = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
        $status          = $_POST['status'] ?? 'active';

        // จัดการรูปภาพ
        $image_path = $_POST['existing_image'] ?? null;
        if (!empty($_FILES['image']['name'])) {
            $dir = "../uploads/promotions/";
            if (!is_dir($dir)) mkdir($dir, 0777, true);
            $filename = uniqid() . "_" . basename($_FILES['image']['name']);
            if (move_uploaded_file($_FILES['image']['tmp_name'], $dir . $filename)) {
                if ($image_path && file_exists("../" . $image_path)) unlink("../" . $image_path);
                $image_path = "uploads/promotions/" . $filename;
            }
        }

        try {
            if ($id) {
                // UPDATE
                $sql = "UPDATE promotions SET title=?, discount=?, discount_type=?, min_requirement=?, summary=?, message=?, start_date=?, end_date=?, status=?, image=? WHERE id=? AND store_id=?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$title, $discount, $discount_type, $min_requirement, $summary, $message, $start_date, $end_date, $status, $image_path, $id, $store_id]);
            } else {
                // INSERT
                $sql = "INSERT INTO promotions (id, created_by, store_id, title, discount, discount_type, min_requirement, summary, message, image, start_date, end_date, status, audience) 
                        VALUES (UUID(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', 'store_specific')";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$user_id, $store_id, $title, $discount, $discount_type, $min_requirement, $summary, $message, $image_path, $start_date, $end_date]);
            }

            // ใช้ JS แทน header() เพื่อเลี่ยง Warning "Headers already sent"
            echo "<script>window.location.href='" . $_SERVER['PHP_SELF'] . "';</script>";
            exit;
        } catch (PDOException $e) {
            echo "<script>alert('Error: " . addslashes($e->getMessage()) . "');</script>";
        }
    }

    // --- ลบโปรโมชั่น ---
    if (isset($_POST['delete_promotion'])) {
        $id = $_POST['promo_id'];
        $stmt = $pdo->prepare("SELECT image FROM promotions WHERE id=? AND store_id=?");
        $stmt->execute([$id, $store_id]);
        $img = $stmt->fetchColumn();
        if ($img && file_exists("../" . $img)) unlink("../" . $img);

        $stmt = $pdo->prepare("DELETE FROM promotions WHERE id=? AND store_id=?");
        $stmt->execute([$id, $store_id]);
        echo "success"; // ตอบกลับให้ Fetch API
        exit;
    }
}

/* =========================
    LOAD DATA
========================= */
// ตรวจสอบก่อนดึงข้อมูล
if ($store_id) {
    $stmt = $pdo->prepare("SELECT * FROM promotions WHERE store_id = ? ORDER BY created_at DESC");
    $stmt->execute([$store_id]);
    $promotions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $promotions = [];
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการโปรโมชั่น</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root {
            --main-blue: #0061ff;
            --light-blue: #60a5fa;
            --bg-color: #f8fbff;
        }

        body {
            background-color: var(--bg-color);
            font-family: 'Kanit', sans-serif;
        }

        .page-header {
            background: linear-gradient(135deg, var(--main-blue), var(--light-blue));
            padding: 60px 0;
            border-radius: 0 0 40px 40px;
            color: white;
            margin-bottom: -40px;
        }

        .promo-card {
            border: none;
            border-radius: 25px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            overflow: hidden;
            background: white;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .promo-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
        }

        .img-container {
            height: 200px;
            position: relative;
            background: #f0f0f0;
        }

        .img-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .discount-badge {
            position: absolute;
            bottom: 15px;
            right: 15px;
            background: rgba(255, 255, 255, 0.95);
            padding: 8px 15px;
            border-radius: 15px;
            font-weight: bold;
            color: var(--main-blue);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .status-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 5px;
        }

        .btn-custom-primary {
            background: linear-gradient(135deg, var(--main-blue), var(--light-blue));
            border: none;
            color: white;
            border-radius: 12px;
            padding: 10px 25px;
        }
    </style>
</head>

<body>

    <div class="page-header text-center">
        <div class="container">
            <h1 class="fw-bold"><i class="bi bi-stars me-2"></i> ศูนย์จัดการโปรโมชั่น</h1>
            <p class="lead opacity-75">ออกแบบแคมเปญเพื่อดึงดูดใจลูกค้าของคุณ</p>
            <button class="btn btn-light rounded-pill px-4 fw-bold mt-3 shadow" onclick="openAddModal()">
                <i class="bi bi-plus-lg me-2"></i> สร้างโปรโมชั่นใหม่
            </button>
        </div>
    </div>

    <div class="container mt-5 pb-5">
        <div class="row g-4">
            <?php if (empty($promotions)): ?>
                <div class="col-12 text-center py-5">
                    <i class="bi bi-megaphone text-muted fs-1"></i>
                    <p class="text-muted mt-2">ยังไม่มีโปรโมชั่นในขณะนี้</p>
                </div>
            <?php endif; ?>

            <?php foreach ($promotions as $p): ?>
                <div class="col-lg-4 col-md-6">
                    <div class="card promo-card h-100 shadow-sm">
                        <div class="img-container">
                            <?php if ($p['image']): ?>
                                <img src="../<?= $p['image'] ?>" alt="Promo">
                            <?php else: ?>
                                <div class="w-100 h-100 d-flex align-items-center justify-content-center bg-light">
                                    <i class="bi bi-image text-muted fs-1"></i>
                                </div>
                            <?php endif; ?>
                            <div class="discount-badge">
                                <?= $p['discount_type'] == 'percentage' ? $p['discount'] . '%' : '฿' . number_format($p['discount']) ?> OFF
                            </div>
                        </div>

                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="small text-muted"><i class="bi bi-calendar3 me-1"></i> <?= date('d/m/y', strtotime($p['start_date'])) ?></span>
                                <span class="badge rounded-pill <?= $p['status'] == 'active' ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' ?>">
                                    <span class="status-dot bg-<?= $p['status'] == 'active' ? 'success' : 'secondary' ?>"></span>
                                    <?= strtoupper($p['status']) ?>
                                </span>
                            </div>
                            <h5 class="fw-bold mb-2"><?= htmlspecialchars($p['title']) ?></h5>
                            <p class="text-muted small mb-3"><?= htmlspecialchars($p['summary']) ?></p>

                            <?php if ($p['min_requirement'] > 0): ?>
                                <div class="alert alert-info py-1 px-2 small border-0 mb-3">
                                    <i class="bi bi-info-circle me-1"></i> เงื่อนไข: ซักครบ <?= $p['min_requirement'] ?> ครั้ง
                                </div>
                            <?php endif; ?>

                            <div class="d-flex gap-2">
                                <button class="btn btn-outline-primary btn-sm w-100 rounded-3" onclick='openEditModal(<?= json_encode($p) ?>)'>
                                    <i class="bi bi-pencil me-1"></i> แก้ไข
                                </button>
                                <button class="btn btn-outline-danger btn-sm rounded-3" onclick="confirmDelete('<?= $p['id'] ?>')">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="modal fade" id="promoModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 25px;">
                <form id="promoForm" method="post" enctype="multipart/form-data">
                    <div class="modal-header border-0 p-4 pb-0">
                        <h4 class="modal-title fw-bold" id="modalTitle">รายละเอียดโปรโมชั่น</h4>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <input type="hidden" name="save_promotion" value="1">
                        <input type="hidden" name="promo_id" id="f_id">
                        <input type="hidden" name="existing_image" id="f_existing_image">

                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label fw-bold small">ชื่อโปรโมชั่น</label>
                                <input type="text" name="title" id="f_title" class="form-control rounded-3" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold small">สถานะ</label>
                                <select name="status" id="f_status" class="form-select rounded-3">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold small">จำนวนส่วนลด</label>
                                <input type="number" name="discount" id="f_discount" class="form-control rounded-3" step="0.01" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold small">ประเภทส่วนลด</label>
                                <select name="discount_type" id="f_discount_type" class="form-select rounded-3">
                                    <option value="fixed">บาท (Fixed)</option>
                                    <option value="percentage">เปอร์เซ็นต์ (%)</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold small">เงื่อนไข (ซักครบกี่ครั้ง)</label>
                                <input type="number" name="min_requirement" id="f_min_requirement" class="form-control rounded-3" value="0">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold small">วันที่เริ่ม</label>
                                <input type="datetime-local" name="start_date" id="f_start" class="form-control rounded-3" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold small">วันที่สิ้นสุด</label>
                                <input type="datetime-local" name="end_date" id="f_end" class="form-control rounded-3">
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-bold small">คำโปรยการ์ด</label>
                                <input type="text" name="summary" id="f_summary" class="form-control rounded-3" maxlength="100">
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-bold small">รูปภาพโปรโมชั่น</label>
                                <input type="file" name="image" class="form-control rounded-3" accept="image/*">
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-bold small">รายละเอียดเพิ่มเติม</label>
                                <textarea name="message" id="f_message" class="form-control rounded-3" rows="3"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 p-4 pt-0">
                        <button type="submit" class="btn btn-custom-primary w-100 fw-bold">บันทึกโปรโมชั่น</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        const modalEl = document.getElementById('promoModal');
        const modal = new bootstrap.Modal(modalEl);

        function openAddModal() {
            document.getElementById('promoForm').reset();
            document.getElementById('f_id').value = '';
            document.getElementById('modalTitle').innerText = '🚀 สร้างโปรโมชั่นใหม่';
            // ตั้งค่าเวลาปัจจุบัน
            const now = new Date();
            now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
            document.getElementById('f_start').value = now.toISOString().slice(0, 16);
            modal.show();
        }

        function openEditModal(data) {
            document.getElementById('modalTitle').innerText = '📝 แก้ไขโปรโมชั่น';
            document.getElementById('f_id').value = data.id;
            document.getElementById('f_title').value = data.title;
            document.getElementById('f_discount').value = data.discount;
            document.getElementById('f_discount_type').value = data.discount_type;
            document.getElementById('f_min_requirement').value = data.min_requirement;
            document.getElementById('f_summary').value = data.summary;
            document.getElementById('f_message').value = data.message;
            document.getElementById('f_status').value = data.status;
            document.getElementById('f_existing_image').value = data.image;

            if (data.start_date) document.getElementById('f_start').value = data.start_date.replace(" ", "T").substring(0, 16);
            if (data.end_date) document.getElementById('f_end').value = data.end_date.replace(" ", "T").substring(0, 16);

            modal.show();
        }

        function confirmDelete(id) {
            Swal.fire({
                title: 'ยืนยันการลบ?',
                text: "ข้อมูลโปรโมชั่นจะหายไปอย่างถาวร!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'ลบเลย!',
                cancelButtonText: 'ยกเลิก'
            }).then((result) => {
                if (result.isConfirmed) {
                    const formData = new FormData();
                    formData.append('delete_promotion', '1');
                    formData.append('promo_id', id);
                    fetch('', {
                        method: 'POST',
                        body: formData
                    }).then(res => {
                        if (res.ok) location.reload();
                    });
                }
            });
        }
    </script>
</body>

</html>