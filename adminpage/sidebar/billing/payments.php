<?php
// PHP คงเดิมตามที่แจ้งไว้ครับ
$sql = "
    SELECT
        ss.id,
        s.name AS store_name,
        ss.plan,
        ss.monthly_fee,
        ss.status,
        ss.slip_image,
        ss.paid_at,
        ss.created_at
    FROM store_subscriptions ss
    JOIN stores s ON ss.store_id = s.id
    ORDER BY ss.created_at DESC
";
$rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
?>

<!doctype html>
<html lang="th">

<head>
    <meta charset="utf-8">
    <title>Payment History Management</title>
    <link rel="stylesheet" href="../../../bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../../bootstrap/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Anuphan:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --bg-body: #f1f5f9;
            --primary-gradient: linear-gradient(135deg, #6366f1 0%, #4338ca 100%);
            --glass: rgba(255, 255, 255, 0.8);
        }

        body {
            background-color: var(--bg-body);
            font-family: 'Anuphan', sans-serif;
            color: #1e293b;
        }

        /* Container & Header */
        .page-header {
            background: white;
            padding: 2rem;
            border-radius: 24px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        /* Table Design */
        .custom-card {
            border: none;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05);
        }

        .table {
            margin-bottom: 0;
        }

        .table thead th {
            background: #f8fafc;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.05em;
            font-weight: 700;
            color: #64748b;
            padding: 1.25rem 1rem;
            border-bottom: 1px solid #e2e8f0;
        }

        .table tbody td {
            padding: 1.25rem 1rem;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
        }

        .table-hover tbody tr:hover {
            background-color: #f8fafc;
            transition: all 0.2s ease;
        }

        /* Slip Thumbnail */
        .slip-wrapper {
            position: relative;
            width: 45px;
            height: 45px;
            cursor: pointer;
        }

        .slip-thumb {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 12px;
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            border: 2px solid white;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .slip-wrapper:hover .slip-thumb {
            transform: scale(1.5) rotate(-5deg);
            z-index: 10;
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.2);
        }

        /* Status Badges */
        .status-badge {
            padding: 6px 12px;
            border-radius: 10px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .bg-waiting_approve {
            background: #fff7ed;
            color: #c2410c;
            border: 1px solid #ffedd5;
        }

        .bg-active {
            background: #f0fdf4;
            color: #15803d;
            border: 1px solid #dcfce7;
        }

        .bg-rejected {
            background: #fef2f2;
            color: #b91c1c;
            border: 1px solid #fee2e2;
        }

        /* Waiting Approve Highlight */
        .row-waiting {
            background-color: #fffaf5 !important;
            border-left: 4px solid #f97316;
        }

        /* Animation for Waiting Row */
        @keyframes subtle-glow {
            0% {
                box-shadow: inset 0 0 10px rgba(249, 115, 22, 0.05);
            }

            50% {
                box-shadow: inset 0 0 20px rgba(249, 115, 22, 0.1);
            }

            100% {
                box-shadow: inset 0 0 10px rgba(249, 115, 22, 0.05);
            }
        }

        .row-waiting {
            animation: subtle-glow 2s infinite ease-in-out;
        }

        /* Modal Customization */
        .modal-blur {
            backdrop-filter: blur(8px);
            background: rgba(15, 23, 42, 0.7);
        }
    </style>
</head>

<body>

    <div class="container-fluid px-4 py-5">

        <div class="page-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <div class="bg-primary p-2 rounded-3 text-white shadow-sm">
                        <i class="bi bi-credit-card-2-back fs-4"></i>
                    </div>
                    <h2 class="fw-bold mb-0 ms-2">ประวัติการชำระเงิน</h2>
                </div>
                <p class="text-muted mb-0">ตรวจสอบและอนุมัติการสมัครแพ็กเกจของร้านค้าในระบบ</p>
            </div>
        </div>

        <div class="card custom-card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle" id="paymentTable">
                        <thead>
                            <tr>
                                <th class="ps-4">ร้านค้า</th>
                                <th>แพ็กเกจ</th>
                                <th>จำนวนเงิน</th>
                                <th>หลักฐานการโอน</th>
                                <th>วันที่ชำระ</th>
                                <th class="text-center">สถานะ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($rows)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5">
                                        <img src="https://cdn-icons-png.flaticon.com/512/6134/6134065.png" style="width: 80px; opacity: 0.3;" class="mb-3">
                                        <p class="text-muted">ไม่พบข้อมูลการสมัครแพ็กเกจในขณะนี้</p>
                                    </td>
                                </tr>
                            <?php endif; ?>

                            <?php foreach ($rows as $r):
                                $isWaiting = ($r['status'] === 'waiting_approve');
                            ?>
                                <tr class="<?= $isWaiting ? 'row-waiting' : '' ?>">

                                    <td class="ps-4">
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-info">
                                                <div class="fw-bold text-dark"><?= htmlspecialchars($r['store_name']) ?></div>
                                                <small class="text-muted text-uppercase" style="font-size: 10px;">ID: #<?= $r['id'] ?></small>
                                            </div>
                                        </div>
                                    </td>

                                    <td>
                                        <div class="d-flex flex-column">
                                            <span class="fw-semibold text-primary"><?= htmlspecialchars($r['plan']) ?></span>
                                        </div>
                                    </td>

                                    <td>
                                        <div class="fw-bold fs-6">
                                            <?= number_format($r['monthly_fee'], 2) ?>
                                            <span class="text-muted fw-normal" style="font-size: 12px;">฿</span>
                                        </div>
                                    </td>

                                    <td>
                                        <?php if ($r['slip_image']): ?>
                                            <div class="slip-wrapper">
                                                <img src="../../<?= htmlspecialchars($r['slip_image']) ?>"
                                                    class="slip-thumb img-trigger"
                                                    data-full="../../<?= htmlspecialchars($r['slip_image']) ?>"
                                                    alt="slip">
                                            </div>
                                        <?php else: ?>
                                            <span class="badge rounded-pill bg-light text-muted fw-normal">ไม่มีสลิป</span>
                                        <?php endif; ?>
                                    </td>

                                    <td class="text-muted">
                                        <div style="font-size: 0.9rem;">
                                            <i class="bi bi-calendar3 me-1"></i>
                                            <?= $r['paid_at'] ? date('d M Y', strtotime($r['paid_at'])) : '-' ?>
                                        </div>
                                        <div style="font-size: 0.75rem;">
                                            <i class="bi bi-clock me-1"></i>
                                            <?= $r['paid_at'] ? date('H:i', strtotime($r['paid_at'])) : '-' ?>
                                        </div>
                                    </td>

                                    <td class="text-center">
                                        <?php
                                        $statusIcon = match ($r['status']) {
                                            'active' => 'check-circle-fill',
                                            'waiting_approve' => 'hourglass-split',
                                            'rejected' => 'x-circle-fill',
                                            default => 'dash-circle'
                                        };
                                        ?>
                                        <div class="status-badge bg-<?= $r['status'] ?>">
                                            <i class="bi bi-<?= $statusIcon ?>"></i>
                                            <?= strtoupper(str_replace('_', ' ', $r['status'])) ?>
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

    <script>
        document.querySelectorAll('.img-trigger').forEach(img => {
            img.onclick = function() {
                const src = this.getAttribute('data-full');

                const overlay = document.createElement('div');
                overlay.className = 'modal-blur';
                overlay.style = `
            position: fixed; inset: 0; z-index: 9999;
            display: flex; align-items: center; justify-content: center;
            opacity: 0; transition: opacity 0.3s ease;
        `;

                overlay.innerHTML = `
            <div style="transform: scale(0.8); transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275); max-width: 90%; position: relative;">
                <img src="${src}" style="max-height: 85vh; border-radius: 20px; box-shadow: 0 25px 50px rgba(0,0,0,0.5); border: 5px solid white;">
                <button style="position: absolute; top: -20px; right: -20px; background: white; border: none; width: 40px; height: 40px; border-radius: 50%; font-weight: bold; cursor: pointer; box-shadow: 0 4px 10px rgba(0,0,0,0.3);">✕</button>
                <div class="text-white text-center mt-3 fw-bold">คลิกที่ว่างเพื่อปิด</div>
            </div>
        `;

                document.body.appendChild(overlay);

                // Trigger Animation
                setTimeout(() => {
                    overlay.style.opacity = '1';
                    overlay.querySelector('div').style.transform = 'scale(1)';
                }, 10);

                const close = () => {
                    overlay.style.opacity = '0';
                    overlay.querySelector('div').style.transform = 'scale(0.8)';
                    setTimeout(() => overlay.remove(), 300);
                };

                overlay.onclick = e => e.target === overlay || e.target.tagName === 'BUTTON' ? close() : null;
            }
        });
    </script>

    <script src="../../../bootstrap/js/bootstrap.bundle.min.js"></script>
</body>

</html>