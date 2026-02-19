<?php

require_once "../../ld_db.php";

// 1. อัปเดตโปรโมชั่นที่หมดเวลาแล้วให้เป็น inactive โดยอัตโนมัติ (ทำทุกครั้งที่มีคนเปิดหน้านี้)
try {
    $updateExpired = $pdo->prepare("
        UPDATE promotions 
        SET status = 'inactive' 
        WHERE status = 'active' 
        AND end_date < NOW()
    ");
    $updateExpired->execute();
} catch (PDOException $e) {
    // สามารถใส่ log error ได้ที่นี่
}

// 2. ดึงข้อมูลโปรโมชั่นล่าสุด
$stmt = $pdo->prepare("SELECT * FROM promotions ORDER BY created_at DESC");
$stmt->execute();
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ฟังก์ชันสำหรับแปลงชื่อ Audience ให้เป็นภาษาไทยและสี Badge (อิงตามหน้า Create)
function getAudienceBadge($audience)
{
    switch ($audience) {
        case 'stores':
            return ['text' => 'ทุกร้านค้า (Public)', 'class' => 'bg-info text-dark'];
        case 'store_specific':
            return ['text' => 'เฉพาะบางร้าน', 'class' => 'bg-warning text-dark'];
        default:
            return ['text' => $audience, 'class' => 'bg-light text-dark'];
    }
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Promotion Management - Platform Admin</title>

    <link href="../../bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">

    <style>
        body {
            font-family: 'Kanit', sans-serif;
            background-color: #f4f7fe;
            color: #334155;
        }

        .page-header {
            background: linear-gradient(135deg, #4361ee 0%, #7209b7 100%);
            padding: 60px 0 120px 0;
            color: white;
            position: relative;
        }

        .main-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            margin-top: -80px;
            border: none;
            padding: 30px;
        }

        .promo-thumb {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            object-fit: cover;
            border: 2px solid #fff;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        /* สถานะต่างๆ */
        .badge-active {
            background: #dcfce7;
            color: #15803d;
            border: 1px solid #bbf7d0;
        }

        .badge-draft {
            background: #f1f5f9;
            color: #475569;
            border: 1px solid #e2e8f0;
        }

        .badge-inactive {
            background: #fee2e2;
            color: #b91c1c;
            border: 1px solid #fecaca;
        }

        .badge-expired {
            background: #f8fafc;
            color: #94a3b8;
            border: 1px solid #e2e8f0;
        }

        .btn-action {
            width: 38px;
            height: 38px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            transition: 0.2s;
        }

        .hover-push:hover {
            transform: scale(1.05);
        }
    </style>
</head>

<body>

    <div class="page-header">
        <div class="container-fluid px-5">
            <div class="d-flex justify-content-between align-items-center" data-aos="fade-down">
                <div>
                    <h1 class="fw-bold mb-1"><i class="bi bi-megaphone me-2"></i> ศูนย์จัดการโปรโมชั่น</h1>
                    <p class="mb-0 opacity-75">สร้างและบริหารจัดการแคมเปญส่วนลดสำหรับร้านค้า</p>
                </div>
                <a href="../promotion/create.php" class="btn btn-light rounded-pill px-4 py-2 fw-bold shadow hover-push">
                    <i class="bi bi-plus-lg me-2 text-primary"></i> เพิ่มโปรโมชั่น
                </a>
            </div>
        </div>
    </div>

    <div class="container-fluid px-5 mb-5">
        <div class="main-card" data-aos="fade-up">
            <div class="table-responsive">
                <table id="promoTable" class="table align-middle" style="width:100%">
                    <thead>
                        <tr>
                            <th>รายละเอียดโปรโมชั่น</th>
                            <th>ส่วนลด</th>
                            <th>ระยะเวลา</th>
                            <th>กลุ่มเป้าหมาย</th>
                            <th>สถานะ</th>
                            <th class="text-end">เครื่องมือ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($result as $row):
                            $aud = getAudienceBadge($row['audience']);
                            $now = date('Y-m-d H:i:s');
                            $is_expired = ($row['end_date'] < $now);
                        ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <?php
                                        $imgPath = "../../" . $row['image'];
                                        if (!empty($row['image']) && file_exists($imgPath)):
                                        ?>
                                            <img src="<?= $imgPath ?>" class="promo-thumb me-3">
                                        <?php else: ?>
                                            <div class="promo-thumb me-3 bg-light d-flex align-items-center justify-content-center">
                                                <i class="bi bi-image text-muted"></i>
                                            </div>
                                        <?php endif; ?>
                                        <div>
                                            <div class="fw-bold"><?= htmlspecialchars($row['title']) ?></div>
                                            <div class="text-muted small"><?= htmlspecialchars($row['summary']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="fw-bold text-primary">
                                        <?= number_format($row['discount'], ($row['discount_type'] == 'percentage' ? 0 : 2)) ?>
                                        <?= $row['discount_type'] == 'percentage' ? '%' : '฿' ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="small">
                                        <div class="text-success"><i class="bi bi-calendar-check me-1"></i> <?= date('d/m/y H:i', strtotime($row['start_date'])) ?></div>
                                        <div class="text-danger"><i class="bi bi-calendar-x me-1"></i> <?= date('d/m/y H:i', strtotime($row['end_date'])) ?></div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge <?= $aud['class'] ?> fw-normal px-3">
                                        <?= $aud['text'] ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($is_expired && $row['status'] !== 'draft'): ?>
                                        <span class="badge badge-expired px-3 py-2 text-muted"><i class="bi bi-clock-history me-1"></i> หมดอายุ</span>
                                    <?php elseif ($row['status'] === 'active'): ?>
                                        <span class="badge badge-active px-3 py-2"><i class="bi bi-check-circle-fill me-1"></i> ออนไลน์</span>
                                    <?php elseif ($row['status'] === 'inactive'): ?>
                                        <span class="badge badge-inactive px-3 py-2"><i class="bi bi-x-circle-fill me-1"></i> ปิดใช้งาน</span>
                                    <?php else: ?>
                                        <span class="badge badge-draft px-3 py-2"><i class="bi bi-pencil-fill me-1"></i> ร่าง</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <a href="../promotion/edit.php?id=<?= $row['id'] ?>" class="btn-action btn btn-outline-primary me-1" title="แก้ไข">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <button type="button" class="btn-action btn btn-outline-danger btn-delete" data-id="<?= $row['id'] ?>" data-name="<?= htmlspecialchars($row['title']) ?>">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.js"></script>
    <script src="../../bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        $(document).ready(function() {
            AOS.init({
                duration: 800,
                once: true
            });

            $('#promoTable').DataTable({
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.13.6/i18n/th.json"
                },
                "order": [
                    [2, "desc"]
                ],
                "pageLength": 10
            });

            $(document).on('click', '.btn-delete', function() {
                const id = $(this).data('id');
                const name = $(this).data('name');
                Swal.fire({
                    title: 'ยืนยันการลบ?',
                    html: `คุณต้องการลบโปรโมชั่น <br><b>"${name}"</b> ใช่หรือไม่?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ef4444',
                    confirmButtonText: 'ยืนยันการลบ',
                    cancelButtonText: 'ยกเลิก',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = '../promotion/delete.php?id=' + id;
                    }
                });
            });
        });
    </script>
</body>

</html>