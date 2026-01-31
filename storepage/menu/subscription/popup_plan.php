<?php
// popup_plan.php

$store_id = $_SESSION['store_id'] ?? null;

/* ===== ตรวจ subscription ล่าสุด ===== */
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
/* ===== รออนุมัติ ===== */
if ($sub && $sub['status'] === 'waiting_approve'):
?>
<div id="subscription-overlay">
<div class="sub-card">

    <h4 class="fw-bold mb-3">⏳ รอการตรวจสอบ</h4>

    <p class="text-muted">
        คุณได้สมัครแพ็กเกจ<br>
        <strong><?= htmlspecialchars($sub['plan']) ?></strong><br>
        เรียบร้อยแล้ว
    </p>

    <?php if (!empty($sub['slip_image'])): ?>
        <img src="../<?= htmlspecialchars($sub['slip_image']) ?>"
             class="slip-preview my-3">
    <?php endif; ?>

    <div class="alert alert-warning mb-0">
        ⛔ กรุณารอแอดมินตรวจสอบและอนุมัติ
    </div>

</div>
</div>
<?php
    return;
endif;
?>

<?php
/* ===== แพ็กเกจ ===== */
$stmt = $pdo->query("
    SELECT id, name, price, amount, qr_image
    FROM billing_plans
    WHERE status = 'active'
    ORDER BY price ASC
");
$plans = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div id="subscription-overlay">
<div class="sub-card">

<h4 class="fw-bold text-center mb-2">🔒 สมัครแพ็กเกจ</h4>
<p class="text-muted text-center mb-4">
    ต้องสมัครแพ็กเกจก่อนจึงจะใช้งานระบบได้
</p>

<form method="post"
      action="menu/subscription/subscribe_action.php"
      enctype="multipart/form-data"
      id="subForm">

<!-- PLAN -->
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

<!-- INFO -->
<div id="planInfo" class="text-center d-none">
    <div class="amount-box mb-2">
        โอนเงิน <span id="planAmount"></span> บาท
    </div>

    <img id="planQR" class="qr-img" src="">
</div>

<!-- SLIP -->
<div id="slipBox" class="d-none mt-3">
    <label class="form-label">อัปโหลดสลิปการโอน</label>
    <input type="file"
           name="slip_image"
           class="form-control"
           accept="image/*"
           required>
</div>

<button class="btn btn-main w-100 mt-4" id="submitBtn">
    ยืนยันสมัครแพ็กเกจ
</button>

</form>

</div>
</div>

<style>
    
/* OVERLAY */
#subscription-overlay{
    position:fixed;
    inset:0;
    background:rgba(15,23,42,.75);
    display:flex;
    align-items:center;
    justify-content:center;
    z-index:99999;
}


/* CARD */
.sub-card{
    background:#fff;
    width:100%;
    max-width:460px;
    border-radius:22px;
    padding:28px;
    text-align:center;
    box-shadow:0 30px 60px rgba(0,0,0,.25);
}

/* QR */
.qr-img{
    width:180px;
    border-radius:14px;
    border:1px solid #eee;
    padding:8px;
}

/* SLIP PREVIEW */
.slip-preview{
    width:180px;
    border-radius:12px;
    border:1px solid #eee;
}

/* AMOUNT */
.amount-box{
    font-size:1.1rem;
    font-weight:600;
    color:#1e3c72;
}

/* BUTTON */
.btn-main{
    background:linear-gradient(135deg,#1e3c72,#2a5298);
    color:#fff;
    border-radius:14px;
    font-weight:600;
}
.btn-main:hover{
    opacity:.9;
}
</style>

<script>
const select   = document.getElementById('planSelect');
const info     = document.getElementById('planInfo');
const qr       = document.getElementById('planQR');
const amount   = document.getElementById('planAmount');
const slipBox  = document.getElementById('slipBox');
const btn      = document.getElementById('submitBtn');

select.addEventListener('change', () => {
    const opt = select.selectedOptions[0];

    if (!opt || !opt.value) {
        info.classList.add('d-none');
        slipBox.classList.add('d-none');
        qr.src = '';
        return;
    }

    amount.innerText = opt.dataset.amount;

    // path ตามระบบจริง
    qr.src = '../adminpage/sidebar/' + opt.dataset.qr;

    info.classList.remove('d-none');
    slipBox.classList.remove('d-none');
});

document.getElementById('subForm').addEventListener('submit',()=>{
    btn.innerText = 'กำลังส่งข้อมูล...';
    btn.disabled = true;
});
</script>
