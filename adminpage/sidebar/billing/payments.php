<?php
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
    <title>การสมัครแพ็กเกจร้านค้า</title>
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
            <h3 class="fw-bold mb-0">💳 ประวัติการสมัครแพ็กเกจ</h3>
            <small class="text-muted">
                การชำระเงินและสถานะการสมัครแพ็กเกจของร้านค้าในระบบ
            </small>
        </div>
    </div>

    <!-- TABLE CARD -->
    <div class="card shadow-sm border-0">
        <div class="card-body p-0">

            <table class="table table-hover align-middle mb-0" id="paymentTable">
                <thead class="table-light">
                    <tr>
                        <th>ร้านค้า</th>
                        <th>แพ็กเกจ</th>
                        <th>จำนวนเงิน</th>
                        <th>สลิป</th>
                        <th>วันที่ชำระ</th>
                        <th>สถานะ</th>
                    </tr>
                </thead>

                <tbody>
                <?php if (empty($rows)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">
                            ยังไม่มีข้อมูลการสมัครแพ็กเกจ
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
                            <?= htmlspecialchars($r['plan']) ?>
                        </span>
                    </td>

                    <!-- AMOUNT -->
                    <td>
                        <span class="fw-bold text-success">
                            <?= number_format($r['monthly_fee'], 2) ?> ฿
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

                    <!-- DATE -->
                    <td>
                        <?= $r['paid_at']
                            ? date('d/m/Y H:i', strtotime($r['paid_at']))
                            : '<span class="text-muted">-</span>' ?>
                    </td>

                    <!-- STATUS -->
                    <td>
                        <?php
                        $badge = match ($r['status']) {
                            'active'          => 'success',
                            'waiting_approve' => 'warning',
                            'rejected'        => 'danger',
                            default           => 'secondary'
                        };
                        ?>
                        <span class="badge bg-<?= $badge ?>">
                            <?= htmlspecialchars($r['status']) ?>
                        </span>
                    </td>

                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

        </div>
    </div>

</div>
<script>
document.querySelectorAll('#paymentTable tbody tr').forEach(row => {
    if (row.innerText.includes('waiting_approve')) {
        row.style.background = '#fff7ed';
    }
});
</script>
<script src="../../../bootstrap/js/bootstrap.bundle.min.js"></script>
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
                <img src="${src}" style="max-width:500px;display:block;margin:auto">
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

</body>
</html>
