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
    <link rel="icon" href="../../../image/3.jpg">
    </link>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="../../../bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="../../../bootstrap/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/style.css" rel="stylesheet">
    <title>แพ็กเกจรายเดือน</title>

    <div class="container-fluid px-4 mt-4">
        <!-- HEADER -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h3 class="fw-bold mb-0">💳 แพ็กเกจรายเดือน</h3>
                <small class="text-muted">
                    จัดการแพ็กเกจพรีเมียมสำหรับร้านค้าในระบบ
                </small>
            </div>

            <a href="billing/plan_create.php" class="btn btn-primary">
                <i class="bi bi-plus-circle me-1"></i> เพิ่มแพ็กเกจใหม่
            </a>
        </div>

        <!-- TABLE CARD -->
        <div class="card shadow-sm border-0">
            <div class="card-body p-0">

                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>แพ็กเกจ</th>
                            <th>ราคา</th>
                            <th>ยอดโอน</th>
                            <th>QR Code</th>
                            <th>สถานะ</th>
                            <th class="text-end" width="180">จัดการ</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (empty($plans)): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    ยังไม่มีแพ็กเกจในระบบ
                                </td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach ($plans as $p): ?>
                            <tr>

                                <!-- NAME -->
                                <td class="fw-semibold">
                                    <?= htmlspecialchars($p['name']) ?>
                                </td>

                                <!-- PRICE -->
                                <td>
                                    <span class="fw-bold text-primary">
                                        <?= number_format($p['price'], 2) ?>
                                    </span>
                                    <small class="text-muted">฿ / เดือน</small>
                                </td>

                                <!-- TRANSFER -->
                                <td>
                                    <span class="fw-bold text-danger">
                                        <?= number_format($p['amount'], 2) ?> ฿
                                    </span>
                                </td>

                                <!-- QR -->
                                <td>
                                    <?php if ($p['qr_image']): ?>
                                        <img src="/ld_project/adminpage/sidebar/<?= htmlspecialchars($p['qr_image']) ?>"
     class="qr-thumb"
     data-src="/ld_project/adminpage/sidebar/<?= htmlspecialchars($p['qr_image']) ?>"
     alt="QR Code"
     style="width:80px;height:80px;object-fit:contain;cursor:pointer;">

                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>

                                <!-- STATUS -->
                                <td>
                                    <span class="badge bg-<?=
                                                            $p['status'] === 'active' ? 'success' : 'secondary'
                                                            ?>">
                                        <?= htmlspecialchars($p['status']) ?>
                                    </span>
                                </td>

                                <!-- ACTION -->
                                <td class="text-end">
                                    <a href="billing/plan_edit.php?id=<?= $p['id'] ?>"
                                        class="btn btn-sm btn-outline-warning">
                                        แก้ไข
                                    </a>

                                    <a href="billing/plan_delete.php?id=<?= $p['id'] ?>"
                                        class="btn btn-sm btn-outline-danger"
                                        onclick="return confirm('ต้องการปิดการใช้งานแพ็กเกจนี้จริงหรือไม่?')">
                                        ปิดใช้
                                    </a>
                                </td>

                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

            </div>
        </div>
        <div id="qrModal" style="
    display:none;
    position:fixed;
    inset:0;
    background:rgba(0,0,0,.65);
    z-index:9999;
    align-items:center;
    justify-content:center;
">
            <div style="
        background:#fff;
        padding:20px;
        border-radius:12px;
        max-width:90%;
        text-align:center;
        position:relative;
    ">
                <img id="qrModalImg"
                    src=""
                    style="max-width:320px;width:100%;height:auto;">
                <div class="mt-3">
                    <button class="btn btn-secondary btn-sm" onclick="closeQrModal()">
                        ปิด
                    </button>
                </div>
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
            document.getElementById('qrModalImg').src = '';
        }

        // คลิกพื้นหลังเพื่อปิด
        document.getElementById('qrModal').addEventListener('click', e => {
            if (e.target.id === 'qrModal') {
                closeQrModal();
            }
        });
    </script>


    </body>

</html>