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
?>

<style>
    /* ----- BASE OVERLAY ----- */
    #subscription-overlay {
        position: fixed;
        inset: 0;
        background: radial-gradient(circle at center, rgba(30, 41, 59, 0.7) 0%, rgba(15, 23, 42, 0.9) 100%);
        backdrop-filter: blur(12px);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 99999;
        padding: 20px;
        font-family: 'Kanit', sans-serif;
    }

    /* ----- PREMIUM CARD ----- */
    .sub-card {
        background: rgba(255, 255, 255, 0.95);
        width: 100%;
        max-width: 500px;
        border-radius: 40px;
        padding: 45px;
        text-align: center;
        box-shadow: 0 40px 100px -20px rgba(0, 0, 0, 0.5), inset 0 0 0 1px rgba(255, 255, 255, 0.5);
        animation: cardAppear 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
        position: relative;
        overflow: hidden;
    }

    @keyframes cardAppear {
        from {
            opacity: 0;
            transform: translateY(40px) scale(0.9);
        }

        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    /* ----- WAITING APPROVE UI (THE "ALANGKAR" PART) ----- */
    .status-ocean {
        position: relative;
        width: 140px;
        height: 140px;
        margin: 0 auto 30px;
    }

    .wave-circle {
        position: absolute;
        width: 100%;
        height: 100%;
        background: #3b82f6;
        border-radius: 50%;
        opacity: 0.15;
        animation: wave-ping 3s infinite;
    }

    .wave-circle:nth-child(2) {
        animation-delay: 1s;
    }

    .icon-box-3d {
        width: 100px;
        height: 100px;
        background: linear-gradient(135deg, #3b82f6, #2563eb);
        border-radius: 30px;
        margin: 20px auto;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 3rem;
        color: white;
        box-shadow: 0 20px 40px -10px rgba(37, 99, 235, 0.5);
        transform: rotate(-5deg);
        animation: float 4s ease-in-out infinite;
        position: relative;
        z-index: 5;
    }

    @keyframes float {

        0%,
        100% {
            transform: translateY(0) rotate(-5deg);
        }

        50% {
            transform: translateY(-15px) rotate(5deg);
        }
    }

    @keyframes wave-ping {
        0% {
            transform: scale(1);
            opacity: 0.2;
        }

        100% {
            transform: scale(1.8);
            opacity: 0;
        }
    }

    .plan-badge {
        background: #f1f5f9;
        border: 1px solid #e2e8f0;
        padding: 8px 20px;
        border-radius: 100px;
        font-weight: 600;
        color: #475569;
        display: inline-block;
        margin-bottom: 15px;
    }

    .slip-container-premium {
        perspective: 1000px;
        margin: 25px 0;
    }

    .slip-preview-premium {
        width: 160px;
        border-radius: 20px;
        transform: rotateY(-10deg) rotateX(5deg);
        box-shadow: 20px 20px 50px rgba(0, 0, 0, 0.15);
        transition: 0.5s;
        border: 4px solid white;
    }

    .slip-preview-premium:hover {
        transform: rotateY(0) rotateX(0) scale(1.1);
    }

    /* ----- FORMS & INPUTS ----- */
    .form-label {
        font-weight: 600;
        color: #1e293b;
        margin-left: 10px;
    }

    .form-select,
    .form-control {
        border-radius: 20px;
        padding: 15px 25px;
        border: 2px solid #f1f5f9;
        background: #f8fafc;
        font-weight: 500;
        transition: all 0.3s;
    }

    .form-select:focus {
        border-color: #3b82f6;
        background: #fff;
        box-shadow: 0 10px 20px rgba(59, 130, 246, 0.1);
    }

    .amount-display {
        background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
        border-radius: 25px;
        padding: 25px;
        margin: 25px 0;
        border: 1px dashed #3b82f6;
    }

    .btn-premium {
        background: linear-gradient(135deg, #2563eb, #1e40af);
        color: white;
        border: none;
        padding: 20px;
        border-radius: 22px;
        font-weight: 700;
        width: 100%;
        box-shadow: 0 20px 40px -10px rgba(37, 99, 235, 0.4);
        transition: all 0.3s;
    }

    .btn-premium:hover {
        transform: translateY(-3px);
        box-shadow: 0 25px 50px -10px rgba(37, 99, 235, 0.5);
        color: white;
    }
</style>

<?php if ($sub && $sub['status'] === 'waiting_approve'): ?>
    <div id="subscription-overlay">
        <div class="sub-card">
            <div class="status-ocean">
                <div class="wave-circle"></div>
                <div class="wave-circle"></div>
                <div class="icon-box-3d">⌛</div>
            </div>

            <h3 class="fw-bold text-dark mb-1">กำลังดำเนินการ</h3>
            <p class="text-muted mb-4">เราได้รับข้อมูลการชำระเงินเรียบร้อยแล้ว</p>

            <div class="plan-badge">
                <i class="bi bi-box-seam me-2"></i> แพ็กเกจ: <?= htmlspecialchars($sub['plan']) ?>
            </div>

            <?php if (!empty($sub['slip_image'])): ?>
                <div class="slip-container-premium text-center">
                    <img src="../<?= htmlspecialchars($sub['slip_image']) ?>" class="slip-preview-premium">
                </div>
            <?php endif; ?>

            <div class="p-4 rounded-4 bg-light text-start mb-4">
                <div class="d-flex align-items-center mb-2">
                    <div class="spinner-grow spinner-grow-sm text-primary me-3"></div>
                    <span class="fw-bold text-primary">แอดมินกำลังตรวจสอบความถูกต้อง</span>
                </div>
                <small class="text-muted">โดยปกติจะใช้เวลาไม่เกิน 1 ชั่วโมง (ช่วงเวลาทำการ 08:00 - 22:00)</small>
            </div>

            <button onclick="location.reload()" class="btn btn-outline-secondary w-100 rounded-4 py-3">
                <i class="bi bi-arrow-clockwise me-2"></i> รีเฟรชหน้าจอ
            </button>
        </div>
    </div>
<?php return;
endif; ?>


<?php
$stmt = $pdo->query("SELECT id, name, price, amount, qr_image FROM billing_plans WHERE status = 'active' ORDER BY price ASC");
$plans = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div id="subscription-overlay">
    <div class="sub-card">
        <div class="mb-4">
            <h3 class="fw-bold text-dark mb-2">เริ่มต้นใช้งานระบบ</h3>
            <p class="text-muted">กรุณาเลือกแพ็กเกจเพื่อเปิดการใช้งานร้านค้าของคุณ</p>
        </div>

        <form method="post" action="menu/subscription/subscribe_action.php" enctype="multipart/form-data" id="subForm">

            <div class="text-start mb-4">
                <label class="form-label">เลือกแพ็กเกจที่ต้องการ</label>
                <select class="form-select form-select-lg" name="plan_id" id="planSelect" required>
                    <option value="">-- คลิกเพื่อเลือก --</option>
                    <?php foreach ($plans as $p): ?>
                        <option value="<?= $p['id'] ?>"
                            data-amount="<?= $p['amount'] ?>"
                            data-qr="<?= htmlspecialchars($p['qr_image']) ?>">
                            <?= htmlspecialchars($p['name']) ?> (<?= number_format($p['price'], 0) ?> บาท)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div id="planInfo" class="d-none">
                <div class="amount-display">
                    <span class="text-muted small text-uppercase fw-bold">ยอดโอนสุทธิ</span>
                    <h2 class="fw-bold text-primary mb-0">฿<span id="planAmount"></span></h2>
                </div>

                <div class="mb-4">
                    <div style="background: white; display: inline-block; padding: 15px; border-radius: 25px; box-shadow: 0 10px 30px rgba(0,0,0,0.05);">
                        <img id="planQR" src="" style="width: 200px; height: 200px; object-fit: contain;">
                    </div>
                    <p class="mt-3 text-muted small"><i class="bi bi-qr-code-scan me-2"></i> สแกนผ่านแอปธนาคารเพื่อโอนเงิน</p>
                </div>

                <div class="text-start mb-4">
                    <label class="form-label">อัปโหลดสลิปยืนยัน</label>
                    <input type="file" name="slip_image" id="slipInput" class="form-control" accept="image/*" required>

                    <div id="previewWrapper" style="display:none;" class="mt-3">
                        <img id="imgPreview" src="#" style="width: 100%; border-radius: 15px; border: 2px solid #3b82f6;">
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-premium btn-lg" id="submitBtn">
                ยืนยันการสมัครสมาชิก
            </button>
        </form>
    </div>
</div>

<script>
    const select = document.getElementById('planSelect');
    const info = document.getElementById('planInfo');
    const qr = document.getElementById('planQR');
    const amount = document.getElementById('planAmount');
    const slipInput = document.getElementById('slipInput');
    const imgPreview = document.getElementById('imgPreview');
    const previewWrapper = document.getElementById('previewWrapper');
    const btn = document.getElementById('submitBtn');

    select.addEventListener('change', () => {
        const opt = select.selectedOptions[0];
        if (!opt || !opt.value) {
            info.classList.add('d-none');
            return;
        }
        amount.innerText = Number(opt.dataset.amount).toLocaleString();
        qr.src = '../adminpage/sidebar/' + opt.dataset.qr;
        info.classList.remove('d-none');
    });

    slipInput.onchange = evt => {
        const [file] = slipInput.files;
        if (file) {
            imgPreview.src = URL.createObjectURL(file);
            previewWrapper.style.display = 'block';
        }
    }

    document.getElementById('subForm').addEventListener('submit', () => {
        btn.innerHTML = `<span class="spinner-border spinner-border-sm me-2"></span> กำลังส่งข้อมูล...`;
        btn.disabled = true;
    });
</script>