<?php
// PHP LOGIC เดิมของคุณทั้งหมด (ห้ามแก้)
$store_id = $_SESSION['store_id'] ?? null;
if (!$store_id) {
    die("ไม่พบร้าน");
}

$stmt = $pdo->prepare("
    SELECT 
        ss.id AS staff_id,
        u.display_name,
        u.email,
        u.phone,
        u.profile_image,
        ss.role,
        ss.created_at
    FROM store_staff ss
    JOIN users u ON ss.user_id = u.id
    WHERE ss.store_id = ?
      AND ss.role != 'store_owner'
    ORDER BY ss.created_at DESC
");
$stmt->execute([$store_id]);
$staffs = $stmt->fetchAll(PDO::FETCH_ASSOC);

function staff_image($img){
    if ($img) {
        $path = '/ld_project/' . ltrim($img,'/');
        if (file_exists($_SERVER['DOCUMENT_ROOT'].$path)) {
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
    }

    body {
        background-color: var(--light-bg);
        font-family: 'Inter', 'Kanit', sans-serif;
    }

    /* Header Styling */
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

    /* Card & Table Styling */
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
        letter-spacing: 0.5px;
        padding: 1.2rem;
        border: none;
    }

    .table tbody tr {
        transition: all 0.3s ease;
    }

    .table tbody tr:hover {
        background-color: #ffffff !important;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
    }

    /* Staff Avatar */
    .avatar-wrapper {
        position: relative;
        display: inline-block;
    }
    .avatar-img {
        width: 48px;
        height: 48px;
        object-fit: cover;
        border-radius: 14px; /* ทรงเหลี่ยมมนดูโมเดิร์นกว่าวงกลม */
        border: 2px solid #fff;
        box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        transition: 0.3s;
    }
    tr:hover .avatar-img {
        border-color: var(--primary-blue);
        transform: scale(1.1) rotate(5deg);
    }

    /* Badge Style */
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
        box-shadow: 0 4px 15px rgba(0, 123, 255, 0.3);
        transition: 0.3s;
    }
    .btn-add-staff:hover {
        transform: translateY(-3px);
        box-shadow: 0 6px 20px rgba(0, 123, 255, 0.4);
        color: white;
    }

    .btn-delete-custom {
        color: #ff4d4d;
        background-color: #fff0f0;
        border: none;
        border-radius: 10px;
        padding: 8px 15px;
        transition: 0.2s;
    }
    .btn-delete-custom:hover {
        background-color: #ff4d4d;
        color: #fff;
    }

    /* Modal Styling */
    .modal-content {
        border-radius: 25px;
        border: none;
    }
    .modal-header {
        border-radius: 25px 25px 0 0;
        background: linear-gradient(45deg, var(--primary-blue), var(--dark-blue)) !important;
    }
    .form-control {
        border-radius: 12px;
        padding: 12px;
        border: 1px solid #e0e0e0;
        background-color: #f9f9f9;
    }
    .form-control:focus {
        background-color: #fff;
        box-shadow: 0 0 0 4px rgba(0, 123, 255, 0.1);
    }
</style>

<div class="container  py-4">
    <div class="d-flex justify-content-between align-items-end mb-4">
        <div>
            <h2 class="fw-bold section-title mb-1">จัดการพนักงาน</h2>
            <p class="text-muted small mb-0">ดูแลและบริหารจัดการสิทธิ์การเข้าถึงของทีมงาน</p>
        </div>
        <button class="btn btn-primary btn-add-staff" data-bs-toggle="modal" data-bs-target="#addStaffModal">
            <i class="bi bi-person-plus-fill me-2"></i> เพิ่มพนักงานใหม่
        </button>
    </div>

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
                                <img src="" style="width: 80px; opacity: 0.5" class="mb-3">
                                <p class="text-muted">ไม่พบข้อมูลพนักงานในขณะนี้</p>
                            </td>
                        </tr>
                        <?php endif; ?>

                        <?php foreach ($staffs as $s): ?>
                        <tr>
                            <td class="ps-4">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="avatar-wrapper">
                                        <img src="<?= staff_image($s['profile_image']) ?>" class="avatar-img shadow-sm">
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark mb-0">
                                            <?= htmlspecialchars($s['display_name']) ?>
                                        </div>
                                        <div class="text-muted small">
                                            <i class="bi bi-envelope me-1"></i><?= htmlspecialchars($s['email']) ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="text-dark fw-medium">
                                    <i class="bi bi-telephone-outbound text-primary me-2"></i><?= htmlspecialchars($s['phone']) ?>
                                </div>
                            </td>
                            <td>
                                <span class="badge-staff">
                                    <i class="bi bi-shield-check me-1"></i> <?= strtoupper($s['role']) ?>
                                </span>
                            </td>
                            <td>
                                <div class="text-muted small">
                                    <i class="bi bi-calendar3 me-1"></i> <?= date('d M Y', strtotime($s['created_at'])) ?>
                                </div>
                            </td>
                            <td class="text-end pe-4">
                                <a href="menu/staff_edit/staff_delete.php?id=<?= $s['staff_id'] ?>" 
                                   class="btn btn-delete-custom"
                                   onclick="return confirm('⚠️ คุณต้องการลบพนักงานคนนี้ออกจากระบบใช่หรือไม่?')">
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
      <div class="modal-header text-white px-4 py-3">
        <h5 class="modal-title fw-bold">➕ เพิ่มพนักงานเข้าร้าน</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="post" action="menu/staff_edit/staff_add.php">
        <div class="modal-body p-4">
          <div class="mb-4">
            <label class="form-label fw-semibold">อีเมลพนักงาน <span class="text-danger">*</span></label>
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0"><i class="bi bi-envelope text-primary"></i></span>
                <input type="email" name="email" class="form-control border-start-0" placeholder="example@email.com" required>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">เบอร์โทรศัพท์ <span class="text-danger">*</span></label>
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0"><i class="bi bi-phone text-primary"></i></span>
                <input type="text" name="phone" class="form-control border-start-0" placeholder="08x-xxx-xxxx" required>
            </div>
          </div>
        </div>
        <div class="modal-footer border-0 px-4 pb-4">
          <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">ยกเลิก</button>
          <button type="submit" class="btn btn-primary btn-add-staff px-5 rounded-pill">บันทึกข้อมูล</button>
        </div>
      </form>
    </div>
  </div>
</div>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
<script src="../bootstrap/js/bootstrap.bundle.js"></script>