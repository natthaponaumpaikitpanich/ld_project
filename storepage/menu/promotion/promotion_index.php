<?php
// ไม่ต้องใช้ ob_start(); ถ้าไฟล์นี้ถูก include มาแล้วในไฟล์หลักที่มี ob_start อยู่แล้ว
// แต่ถ้าใช้แยกไฟล์เดี่ยวๆ ก็คงไว้ได้ครับ

/* =========================
    HANDLE POST ACTIONS
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // --- เพิ่มโปรโมชั่น ---
    if (isset($_POST['add_promotion'])) {
        $title      = $_POST['title'] ?? '';
        $discount   = (int)($_POST['discount'] ?? 0);
        $summary    = $_POST['summary'] ?? null;
        $message    = $_POST['message'] ?? ''; // แก้ไขปัญหา Undefined Key
        $start_date = $_POST['start_date'] ?? date('Y-m-d H:i:s');
        $end_date   = $_POST['end_date'] ?? null;

        $image_path = null;
        if (!empty($_FILES['image']['name'])) {
            $dir = "../uploads/promotions/";
            if (!is_dir($dir)) mkdir($dir, 0777, true);
            $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $filename = uniqid() . "." . $ext;
            move_uploaded_file($_FILES['image']['tmp_name'], $dir . $filename);
            $image_path = "uploads/promotions/" . $filename;
        }

        $stmt = $pdo->prepare("INSERT INTO promotions (id, created_by, store_id, title, discount, summary, message, image, start_date, end_date, status, audience) VALUES (UUID(), ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', 'store_specific')");
        $stmt->execute([$user_id, $store_id, $title, $discount, $summary, $message, $image_path, $start_date, $end_date]);

        echo "<script>window.location.href='" . $_SERVER['PHP_SELF'] . "';</script>";
        exit;
    }

    // --- แก้ไขโปรโมชั่น ---
    if (isset($_POST['edit_promotion'])) {
        $id = $_POST['promo_id'];
        $title = $_POST['title'];
        $discount = $_POST['discount'];
        $summary = $_POST['summary'];
        $message = $_POST['message'] ?? ''; // แก้ไขปัญหา Undefined Key
        $start_date = $_POST['start_date'];
        $end_date = $_POST['end_date'];
        $status = $_POST['status'];

        if (!empty($_FILES['image']['name'])) {
            $dir = "../uploads/promotions/";
            $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $filename = uniqid() . "." . $ext;
            move_uploaded_file($_FILES['image']['tmp_name'], $dir . $filename);
            $image_path = "uploads/promotions/" . $filename;

            $stmt = $pdo->prepare("UPDATE promotions SET title=?, discount=?, summary=?, message=?, start_date=?, end_date=?, status=?, image=? WHERE id=? AND store_id=?");
            $stmt->execute([$title, $discount, $summary, $message, $start_date, $end_date, $status, $image_path, $id, $store_id]);
        } else {
            $stmt = $pdo->prepare("UPDATE promotions SET title=?, discount=?, summary=?, message=?, start_date=?, end_date=?, status=? WHERE id=? AND store_id=?");
            $stmt->execute([$title, $discount, $summary, $message, $start_date, $end_date, $status, $id, $store_id]);
        }

        echo "<script>window.location.href='" . $_SERVER['PHP_SELF'] . "';</script>";
        exit;
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

        echo "<script>window.location.href='" . $_SERVER['PHP_SELF'] . "';</script>";
        exit;
    }
}

/* =========================
    LOAD PROMOTIONS
========================= */
$stmt = $pdo->prepare("SELECT * FROM promotions WHERE store_id = ? ORDER BY created_at DESC");
$stmt->execute([$store_id]);
$promotions = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
    :root {
        --main-blue: #0061ff;
        --light-blue: #60a5fa;
        --bg-color: #f0f7ff;
        --glass: rgba(255, 255, 255, 0.9);
    }

    .page-header {
        background: linear-gradient(135deg, var(--main-blue), var(--light-blue));
        padding: 40px 0;
        margin-bottom: -50px;
        color: white;
        border-radius: 0 0 50px 50px;
    }

    .promo-card {
        border: none;
        border-radius: 20px;
        background: var(--glass);
        transition: all 0.3s ease;
        overflow: hidden;
        position: relative;
    }

    .img-container {
        height: 180px;
        overflow: hidden;
        position: relative;
        background: #eee;
    }

    .img-container img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .action-btns {
        position: absolute;
        top: 10px;
        left: 10px;
        z-index: 20;
        display: flex;
        gap: 5px;
    }

    .btn-action {
        width: 35px;
        height: 35px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        border: none;
        color: white;
        transition: 0.2s;
    }

    .btn-edit {
        background: rgba(255, 193, 7, 0.9);
    }

    .btn-delete {
        background: rgba(220, 53, 69, 0.9);
    }

    .btn-primary-custom {
        background: linear-gradient(135deg, #0061ff 0%, #60a5fa 100%);
        border: none;
        border-radius: 12px;
        color: white;
        padding: 10px 20px;
    }
</style>

<div class="page-header text-center text-md-start">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1 class="fw-bold mb-0"><i class="bi bi-megaphone-fill me-2"></i> โปรโมชั่นร้านค้า</h1>
                <p class="opacity-75">สร้าง แก้ไข และจัดการแคมเปญของคุณ</p>
            </div>
            <div class="col-md-4 text-md-end">
                <button class="btn btn-light btn-lg fw-bold rounded-pill px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#addPromotionModal">
                    <i class="bi bi-plus-circle-fill me-2 text-primary"></i> เพิ่มโปรโมชั่น
                </button>
            </div>
        </div>
    </div>
</div>

<div class="container" style="margin-top: 80px; padding-bottom: 50px;">
    <div class="row">
        <?php foreach ($promotions as $p): ?>
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card promo-card h-100 shadow-sm">
                    <div class="action-btns">
                        <button class="btn-action btn-edit" onclick='openEditModal(<?= json_encode($p) ?>)'><i class="bi bi-pencil-square"></i></button>
                        <button class="btn-action btn-delete" onclick="confirmDelete('<?= $p['id'] ?>')"><i class="bi bi-trash"></i></button>
                    </div>
                    <div class="img-container">
                        <?php if ($p['image']): ?>
                            <img src="../<?= $p['image'] ?>" alt="Promo">
                        <?php else: ?>
                            <div class="w-100 h-100 d-flex align-items-center justify-content-center bg-secondary text-white"><i class="bi bi-image fs-1"></i></div>
                        <?php endif; ?>
                    </div>
                    <div class="card-body p-4">
                        <h5 class="fw-bold"><?= htmlspecialchars($p['title']) ?></h5>
                        <p class="text-muted small"><?= htmlspecialchars($p['summary']) ?></p>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="badge bg-primary">ลด <?= number_format($p['discount']) ?>.-</span>
                            <span class="badge <?= $p['status'] === 'active' ? 'bg-success' : 'bg-secondary' ?>"><?= $p['status'] ?></span>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="modal fade" id="addPromotionModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 30px;">
            <form method="post" enctype="multipart/form-data">
                <div class="modal-header bg-primary text-white p-4">
                    <h4 class="modal-title fw-bold"><i class="bi bi-plus-circle-fill me-2"></i> เพิ่มโปรโมชั่นใหม่</h4>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" name="add_promotion" value="1">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label fw-bold">ชื่อโปรโมชั่น</label>
                            <input type="text" name="title" class="form-control" required placeholder="เช่น โปรต้อนรับปีใหม่">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">ส่วนลด (บาท)</label>
                            <input type="number" name="discount" class="form-control" value="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">เริ่ม</label>
                            <input type="datetime-local" name="start_date" class="form-control" value="<?= date('Y-m-d\TH:i') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">สิ้นสุด</label>
                            <input type="datetime-local" name="end_date" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">คำโปรย (สั้นๆ แสดงหน้าการ์ด)</label>
                            <input type="text" name="summary" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">รายละเอียดโปรโมชั่น</label>
                            <textarea name="message" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">รูปภาพ</label>
                            <input type="file" name="image" class="form-control" accept="image/*">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4">
                    <button type="submit" class="btn btn-primary-custom px-5 rounded-pill">สร้างโปรโมชั่น</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 30px;">
            <form method="post" enctype="multipart/form-data">
                <div class="modal-header bg-primary text-light p-4">
                    <h4 class="modal-title fw-bold"><i class="bi bi-pencil-fill me-2"></i> แก้ไขโปรโมชั่น</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" name="edit_promotion" value="1">
                    <input type="hidden" name="promo_id" id="edit_id">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label fw-bold">ชื่อโปรโมชั่น</label>
                            <input type="text" name="title" id="edit_title" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">สถานะ</label>
                            <select name="status" id="edit_status" class="form-select">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">ส่วนลด (บาท)</label>
                            <input type="number" name="discount" id="edit_discount" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">เริ่ม</label>
                            <input type="datetime-local" name="start_date" id="edit_start" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">สิ้นสุด</label>
                            <input type="datetime-local" name="end_date" id="edit_end" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">คำโปรย</label>
                            <input type="text" name="summary" id="edit_summary" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">รายละเอียดโปรโมชั่น</label>
                            <textarea name="message" id="edit_message" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">เปลี่ยนรูปภาพ (ปล่อยว่างไว้ถ้าไม่เปลี่ยน)</label>
                            <input type="file" name="image" class="form-control" accept="image/*">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4">
                    <button type="submit" class="btn btn-primary-custom px-5 rounded-pill">บันทึกการเปลี่ยนแปลง</button>
                </div>
            </form>
        </div>
    </div>
</div>

<form id="deleteForm" method="post" style="display:none;">
    <input type="hidden" name="delete_promotion" value="1">
    <input type="hidden" name="promo_id" id="delete_id_input">
</form>

<script>
    function openEditModal(data) {
        document.getElementById('edit_id').value = data.id;
        document.getElementById('edit_title').value = data.title;
        document.getElementById('edit_discount').value = data.discount;
        document.getElementById('edit_summary').value = data.summary;
        document.getElementById('edit_message').value = data.message || '';
        document.getElementById('edit_status').value = data.status;

        if (data.start_date) document.getElementById('edit_start').value = data.start_date.replace(" ", "T").substring(0, 16);
        if (data.end_date) document.getElementById('edit_end').value = data.end_date.replace(" ", "T").substring(0, 16);

        new bootstrap.Modal(document.getElementById('editModal')).show();
    }

    function confirmDelete(id) {
        if (confirm('คุณแน่ใจหรือไม่ว่าต้องการลบโปรโมชั่นนี้?')) {
            document.getElementById('delete_id_input').value = id;
            document.getElementById('deleteForm').submit();
        }
    }
</script>