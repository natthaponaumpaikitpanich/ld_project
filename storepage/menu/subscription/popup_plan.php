<?php
// popup_plan.php
// ใช้ใน storepage/index.php

$store_id = $_SESSION['store_id'] ?? null;

/* ===== ตรวจสถานะ subscription ล่าสุดของร้าน ===== */
$sub = null;
if ($store_id) {
    $stmt = $pdo->prepare("
        SELECT status, slip_image, plan
        FROM store_subscriptions
        WHERE store_id = ?
        ORDER BY created_at DESC
        LIMIT 1
    ");
    $stmt->execute([$store_id]);
    $sub = $stmt->fetch(PDO::FETCH_ASSOC);
}

/* ===== ถ้าอยู่สถานะรออนุมัติ ===== */
if ($sub && $sub['status'] === 'waiting_approve'):
?>
<div id="subscription-overlay">
<div class="bg-white rounded-4 p-4 shadow text-center" style="width:460px">

    <h4 class="fw-bold mb-3">⏳ รอการตรวจสอบ</h4>

    <p class="text-muted">
        คุณได้ส่งสลิปสมัครแพ็กเกจ<br>
        <strong><?= htmlspecialchars($sub['plan']) ?></strong><br>
        เรียบร้อยแล้ว
    </p>

    <?php if (!empty($sub['slip_image'])): ?>
        <img src="../<?= htmlspecialchars($sub['slip_image']) ?>"
             style="width:180px;border-radius:8px;border:1px solid #eee"
             class="my-3">
    <?php endif; ?>

    <div class="alert alert-warning mb-0">
        ⛔ กรุณารอแอดมินตรวจสอบและอนุมัติ
    </div>

</div>
</div>
<?php
    return; // ❗ สำคัญ: ไม่ให้ form สมัครแสดง
endif;
?>

<?php
/* ===== ยังไม่สมัคร / pending ===== */
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

<h4 class="fw-bold text-center mb-3">🔒 สมัครแพ็กเกจ</h4>
<p class="text-muted text-center mb-4">
    ต้องสมัครแพ็กเกจก่อนจึงจะใช้งานระบบได้
</p>

<form method="post"
      action="menu/subscription/subscribe_action.php"
      enctype="multipart/form-data">

    <!-- เลือกแพ็กเกจ -->
    <div class="mb-3">
        <label class="form-label">เลือกแพ็กเกจ</label>
        <select class="form-select" name="plan_id" id="planSelect" required>
            <option value="">-- เลือกแพ็กเกจ --</option>
            <?php foreach ($plans as $p): ?>
                <option value="<?= $p['id'] ?>"
                        data-amount="<?= $p['amount'] ?>"
                        data-qr="<?= htmlspecialchars($p['qr_image']) ?>">
                    <?= htmlspecialchars($p['name']) ?>
                    (<?= number_format($p['price'],2) ?> ฿)
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- แสดงจำนวนเงิน + QR -->
    <div id="planInfo" class="text-center d-none">
        <div class="fw-bold text-danger mb-2">
            โอนเงิน <span id="planAmount"></span> ฿
        </div>

        <img id="planQR"
             src=""
             alt="QR Code"
             style="width:180px;border:1px solid #eee;padding:8px;border-radius:8px">
    </div>

    <!-- อัปโหลดสลิป -->
    <div id="slipBox" class="d-none mt-3">
        <label class="form-label">อัปโหลดสลิปการโอน</label>
        <input type="file"
               name="slip_image"
               class="form-control"
               accept="image/*"
               required>
    </div>

    <button class="btn btn-primary w-100 mt-4">
        ยืนยันเลือกแพ็กเกจ
    </button>

</form>

</div>
</div>

<script>
const select   = document.getElementById('planSelect');
const info     = document.getElementById('planInfo');
const qr       = document.getElementById('planQR');
const amount   = document.getElementById('planAmount');
const slipBox  = document.getElementById('slipBox');

select.addEventListener('change', () => {
    const opt = select.selectedOptions[0];

    if (!opt || !opt.value) {
        info.classList.add('d-none');
        slipBox.classList.add('d-none');
        qr.src = '';
        return;
    }

    amount.innerText = opt.dataset.amount;

    // 🔥 path ตามโครงสร้างจริง
    // qr_image = billing/uploads/xxx.jpg
    qr.src = '../adminpage/sidebar/' + opt.dataset.qr;

    info.classList.remove('d-none');
    slipBox.classList.remove('d-none');
});
</script>
