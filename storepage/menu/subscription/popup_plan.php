<?php
$stmt = $pdo->query("
    SELECT id, name, price, amount, qr_image
    FROM billing_plans
    WHERE status = 'active'
    ORDER BY price ASC
");
$plans = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div id="subscription-overlay">
    <div class="bg-white rounded-4 p-4 shadow" style="width:460px">

        <h4 class="fw-bold text-center mb-2">🔒 สมัครแพ็กเกจ</h4>
        <p class="text-center text-muted mb-4">
            กรุณาเลือกแพ็กเกจก่อนเริ่มใช้งานระบบ
        </p>

        <form method="post"
              action="menu/subscription/subscribe_action.php"
              enctype="multipart/form-data">

            <!-- เลือกแพ็กเกจ -->
            <div class="mb-3">
                <label class="form-label fw-semibold">แพ็กเกจ</label>
                <select class="form-select" name="plan_id" id="planSelect" required>
                    <option value="">-- เลือกแพ็กเกจ --</option>
                    <?php foreach ($plans as $p): ?>
                        <option
                            value="<?= $p['id'] ?>"
                            data-amount="<?= $p['amount'] ?>"
                            data-qr="<?= htmlspecialchars($p['qr_image']) ?>">
                            <?= htmlspecialchars($p['name']) ?>
                            (<?= number_format($p['price'],2) ?> ฿/เดือน)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- ข้อมูลแพ็กเกจ -->
            <div id="planInfo" class="d-none text-center mb-3">
                <div class="fw-bold text-danger mb-2">
                    💸 ยอดโอน <span id="planAmount"></span> ฿
                </div>

                <img id="planQR"
                     src=""
                     alt="QR Code"
                     style="width:180px"
                     class="border rounded p-2">
            </div>

            <!-- อัปโหลดสลิป -->
            <div id="slipBox" class="d-none mb-3">
                <label class="form-label fw-semibold">📤 อัปโหลดสลิปการโอน</label>
                <input type="file"
                       name="slip_image"
                       class="form-control"
                       accept="image/*"
                       required>
            </div>

            <button type="submit" class="btn btn-primary w-100">
                ยืนยันสมัครแพ็กเกจ
            </button>

        </form>

    </div>
</div>

<script>
const select  = document.getElementById('planSelect');
const info    = document.getElementById('planInfo');
const qrImg   = document.getElementById('planQR');
const amount  = document.getElementById('planAmount');
const slipBox = document.getElementById('slipBox');

select.addEventListener('change', () => {
    const opt = select.selectedOptions[0];

    if (!opt || !opt.value) {
        info.classList.add('d-none');
        slipBox.classList.add('d-none');
        qrImg.src = '';
        return;
    }

    amount.textContent = parseFloat(opt.dataset.amount).toLocaleString();
    qrImg.src = '../' + opt.dataset.qr;

    info.classList.remove('d-none');
    slipBox.classList.remove('d-none');
});
</script>
