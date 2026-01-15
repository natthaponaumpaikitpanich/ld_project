<?php
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'store_owner') {
    die('no permission');
}

$owner_id = $_SESSION['user_id'];

/* ===== FETCH STORE ===== */
$stmt = $pdo->prepare("
    SELECT
        s.id AS store_id,
        s.name AS store_name,
        s.address,
        s.phone,
        s.status,
        s.timezone,
        s.created_at,
        u.display_name,
        u.email,
        u.phone AS owner_phone
    FROM stores s
    JOIN users u ON s.owner_id = u.id
    WHERE s.owner_id = ?
    LIMIT 1
");
$stmt->execute([$owner_id]);
$store = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$store) {
    echo '<div class="alert alert-danger">ไม่พบข้อมูลร้าน</div>';
    exit;
}
?>

<style>
.profile-header{
    background:linear-gradient(135deg,#0d6efd,#20c997);
    color:#fff;
    border-radius:20px;
}
</style>
</head>
<!-- ===== HEADER ===== -->
<div class="profile-header p-4 mb-4 shadow-sm">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h4 class="fw-bold mb-1">🏪 <?= htmlspecialchars($store['store_name']) ?></h4>
            <small class="opacity-75">โปรไฟล์ร้านค้า</small>
        </div>
        <button class="btn btn-light btn-sm fw-semibold"
                data-bs-toggle="modal"
                data-bs-target="#editProfileModal">
            ✏️ แก้ไขโปรไฟล์
        </button>
    </div>
</div>

<!-- ===== STORE INFO ===== -->
<div class="card shadow-sm mb-4">
<div class="card-body">
    <h6 class="fw-bold mb-3">📍 ข้อมูลร้าน</h6>

    <p><strong>ที่อยู่:</strong><br><?= nl2br(htmlspecialchars($store['address'])) ?></p>
    <p><strong>เบอร์ร้าน:</strong> <?= htmlspecialchars($store['phone']) ?></p>
    <p><strong>สถานะ:</strong>
        <span class="badge bg-<?= $store['status']==='active'?'success':'secondary' ?>">
            <?= strtoupper($store['status']) ?>
        </span>
    </p>
</div>
</div>

<!-- ===== OWNER INFO ===== -->
<div class="card shadow-sm">
<div class="card-body">
    <h6 class="fw-bold mb-3">👤 เจ้าของร้าน</h6>

    <p><strong>ชื่อ:</strong> <?= htmlspecialchars($store['display_name']) ?></p>
    <p><strong>Email:</strong> <?= htmlspecialchars($store['email']) ?></p>
    <p><strong>เบอร์:</strong> <?= htmlspecialchars($store['owner_phone']) ?></p>
</div>
</div>

</div>

<!-- ================= MODAL EDIT ================= -->
<div class="modal fade" id="editProfileModal" tabindex="-1">
<div class="modal-dialog modal-dialog-centered modal-lg">
<div class="modal-content rounded-4">

<form method="post" action="menu/profile/edit.php">

<div class="modal-header">
    <h5 class="modal-title fw-bold">✏️ แก้ไขโปรไฟล์ร้าน</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>

<div class="modal-body">

    <div class="mb-3">
        <label class="form-label">ชื่อร้าน</label>
        <input type="text" name="store_name" class="form-control"
               value="<?= htmlspecialchars($store['store_name']) ?>" required>
    </div>

    <div class="mb-3">
        <label class="form-label">ที่อยู่ร้าน</label>
        <textarea name="address" class="form-control" rows="3"><?= htmlspecialchars($store['address']) ?></textarea>
    </div>

    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label">เบอร์โทรร้าน</label>
            <input type="text" name="phone" class="form-control"
                   value="<?= htmlspecialchars($store['phone']) ?>">
        </div>
        <div class="col-md-6">
            <label class="form-label">Timezone</label>
            <select name="timezone" class="form-select">
                <option value="Asia/Bangkok" <?= $store['timezone']=='Asia/Bangkok'?'selected':'' ?>>Asia/Bangkok</option>
                <option value="UTC" <?= $store['timezone']=='UTC'?'selected':'' ?>>UTC</option>
            </select>
        </div>
    </div>

</div>

<div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
    <button class="btn btn-primary">💾 บันทึก</button>
</div>

</form>

</div>
</div>
</div>

<script src="../bootstrap/js/bootstrap.bundle.js"></script>
</body>
