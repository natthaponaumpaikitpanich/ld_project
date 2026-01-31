<?php
require_once "../../ld_db.php";

/** * LOGIC: ดึงร้านค้าที่ Subscription หมดอายุแล้ว หรือสถานะเป็น 'unpaid' 
 * ผมสมมติโครงสร้างว่าคุณมีฟิลด์ 'ends_at' ในการเช็ควันหมดอายุ
 */
$sql = "
    SELECT 
        ss.id, 
        ss.store_id, 
        ss.monthly_fee, 
        ss.slip_image, 
        ss.created_at,
        ss.status,
        s.name AS store_name,
        bp.name AS plan_name,
        bp.price,
        DATEDIFF(NOW(), ss.created_at) as days_overdue -- ตัวอย่างการนับวันที่ค้าง
    FROM store_subscriptions ss
    JOIN stores s ON ss.store_id = s.id
    JOIN billing_plans bp ON ss.plan_id = bp.id
    WHERE ss.status = 'waiting_approve' 
       OR ss.status = 'unpaid' 
       OR ss.status = 'expired'
    ORDER BY ss.created_at ASC
";

$rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Overdue Management</title>
    <link rel="stylesheet" href="../../../bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../../bootstrap/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://fonts.googleapis.com/css2?family=Anuphan:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --warning-soft: #fff7ed;
            --danger-soft: #fef2f2;
            --accent:linear-gradient(135deg, #6366f1 0%, #4338ca 100%);
        }

        body {
            background-color: #f8fafc;
            font-family: 'Anuphan', sans-serif;
        }

        /* Header Style */
        .page-title-box {
            background: white;
            padding: 1.5rem;
            border-radius: 16px;
            border-left: 6px solid var(--accent);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }

        /* Table Style */
        .table-container {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.04);
        }

        .table thead th {
            background: #f1f5f9;
            color: #475569;
            text-transform: uppercase;
            font-size: 0.75rem;
            font-weight: 700;
            padding: 1rem;
            border: none;
        }

        /* Row Status Color */
        .row-urgent {
            background-color: var(--danger-soft) !important;
        }

        .row-pending {
            background-color: var(--warning-soft) !important;
        }

        /* Slip Thumbnail */
        .slip-thumb {
            width: 45px;
            height: 45px;
            object-fit: cover;
            border-radius: 10px;
            cursor: pointer;
            transition: 0.3s;
            border: 2px solid #fff;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .slip-thumb:hover {
            transform: scale(1.1);
            z-index: 5;
        }

        /* Action Buttons */
        .btn-action {
            padding: 0.5rem 1rem;
            border-radius: 10px;
            font-weight: 600;
            transition: 0.2s;
        }

        .btn-approve {
            background: #10b981;
            color: white;
            border: none;
        }

        .btn-approve:hover {
            background: #059669;
            transform: translateY(-2px);
        }

        .btn-reject {
            background: white;
            color: #ef4444;
            border: 1px solid #fee2e2;
        }

        .btn-reject:hover {
            background: #fef2f2;
        }
    </style>
</head>

<body>

    <div class="container-fluid px-4 py-4">

        <div class="page-title-box d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold mb-1 text-dark"><i class="bi bi-clock-history me-2 text-primary"></i> รายการค้างชำระ </h3>
                <p class="text-muted mb-0">ระบบกรองร้านค้าที่เกินกำหนดชำระเงินและรายการแจ้งโอนใหม่</p>
            </div>
            <div class="text-end">
                <span class="badge bg-danger rounded-pill px-3 py-2">
                    ทั้งหมด <?= count($rows) ?> รายการ
                </span>
            </div>
        </div>

        <div class="table-container shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="approveTable">
                    <thead>
                        <tr>
                            <th class="ps-4">ร้านค้า / ข้อมูลแผน</th>
                            <th>ประเภท</th>
                            <th>ราคาที่ต้องชำระ</th>
                            <th>หลักฐาน</th>
                            <th class="text-center">สถานะปัจจุบัน</th>
                            <th class="text-end pe-4">ดำเนินการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$rows): ?>
                            <tr>
                                <td colspan="6" class="text-center py-5">
                                    <div class="py-4">
                                        <i class="bi bi-check2-circle display-1 text-success opacity-25"></i>
                                        <h5 class="mt-3 text-muted">เยี่ยมเลย! ไม่มีร้านค้าค้างชำระในขณะนี้</h5>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach ($rows as $r):
                            $statusClass = ($r['status'] == 'expired') ? 'row-urgent' : 'row-pending';
                        ?>
                            <tr class="<?= $statusClass ?>">

                                <td class="ps-4">
                                    <div class="fw-bold text-dark fs-6"><?= htmlspecialchars($r['store_name']) ?></div>
                                    <div class="text-muted small">สมัครเมื่อ: <?= date('d/m/Y', strtotime($r['created_at'])) ?></div>
                                </td>

                                <td>
                                    <span class="badge rounded-pill bg-white text-primary border border-primary px-3">
                                        <i class="bi bi-box-seam me-1"></i> <?= htmlspecialchars($r['plan_name']) ?>
                                    </span>
                                </td>

                                <td>
                                    <div class="fw-bold text-success fs-5">
                                        <?= number_format($r['price'], 2) ?> ฿
                                    </div>
                                </td>

                                <td>
                                    <?php if ($r['slip_image']): ?>
                                        <div class="d-flex align-items-center">
                                            <img src="../../<?= htmlspecialchars($r['slip_image']) ?>"
                                                class="slip-thumb view-slip"
                                                data-img="../../<?= htmlspecialchars($r['slip_image']) ?>"
                                                alt="slip">
                                            <small class="ms-2 text-primary" style="font-size: 10px;">คลิกเพื่อดู</small>
                                        </div>
                                    <?php else: ?>
                                        <span class="badge bg-light text-danger fw-normal">
                                            <i class="bi bi-exclamation-triangle me-1"></i> ยังไม่แนบสลิป
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <td class="text-center">
                                    <?php if ($r['status'] == 'waiting_approve'): ?>
                                        <span class="text-warning fw-bold"><i class="bi bi-hourglass-split"></i> รอตรวจสอบ</span>
                                    <?php elseif ($r['status'] == 'expired'): ?>
                                        <span class="text-danger fw-bold"><i class="bi bi-exclamation-circle"></i> เกินกำหนด</span>
                                    <?php else: ?>
                                        <span class="text-secondary"><?= $r['status'] ?></span>
                                    <?php endif; ?>
                                </td>

                                <td class="text-end pe-4">
                                    <div class="btn-group gap-2">
                                        <button onclick="handleAction(<?= $r['id'] ?>, 'approve', '<?= $r['store_name'] ?>')"
                                            class="btn btn-action btn-approve shadow-sm">
                                            <i class="bi bi-check-lg"></i> อนุมัติ
                                        </button>
                                        <button onclick="handleAction(<?= $r['id'] ?>, 'reject', '<?= $r['store_name'] ?>')"
                                            class="btn btn-action btn-reject">
                                            <i class="bi bi-trash"></i> ปฏิเสธ
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // 1. ฟังก์ชันอนุมัติ/ปฏิเสธ (ใช้ SweetAlert2)
        function handleAction(id, action, storeName) {
            const isApprove = action === 'approve';

            Swal.fire({
                title: isApprove ? 'ยืนยันการอนุมัติ?' : 'ยืนยันการปฏิเสธ?',
                text: `${isApprove ? 'เปิดใช้งานแพ็กเกจให้' : 'ปฏิเสธรายการของ'} ร้าน ${storeName}`,
                icon: isApprove ? 'question' : 'warning',
                showCancelButton: true,
                confirmButtonColor: isApprove ? '#10b981' : '#ef4444',
                cancelButtonColor: '#64748b',
                confirmButtonText: isApprove ? 'ใช่, อนุมัติเลย!' : 'ใช่, ปฏิเสธรายการ',
                cancelButtonText: 'ยกเลิก'
            }).then((result) => {
                if (result.isConfirmed) {
                    // สร้าง Form จำลองเพื่อส่งค่า
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = 'billing/approve_action.php';

                    const fields = {
                        id,
                        action
                    };
                    for (const key in fields) {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = key;
                        input.value = fields[key];
                        form.appendChild(input);
                    }

                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }

        // 2. ฟังก์ชันดูรูปสลิปแบบ Fullscreen (Custom Modal)
        document.querySelectorAll('.view-slip').forEach(img => {
            img.addEventListener('click', () => {
                Swal.fire({
                    imageUrl: img.dataset.img,
                    imageAlt: 'Slip Image',
                    showCloseButton: true,
                    showConfirmButton: false,
                    background: 'rgba(255,255,255,0.9)',
                    backdrop: `rgba(0,0,0,0.8) blur(4px)`
                });
            });
        });
    </script>

</body>

</html>