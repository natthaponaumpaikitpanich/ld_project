<?php
// --- 1. PHP LOGIC สำหรับจัดการสถานะพนักงาน ---
$store_id = $_SESSION['store_id'] ?? null;
if (!$store_id) {
    die("ไม่พบร้าน");
}

// --- ดึงพนักงานที่รออนุมัติ (Pending) ---
$stmtPending = $pdo->prepare("
    SELECT ss.id AS staff_id, u.display_name, u.email, u.phone, u.profile_image, ss.requested_at
    FROM store_staff ss
    JOIN users u ON ss.user_id = u.id
    WHERE ss.store_id = ? AND ss.status = 'pending'
    ORDER BY ss.requested_at ASC
");
$stmtPending->execute([$store_id]);
$pending_staffs = $stmtPending->fetchAll(PDO::FETCH_ASSOC);

// --- ดึงพนักงานที่ทำงานอยู่ (Active) ---
$stmtActive = $pdo->prepare("
    SELECT ss.id AS staff_id, u.display_name, u.email, u.phone, u.profile_image, ss.role, ss.created_at
    FROM store_staff ss
    JOIN users u ON ss.user_id = u.id
    WHERE ss.store_id = ? AND ss.role != 'store_owner' AND ss.status = 'active'
    ORDER BY ss.created_at DESC
");
$stmtActive->execute([$store_id]);
$staffs = $stmtActive->fetchAll(PDO::FETCH_ASSOC);

function staff_image($img)
{
    if ($img) {
        $path = '/ld_project/' . ltrim($img, '/');
        if (file_exists($_SERVER['DOCUMENT_ROOT'] . $path)) {
            return $path;
        }
    }
    return '/ld_project/assets/img/user.png';
}
?>

<style>
    :root {
        --primary-blue: #007bff;
        --soft-blue: #e7f1ff;
        --dark-blue: #0056b3;
        --light-bg: #f8fbff;
        --success-green: #28a745;
        --danger-red: #dc3545;
    }

    body {
        background-color: var(--light-bg);
        font-family: 'Inter', 'Kanit', sans-serif;
    }

    /* Header & General */
    .section-title {
        color: #2c3e50;
        position: relative;
        display: inline-block;
        padding-bottom: 10px;
    }

    .section-title::after {
        content: '';
        position: absolute;
        width: 50%;
        height: 4px;
        background: linear-gradient(90deg, var(--primary-blue), transparent);
        bottom: 0;
        left: 0;
        border-radius: 2px;
    }

    /* Pending Card Styling (ส่วนใหม่) */
    .pending-card {
        background: white;
        border-radius: 20px;
        border-left: 5px solid var(--primary-blue);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.05);
        transition: 0.3s;
    }

    .pending-card:hover {
        transform: translateY(-5px);
    }

    .btn-approve {
        background-color: var(--success-green);
        color: white;
        border-radius: 10px;
        border: none;
        padding: 5px 15px;
        font-weight: 500;
    }

    .btn-reject {
        background-color: #fff0f0;
        color: var(--danger-red);
        border-radius: 10px;
        border: none;
        padding: 5px 15px;
        font-weight: 500;
    }

    /* Table & Card */
    .custom-card {
        border-radius: 20px;
        border: none;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(0, 123, 255, 0.05);
    }

    .table thead th {
        background-color: var(--soft-blue);
        color: var(--dark-blue);
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.85rem;
        padding: 1.2rem;
        border: none;
    }

    .avatar-img {
        width: 48px;
        height: 48px;
        object-fit: cover;
        border-radius: 14px;
        border: 2px solid #fff;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    }

    .badge-staff {
        background-color: var(--soft-blue);
        color: var(--primary-blue);
        font-weight: 500;
        padding: 8px 16px;
        border-radius: 10px;
    }

    /* Action Buttons */
    .btn-add-staff {
        background: linear-gradient(45deg, var(--primary-blue), var(--dark-blue));
        border: none;
        padding: 12px 24px;
        border-radius: 12px;
        font-weight: 600;
        color: white;
        box-shadow: 0 4px 15px rgba(0, 123, 255, 0.3);
    }

    .btn-delete-custom {
        color: #ff4d4d;
        background-color: #fff0f0;
        border: none;
        border-radius: 10px;
        padding: 8px 15px;
        text-decoration: none;
        font-size: 0.9rem;
    }

    .btn-delete-custom:hover {
        background-color: #ff4d4d;
        color: #fff;
    }
</style>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-end mb-4">
        <div>
            <h2 class="fw-bold section-title mb-1">จัดการพนักงาน</h2>
            <p class="text-muted small mb-0">ดูแลและบริหารจัดการคำขอเข้าร่วมงานและพนักงานในสังกัด</p>
        </div>
        <button class="btn btn-primary btn-add-staff" data-bs-toggle="modal" data-bs-target="#addStaffModal">
            <i class="bi bi-person-plus-fill me-2"></i> เพิ่มพนักงานด้วยตนเอง
        </button>
    </div>

    <?php if (!empty($pending_staffs)): ?>
        <div class="mb-5">
            <h5 class="fw-bold mb-3 text-primary"><i class="bi bi-bell-fill me-2"></i>คำขอใหม่ที่รอการตรวจสอบ</h5>
            <div class="row g-3">
                <?php foreach ($pending_staffs as $p): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="pending-card p-3">
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <img src="<?= staff_image($p['profile_image']) ?>" class="avatar-img">
                                <div class="overflow-hidden">
                                    <h6 class="mb-0 fw-bold text-truncate"><?= htmlspecialchars($p['display_name']) ?></h6>
                                    <small class="text-muted d-block text-truncate"><?= htmlspecialchars($p['email']) ?></small>
                                </div>
                            </div>
                            <div class="d-flex gap-2">
                                <a href="menu/staff_edit/staff_approve.php?id=<?= $p['staff_id'] ?>&action=approve" class="btn-approve btn-sm flex-grow-1 text-center text-decoration-none">อนุมัติ</a>
                                <a href="menu/staff_edit/staff_approve.php?id=<?= $p['staff_id'] ?>&action=reject" class="btn-reject btn-sm flex-grow-1 text-center text-decoration-none" onclick="return confirm('ปฏิเสธคำขอนี้?')">ปฏิเสธ</a>
                            </div>
                            <div class="mt-2 text-center">
                                <small style="font-size: 0.7rem;" class="text-muted">ส่งคำขอเมื่อ: <?= date('d/m/Y H:i', strtotime($p['requested_at'])) ?></small>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <hr class="mt-5 opacity-10">
        </div>
    <?php endif; ?>

    <div class="card custom-card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">ข้อมูลพนักงาน</th>
                            <th>เบอร์โทรศัพท์</th>
                            <th>บทบาทหน้าที่</th>
                            <th>เข้าร่วมเมื่อ</th>
                            <th class="text-end pe-4">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($staffs)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-5">
                                    <i class="bi bi-people text-muted fs-1 mb-3 d-block"></i>
                                    <p class="text-muted">ไม่พบข้อมูลพนักงานที่อนุมัติแล้วในขณะนี้</p>
                                </td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach ($staffs as $s): ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="d-flex align-items-center gap-3">
                                        <img src="<?= staff_image($s['profile_image']) ?>" class="avatar-img shadow-sm">
                                        <div>
                                            <div class="fw-bold text-dark mb-0"><?= htmlspecialchars($s['display_name']) ?></div>
                                            <div class="text-muted small"><?= htmlspecialchars($s['email']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="text-dark fw-medium"><?= htmlspecialchars($s['phone']) ?></div>
                                </td>
                                <td><span class="badge-staff"><?= strtoupper($s['role']) ?></span></td>
                                <td>
                                    <div class="text-muted small"><?= date('d M Y', strtotime($s['created_at'])) ?></div>
                                </td>
                                <td class="text-end pe-4">
                                    <a href="menu/staff_edit/staff_delete.php?id=<?= $s['staff_id'] ?>"
                                        class="btn btn-delete-custom"
                                        onclick="return confirm('⚠️ ต้องการลบพนักงานคนนี้ใช่หรือไม่?')">
                                        <i class="bi bi-trash3"></i> ลบออก
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="addStaffModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg">
            <div class="modal-header text-white px-4 py-3" style="background: linear-gradient(45deg, var(--primary-blue), var(--dark-blue));">
                <h5 class="modal-title fw-bold">➕ เพิ่มพนักงานเข้าร้าน</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="post" action="menu/staff_edit/staff_add.php">
                <div class="modal-body p-4">
                    <div class="mb-4">
                        <label class="form-label fw-semibold">อีเมลพนักงาน <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control" placeholder="example@email.com" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">บทบาท <span class="text-danger">*</span></label>
                        <select name="role" class="form-control">
                            <option value="staff">Staff</option>
                            <option value="manager">Manager</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0 px-4 pb-4">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary px-5 rounded-pill shadow">บันทึกข้อมูล</button>
                </div>
            </form>
        </div>
    </div>
</div>