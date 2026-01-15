<?php
include_once "assets/boostap.php";


if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'store_owner') {
    die("ไม่มีสิทธิ์เข้าถึง");
}

$store_id = $_SESSION['store_id'];
$user_id  = $_SESSION['user_id'];

/* =========================
   HANDLE ADD PROMOTION
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_promotion'])) {

    $title      = $_POST['title'] ?? '';
    $discount   = (int)($_POST['discount'] ?? 0);
    $summary    = $_POST['summary'] ?? null;
    $message    = $_POST['message'] ?? null;
    $start_date = $_POST['start_date'] ?? date('Y-m-d H:i:s');
    $end_date   = $_POST['end_date'] ?? null;

    if (!$title) {
        die("กรุณากรอกชื่อโปรโมชั่น");
    }

    /* upload image (optional) */
    $image_path = null;
    if (!empty($_FILES['image']['name'])) {
        $dir = "../uploads/promotions/";
        if (!is_dir($dir)) mkdir($dir, 0777, true);

        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $filename = uniqid().".".$ext;
        move_uploaded_file($_FILES['image']['tmp_name'], $dir.$filename);
        $image_path = "uploads/promotions/".$filename;
    }

    $stmt = $pdo->prepare("
        INSERT INTO promotions (
            id, created_by, store_id,
            title, discount, summary, message, image,
            start_date, end_date, status, audience
        ) VALUES (
            UUID(), ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', 'store_specific'
        )
    ");
    $stmt->execute([
        $user_id,
        $store_id,
        $title,
        $discount,
        $summary,
        $message,
        $image_path,
        $start_date,
        $end_date
    ]);

    exit;
}

/* =========================
   LOAD PROMOTIONS
========================= */
$stmt = $pdo->prepare("
    SELECT *
    FROM promotions
    WHERE store_id = ?
    ORDER BY created_at DESC
");
$stmt->execute([$store_id]);
$promotions = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>โปรโมชั่นร้าน</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
</head>

<body>
<div class="container mt-4">

<h4 class="fw-bold mb-3">🎉 โปรโมชั่นร้าน</h4>

<button class="btn btn-primary mb-3"
        data-bs-toggle="modal"
        data-bs-target="#addPromotionModal">
➕ เพิ่มโปรโมชั่น
</button>

<?php if (!$promotions): ?>
<div class="alert alert-info">ยังไม่มีโปรโมชั่น</div>
<?php endif; ?>

<?php foreach ($promotions as $p): ?>
<div class="card mb-2 shadow-sm">
<div class="card-body">

<h6 class="fw-bold"><?= htmlspecialchars($p['title']) ?></h6>

<?php if ($p['summary']): ?>
<p class="mb-1"><?= htmlspecialchars($p['summary']) ?></p>
<?php endif; ?>

<span class="badge bg-success">
ลด <?= (int)$p['discount'] ?> บาท
</span>

<span class="badge <?= $p['status'] === 'active' ? 'bg-primary' : 'bg-secondary' ?>">
<?= $p['status'] ?>
</span>

<?php if ($p['image']): ?>
<div class="mt-2">
<img src="../<?= $p['image'] ?>" class="img-fluid rounded" style="max-height:150px;">
</div>
<?php endif; ?>

</div>
</div>
<?php endforeach; ?>

</div>

<!-- =========================
     MODAL ADD PROMOTION
========================= -->
<div class="modal fade" id="addPromotionModal" tabindex="-1">
<div class="modal-dialog modal-dialog-centered modal-lg">
<div class="modal-content">

<form method="post" enctype="multipart/form-data">

<div class="modal-header">
<h5 class="modal-title">➕ เพิ่มโปรโมชั่น</h5>
<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>

<div class="modal-body">

<input type="hidden" name="add_promotion" value="1">

<div class="mb-3">
<label class="form-label">ชื่อโปรโมชั่น</label>
<input type="text" name="title" class="form-control" required>
</div>

<div class="mb-3">
<label class="form-label">ส่วนลด (บาท)</label>
<input type="number" name="discount" class="form-control" value="0">
</div>

<div class="mb-3">
<label class="form-label">สรุปสั้น</label>
<input type="text" name="summary" class="form-control">
</div>

<div class="mb-3">
<label class="form-label">รายละเอียด</label>
<textarea name="message" class="form-control" rows="3"></textarea>
</div>

<div class="row">
<div class="col">
<label class="form-label">เริ่ม</label>
<input type="datetime-local" name="start_date" class="form-control">
</div>
<div class="col">
<label class="form-label">สิ้นสุด</label>
<input type="datetime-local" name="end_date" class="form-control">
</div>
</div>

<div class="mb-3 mt-3">
<label class="form-label">รูปโปรโมชั่น (ไม่บังคับ)</label>
<input type="file" name="image" class="form-control">
</div>

</div>

<div class="modal-footer">
<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
ยกเลิก
</button>
<button class="btn btn-primary">
บันทึกโปรโมชั่น
</button>
</div>

</form>

</div>
</div>
</div>

<script src="../bootstrap/js/bootstrap.bundle.js"></script>
</body>
</html>