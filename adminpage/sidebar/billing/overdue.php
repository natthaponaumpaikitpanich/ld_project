<?php
require_once "../../ld_db.php";

$sql = "
SELECT
    ss.id,
    ss.store_id,
    ss.monthly_fee,
    ss.slip_image,
    ss.created_at,

    s.name AS store_name,

    bp.name AS plan_name,
    bp.price

FROM store_subscriptions ss
JOIN stores s ON ss.store_id = s.id
JOIN billing_plans bp ON ss.plan_id = bp.id
WHERE ss.status = 'waiting_approve'
ORDER BY ss.created_at DESC
";

$rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../../../bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../../bootstrap/bootstrap-icons.css">
    <link href="../../assets/style.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Kanit:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <link rel="icon" href="../../../image/3.jpg">
</head>
<body>
<div class="container-fluid px-4 mt-4">

    <!-- HEADER -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h3 class="fw-bold mb-0">🧾 ร้านที่รออนุมัติการชำระเงิน</h3>
            <small class="text-muted">
                ตรวจสอบสลิปและอนุมัติการสมัครแพ็กเกจของร้านค้า
            </small>
        </div>
    </div>

    <!-- CARD -->
    <div class="card shadow-sm border-0">
        <div class="card-body p-0">

            <table class="table table-hover align-middle mb-0" id="approveTable">
                <thead class="table-light">
                    <tr>
                        <th>ร้านค้า</th>
                        <th>แพ็กเกจ</th>
                        <th>ราคา</th>
                        <th>สลิป</th>
                        <th class="text-end" width="220">จัดการ</th>
                    </tr>
                </thead>

                <tbody>
                <?php if (!$rows): ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">
                            🎉 ไม่มีรายการรออนุมัติ
                        </td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($rows as $r): ?>
                <tr>

                    <!-- STORE -->
                    <td class="fw-semibold">
                        <?= htmlspecialchars($r['store_name']) ?>
                    </td>

                    <!-- PLAN -->
                    <td>
                        <span class="badge bg-primary">
                            <?= htmlspecialchars($r['plan_name']) ?>
                        </span>
                    </td>

                    <!-- PRICE -->
                    <td>
                        <span class="fw-bold text-success">
                            <?= number_format($r['price'],2) ?> ฿
                        </span>
                    </td>

                    <!-- SLIP -->
                    <td>
                        <?php if ($r['slip_image']): ?>
                            <img src="../../<?= htmlspecialchars($r['slip_image']) ?>"
                                 class="slip-thumb"
                                 data-img="../../<?= htmlspecialchars($r['slip_image']) ?>"
                                 alt="slip">
                        <?php else: ?>
                            <span class="text-muted">-</span>
                        <?php endif; ?>
                    </td>

                    <!-- ACTION -->
                    <td class="text-end">

                        <form method="post"
                              action="billing/approve_action.php"
                              class="d-inline approve-form">
                            <input type="hidden" name="id" value="<?= $r['id'] ?>">
                            <input type="hidden" name="action" value="approve">
                            <button class="btn btn-sm btn-success">
                                <i class="bi bi-check-circle me-1"></i> Approve
                            </button>
                        </form>

                        <form method="post"
                              action="billing/approve_action.php"
                              class="d-inline reject-form">
                            <input type="hidden" name="id" value="<?= $r['id'] ?>">
                            <input type="hidden" name="action" value="reject">
                            <button class="btn btn-sm btn-outline-danger">
                                <i class="bi bi-x-circle me-1"></i> Reject
                            </button>
                        </form>

                    </td>

                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

        </div>
    </div>

</div>
<script>
document.querySelectorAll('.slip-thumb').forEach(img => {
    img.addEventListener('click', () => {
        const src = img.dataset.img;

        const modal = document.createElement('div');
        modal.style = `
            position:fixed;
            inset:0;
            background:rgba(0,0,0,.6);
            display:flex;
            align-items:center;
            justify-content:center;
            z-index:9999;
        `;

        modal.innerHTML = `
            <div style="background:#fff;padding:20px;border-radius:12px;max-width:90%">
                <img src="${src}" style="max-width:520px;display:block;margin:auto">
                <div class="text-center mt-3">
                    <button class="btn btn-secondary btn-sm">ปิด</button>
                </div>
            </div>
        `;

        modal.querySelector('button').onclick = () => modal.remove();
        modal.onclick = e => e.target === modal && modal.remove();

        document.body.appendChild(modal);
    });
});
</script>
<script>
document.querySelectorAll('#approveTable tbody tr').forEach(row => {
    row.style.background = '#fff7ed';
});
</script>
</body>
</html>