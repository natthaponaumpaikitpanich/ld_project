<?php
$stmt = $pdo->query("
    SELECT *
    FROM billing_plans
    ORDER BY price ASC
");
$plans = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="../../../image/3.jpg">
    <title>จัดการแพ็กเกจรายเดือน</title>

    <link href="../../../bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="../../../bootstrap/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Anuphan:wght@300;400;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary-color: #4361ee;
            --accent-color: #4cc9f0;
            --bg-body: #f8f9fc;
            --glass-bg: rgba(255, 255, 255, 0.9);
        }

        body {
            background-color: var(--bg-body);
            font-family: 'Anuphan', sans-serif;
            color: #2b2d42;
        }

        /* Header Style */
        .page-header {
            background: linear-gradient(135deg, #4361ee 0%, #3a0ca3 100%);
            padding: 40px 0 100px 0;
            color: white;
            margin-bottom: -60px;
        }

        /* Card Customization */
        .main-card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
        }

        /* Table Styling */
        .table thead {
            background-color: #f1f4ff;
        }

        .table thead th {
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
            color: #4a5568;
            padding: 20px;
            border: none;
        }

        .table tbody tr {
            transition: all 0.3s ease;
        }

        .table tbody tr:hover {
            background-color: #f8faff !important;
            transform: scale(1.002);
        }

        .table td {
            padding: 20px;
            vertical-align: middle;
            border-bottom: 1px solid #edf2f7;
        }

        /* Badge Customization */
        .status-badge {
            padding: 6px 14px;
            border-radius: 50px;
            font-weight: 500;
            font-size: 0.75rem;
        }

        /* QR Code Thumbnail */
        .qr-wrapper {
            position: relative;
            display: inline-block;
            transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        .qr-wrapper:hover {
            transform: scale(1.1) rotate(2deg);
        }

        .qr-thumb {
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            border: 2px solid white;
        }

        /* Button Customization */
        .btn-add {
            background: #fff;
            color: var(--primary-color);
            border: none;
            border-radius: 12px;
            padding: 10px 24px;
            font-weight: 600;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: all 0.3s;
        }

        .btn-add:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
            background: #f8f9fa;
        }

        .action-btn {
            border-radius: 8px;
            padding: 6px 12px;
            transition: all 0.2s;
        }

        /* Modal Custom */
        #qrModal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.8);
            backdrop-filter: blur(8px);
            z-index: 10000;
            align-items: center;
            justify-content: center;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        .modal-content-custom {
            background: white;
            padding: 30px;
            border-radius: 24px;
            text-align: center;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }
    </style>
</head>

<body>

    <div class="page-header">
        <div class="container-fluid px-5">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="fw-bold mb-1">💳 การจัดการแพ็กเกจ</h2>
                    <p class="opacity-75 mb-0">ปรับแต่งและตรวจสอบแผนพรีเมียมของระบบคุณ</p>
                </div>
                <a href="billing/plan_create.php" class="btn btn-add">
                    <i class="bi bi-plus-lg me-2"></i> สร้างแพ็กเกจใหม่
                </a>
            </div>
        </div>
    </div>

    <div class="container-fluid px-5 pb-5">
        <div class="card main-card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>ชื่อแพ็กเกจ</th>
                                <th>ราคา / เดือน</th>
                                <th>ยอดที่ต้องโอน</th>
                                <th>QR Code</th>
                                <th>สถานะการใช้งาน</th>
                                <th class="text-center">เครื่องมือ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($plans)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <i class="bi bi-inbox fs-1 d-block mb-3"></i>
                                        ไม่พบข้อมูลแพ็กเกจในขณะนี้
                                    </td>
                                </tr>
                            <?php endif; ?>

                            <?php foreach ($plans as $p): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold text-dark fs-5"><?= htmlspecialchars($p['name']) ?></div>
                                        <small class="text-muted">Subscription Plan</small>
                                    </td>
                                    <td>
                                        <span class="fs-5 fw-bold text-primary">฿<?= number_format($p['price'], 2) ?></span>
                                    </td>
                                    <td>
                                        <div class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-3">
                                            <?= number_format($p['amount'], 2) ?> ฿
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($p['qr_image']): ?>
                                            <div class="qr-wrapper">
                                                <img src="/ld_project/adminpage/sidebar/<?= htmlspecialchars($p['qr_image']) ?>"
                                                    class="qr-thumb"
                                                    data-src="/ld_project/adminpage/sidebar/<?= htmlspecialchars($p['qr_image']) ?>"
                                                    alt="QR Code"
                                                    style="width:65px;height:65px;object-fit:cover;cursor:pointer;">
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted small">ไม่มีภาพ</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($p['status'] === 'active'): ?>
                                            <span class="status-badge bg-success-subtle text-success">
                                                <i class="bi bi-check-circle-fill me-1"></i> Active
                                            </span>
                                        <?php else: ?>
                                            <span class="status-badge bg-secondary-subtle text-secondary">
                                                <i class="bi bi-pause-circle-fill me-1"></i> Inactive
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group">
                                            <a href="billing/plan_edit.php?id=<?= $p['id'] ?>"
                                                class="btn btn-outline-warning action-btn me-2 border-0 bg-warning-subtle">
                                                <i class="bi bi-pencil-square"></i> แก้ไข
                                            </a>
                                            <a href="billing/plan_delete.php?id=<?= $p['id'] ?>"
                                                class="btn btn-outline-danger action-btn border-0 bg-danger-subtle"
                                                onclick="return confirm('ยืนยันการปิดใช้งานแพ็กเกจนี้?')">
                                                <i class="bi bi-trash3"></i> ปิดใช้
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div id="qrModal">
        <div class="modal-content-custom">
            <h5 class="mb-3 fw-bold">ตรวจสอบ QR Code</h5>
            <img id="qrModalImg" src="" style="max-width:300px; border-radius:15px; margin-bottom: 20px;">
            <div>
                <button class="btn btn-dark px-4 rounded-pill" onclick="closeQrModal()">ปิดหน้าต่าง</button>
            </div>
        </div>
    </div>

    <script>
        document.querySelectorAll('.qr-thumb').forEach(img => {
            img.addEventListener('click', () => {
                document.getElementById('qrModalImg').src = img.dataset.src;
                document.getElementById('qrModal').style.display = 'flex';
            });
        });

        function closeQrModal() {
            document.getElementById('qrModal').style.display = 'none';
        }

        window.onclick = function(event) {
            if (event.target == document.getElementById('qrModal')) {
                closeQrModal();
            }
        }
    </script>
</body>

</html>