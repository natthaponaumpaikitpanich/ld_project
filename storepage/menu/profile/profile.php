<?php
// PHP LOGIC เดิมของคุณทั้งหมด (ห้ามแก้)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'store_owner') {
    die('no permission');
}

$owner_id = $_SESSION['user_id'];

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

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
<style>
    :root {
        --main-blue: #0061ff;
        --soft-blue: #f0f7ff;
        --gradient-blue: linear-gradient(135deg, #0061ff 0%, #60a5fa 100%);
    }

    body {
        background-color: #f8fafc;
        font-family: 'Inter', 'Kanit', sans-serif;
    }

    /* Header Profile */
    .profile-header {
        background: var(--gradient-blue);
        color: #fff;
        border-radius: 24px;
        position: relative;
        overflow: hidden;
        border: none;
    }

    .profile-header::before {
        content: '';
        position: absolute;
        top: -50px;
        right: -50px;
        width: 150px;
        height: 150px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 50%;
    }

    /* Card Styling */
    .info-card {
        border: none;
        border-radius: 20px;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        background: #ffffff;
    }

    .info-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 30px rgba(0, 97, 255, 0.08) !important;
    }

    .icon-box {
        width: 40px;
        height: 40px;
        background-color: var(--soft-blue);
        color: var(--main-blue);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 15px;
    }

    /* Status Badge */
    .status-badge {
        padding: 6px 16px;
        border-radius: 50px;
        font-weight: 600;
        font-size: 0.75rem;
        letter-spacing: 0.5px;
    }

    /* Modal Styling */
    .modal-content {
        border: none;
        border-radius: 28px;
        overflow: hidden;
    }

    .modal-header {
        background: #fcfcfc;
        border-bottom: 1px solid #eee;
        padding: 1.5rem;
    }

    .form-label {
        font-weight: 600;
        color: #475569;
        font-size: 0.9rem;
    }

    .form-control,
    .form-select {
        border-radius: 12px;
        padding: 12px 15px;
        border: 1px solid #e2e8f0;
        background-color: #f8fafc;
    }

    .form-control:focus {
        background-color: #fff;
        box-shadow: 0 0 0 4px rgba(0, 97, 255, 0.1);
        border-color: var(--main-blue);
    }

    /* Button Custom */
    .btn-edit-profile {
        background: rgba(255, 255, 255, 0.2);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.3);
        color: white;
        transition: 0.3s;
    }

    .btn-edit-profile:hover {
        background: white;
        color: var(--main-blue);
    }
</style>

<div class="container py-5">
    <div class="profile-header p-5 mb-4 shadow-lg border-0">
        <div class="row align-items-center">
            <div class="col-md-8">
                <div class="d-flex align-items-center mb-2">
                    <span class="fs-1 me-3">🏪</span>
                    <h2 class="fw-bold mb-0"><?= htmlspecialchars($store['store_name']) ?></h2>
                </div>
                <p class="mb-0 opacity-75 fs-6"><i class="bi bi-calendar-check me-2"></i>เปิดร้านเมื่อ: <?= date('d M Y', strtotime($store['created_at'])) ?></p>
            </div>
            <div class="col-md-4 text-md-end mt-3 mt-md-0">
                <button class="btn btn-edit-profile px-4 py-2 rounded-pill fw-bold"
                    data-bs-toggle="modal"
                    data-bs-target="#editProfileModal">
                    <i class="bi bi-pencil-square me-2"></i> แก้ไขโปรไฟล์ร้าน
                </button>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card info-card shadow-sm h-100">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start mb-4">
                        <h5 class="fw-bold text-dark m-0">📍 ข้อมูลสถานที่ตั้ง</h5>
                        <span class="status-badge bg-<?= $store['status'] === 'active' ? 'success' : 'secondary' ?> text-white text-uppercase">
                            <?= $store['status'] ?>
                        </span>
                    </div>

                    <div class="mb-4">
                        <label class="text-muted small d-block mb-1 text-uppercase fw-bold">ที่อยู่ร้าน</label>
                        <div class="d-flex">
                            <div class="icon-box"><i class="bi bi-geo-alt-fill"></i></div>
                            <p class="mb-0 text-secondary fw-medium"><?= nl2br(htmlspecialchars($store['address'])) ?></p>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label class="text-muted small d-block mb-1 text-uppercase fw-bold">เบอร์โทรศัพท์ร้าน</label>
                            <div class="d-flex align-items-center">
                                <div class="icon-box"><i class="bi bi-telephone-fill"></i></div>
                                <span class="fw-bold text-dark"><?= htmlspecialchars($store['phone']) ?></span>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <label class="text-muted small d-block mb-1 text-uppercase fw-bold">เขตเวลา (Timezone)</label>
                            <div class="d-flex align-items-center">
                                <div class="icon-box"><i class="bi bi-globe-asia-australia"></i></div>
                                <span class="fw-medium text-secondary"><?= htmlspecialchars($store['timezone']) ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card info-card shadow-sm h-100 border-start border-primary border-4">
                <div class="card-body p-4">
                    <h5 class="fw-bold text-dark mb-4">👤 ข้อมูลเจ้าของร้าน</h5>

                    <div class="d-flex align-items-center mb-4 p-3 bg-light rounded-4">
                        <div class="flex-shrink-0">
                            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                <i class="bi bi-person-fill fs-4"></i>
                            </div>
                        </div>
                        <div class="ms-3">
                            <h6 class="mb-0 fw-bold"><?= htmlspecialchars($store['display_name']) ?></h6>
                            <small class="text-muted">เจ้าของร้าน (Owner)</small>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="text-muted small d-block mb-1 text-uppercase fw-bold">อีเมลติดต่อ</label>
                        <div class="d-flex align-items-center">
                            <i class="bi bi-envelope-at me-2 text-primary"></i>
                            <span class="text-dark fw-medium"><?= htmlspecialchars($store['email']) ?></span>
                        </div>
                    </div>

                    <div>
                        <label class="text-muted small d-block mb-1 text-uppercase fw-bold">เบอร์ส่วนตัว</label>
                        <div class="d-flex align-items-center">
                            <i class="bi bi-phone me-2 text-primary"></i>
                            <span class="text-dark fw-bold"><?= htmlspecialchars($store['owner_phone']) ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="editProfileModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <form method="post" action="menu/profile/edit.php">
                <div class="modal-header px-4 py-3">
                    <h5 class="modal-title fw-bold text-dark"><i class="bi bi-pencil-square me-2 text-primary"></i>แก้ไขข้อมูลโปรไฟล์ร้าน</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-4">
                        <label class="form-label">ชื่อร้านค้า <span class="text-danger">*</span></label>
                        <input type="text" name="store_name" class="form-control shadow-sm"
                            value="<?= htmlspecialchars($store['store_name']) ?>" required>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">ที่อยู่ร้านโดยละเอียด</label>
                        <textarea name="address" class="form-control shadow-sm" rows="3" placeholder="ระบุเลขที่ตั้ง อาคาร ถนน..."><?= htmlspecialchars($store['address']) ?></textarea>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">เบอร์โทรร้าน</label>
                            <input type="text" name="phone" class="form-control shadow-sm"
                                value="<?= htmlspecialchars($store['phone']) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">เขตเวลาพื้นฐาน</label>
                            <select name="timezone" class="form-select shadow-sm">
                                <option value="Asia/Bangkok" <?= $store['timezone'] == 'Asia/Bangkok' ? 'selected' : '' ?>>Asia/Bangkok (GMT+7)</option>
                                <option value="UTC" <?= $store['timezone'] == 'UTC' ? 'selected' : '' ?>>UTC (เวลามาตรฐานสากล)</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4">
                    <button type="button" class="btn btn-light px-4 rounded-pill" data-bs-dismiss="modal">ยกเลิก</button>
                    <button class="btn btn-primary px-5 rounded-pill shadow" style="background: var(--main-blue);">
                        <i class="bi bi-check-circle me-2"></i>บันทึกการเปลี่ยนแปลง
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="../bootstrap/js/bootstrap.bundle.js"></script>