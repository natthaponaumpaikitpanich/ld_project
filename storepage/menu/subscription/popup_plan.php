<?php
// 1. เริ่มต้น Session และตั้งค่า Timezone ให้ตรงกับ Database
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
date_default_timezone_set('Asia/Bangkok');

$store_id = $_SESSION['store_id'] ?? null;
$now = date('Y-m-d H:i:s');

/* ===== 2. ตรวจสอบสถานะการสมัครสมาชิก (Subscription) ล่าสุด ===== */
$sub = null;
if ($store_id) {
    try {
        $stmt = $pdo->prepare("
            SELECT status, slip_image, plan
            FROM store_subscriptions
            WHERE store_id = ?
            ORDER BY created_at DESC
            LIMIT 1
        ");
        $stmt->execute([$store_id]);
        $sub = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Subscription Query Error: " . $e->getMessage());
    }
}

// หากอยู่ระหว่างรออนุมัติ ให้แสดงหน้า Waiting แล้วหยุด (Return) เพื่อไม่ให้โหลดส่วนที่เหลือ
if ($sub && $sub['status'] === 'waiting_approve'): ?>
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
                <small class="text-muted">ปกติจะใช้เวลาไม่เกิน 1 ชั่วโมง (ช่วงเวลาทำการ 08:00 - 22:00)</small>
            </div>
            <button onclick="location.reload()" class="btn btn-outline-secondary w-100 rounded-4 py-3">
                <i class="bi bi-arrow-clockwise me-2"></i> รีเฟรชหน้าจอ
            </button>
        </div>
    </div>
<?php 
return; // หยุดการทำงานของ PHP ในไฟล์นี้เพียงเท่านี้
endif; 

/* ===== 3. ดึงข้อมูลแพ็กเกจ และ โปรโมชั่นที่ใช้งานได้ ===== */
try {
    // ดึงแพ็กเกจ (Billing Plans)
    $planStmt = $pdo->query("SELECT id, name, price, amount, qr_image FROM billing_plans WHERE status = 'active' ORDER BY price ASC");
    $plans = $planStmt->fetchAll(PDO::FETCH_ASSOC);

    // ดึงโปรโมชั่น (Promotions) 
    // เงื่อนไข: สถานะ Active, กลุ่มเป้าหมายเป็น Stores หรือ All, และอยู่ในช่วงวันที่ใช้งานได้
    $promoStmt = $pdo->prepare("
        SELECT id, title, discount, discount_type 
        FROM promotions 
        WHERE status = 'active' 
        AND (audience = 'stores' OR audience = 'all')
        AND start_date <= ? 
        AND end_date >= ?
    ");
    $promoStmt->execute([$now, $now]);
    $available_promotions = $promoStmt->fetchAll(PDO::FETCH_ASSOC);

    // --- ส่วน Debug สำหรับคุณ (ถ้าโปรไม่ขึ้น ให้เอา // หน้า print_r ออกเพื่อดูค่า) ---
    // echo "<pre style='display:none;'>";
    // print_r($available_promotions);
    // echo "</pre>";

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>

<style>
    /* CSS ส่วนเดิมของคุณคงไว้ และเพิ่มส่วนตกแต่งราคาใหม่ */
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

    .sub-card {
        background: rgba(255, 255, 255, 0.95);
        width: 100%;
        max-width: 500px;
        max-height: 90vh;
        overflow-y: auto;
        border-radius: 40px;
        padding: 40px;
        text-align: center;
        box-shadow: 0 40px 100px -20px rgba(0, 0, 0, 0.5);
        animation: cardAppear 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
    }

    .amount-display {
        background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
        border-radius: 20px;
        padding: 20px;
        margin: 20px 0;
        border: 1px dashed #3b82f6;
    }

    .discount-line {
        color: #059669;
        font-weight: 600;
        font-size: 0.9rem;
    }

    .original-price {
        text-decoration: line-through;
        color: #94a3b8;
        font-size: 0.9rem;
    }

    .btn-premium {
        background: linear-gradient(135deg, #2563eb, #1e40af);
        color: white;
        border: none;
        padding: 16px;
        border-radius: 18px;
        font-weight: 700;
        width: 100%;
        box-shadow: 0 15px 30px -10px rgba(37, 99, 235, 0.4);
        transition: 0.3s;
    }

    /* ... (CSS อื่นๆ คงเดิมตามที่คุณส่งมา) ... */
</style>

<div id="subscription-overlay">
    <div class="sub-card">
        <div class="mb-4">
            <h3 class="fw-bold text-dark mb-2">เริ่มต้นใช้งานระบบ</h3>
            <p class="text-muted">กรุณาเลือกแพ็กเกจและโปรโมชั่นเพื่อเปิดใช้งานร้านค้า</p>
        </div>

        <form method="post" action="menu/subscription/subscribe_action.php" enctype="multipart/form-data" id="subForm">

            <div class="text-start mb-3">
                <label class="form-label">เลือกแพ็กเกจ</label>
                <select class="form-select form-select-lg" name="plan_id" id="planSelect" required>
                    <option value="">-- คลิกเพื่อเลือกแพ็กเกจ --</option>
                    <?php foreach ($plans as $p): ?>
                        <option value="<?= $p['id'] ?>"
                            data-amount="<?= $p['amount'] ?>"
                            data-qr="<?= htmlspecialchars($p['qr_image']) ?>">
                            <?= htmlspecialchars($p['name']) ?> (<?= number_format($p['price'], 0) ?> บาท)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="text-start mb-4">
                <label class="form-label">โปรโมชั่น/ส่วนลด</label>
                <select class="form-select" name="promotion_id" id="promoSelect">
                    <option value="" data-discount="0" data-type="fixed">ไม่ใช้โปรโมชั่น</option>
                    <?php foreach ($available_promotions as $promo): ?>
                        <option value="<?= $promo['id'] ?>"
                            data-discount="<?= $promo['discount'] ?>"
                            data-type="<?= $promo['discount_type'] ?>">
                            <?= htmlspecialchars($promo['title']) ?> (ลด <?= number_format($promo['discount']) ?><?= $promo['discount_type'] == 'percentage' ? '%' : '฿' ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div id="planInfo" class="d-none">
                <div class="amount-display">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted small">ราคาปกติ:</span>
                        <span class="original-price" id="rawPrice">0 บาท</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted small">ส่วนลด:</span>
                        <span class="discount-line" id="discountText">-0 บาท</span>
                    </div>
                    <hr class="my-2" style="border-style: dashed; border-color: #cbd5e1;">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fw-bold text-dark">ยอดโอนสุทธิ:</span>
                        <h2 class="fw-bold text-primary mb-0">฿<span id="planAmount">0</span></h2>
                    </div>
                </div>

                <div class="mb-4">
                    <div style="background: white; display: inline-block; padding: 15px; border-radius: 25px; box-shadow: 0 10px 30px rgba(0,0,0,0.05);">
                        <img id="planQR" src="" style="width: 180px; height: 180px; object-fit: contain;">
                    </div>
                    <p class="mt-3 text-muted small"><i class="bi bi-qr-code-scan me-2"></i> สแกนเพื่อชำระเงินตามยอดสุทธิ</p>
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
    const selectPlan = document.getElementById('planSelect');
    const selectPromo = document.getElementById('promoSelect');
    const planInfo = document.getElementById('planInfo');
    const rawPriceDisp = document.getElementById('rawPrice');
    const discountDisp = document.getElementById('discountText');
    const amountDisp = document.getElementById('planAmount');
    const qrImg = document.getElementById('planQR');
    const submitBtn = document.getElementById('submitBtn');

    function calculatePrice() {
        const planOpt = selectPlan.selectedOptions[0];
        const promoOpt = selectPromo.selectedOptions[0];

        if (!planOpt || !planOpt.value) {
            planInfo.classList.add('d-none');
            return;
        }

        // ค่าพื้นฐาน
        let price = parseFloat(planOpt.dataset.amount);
        let discountVal = parseFloat(promoOpt.dataset.discount);
        let discountType = promoOpt.dataset.type;
        let finalDiscount = 0;

        // คำนวณส่วนลด
        if (discountType === 'percentage') {
            finalDiscount = (price * discountVal) / 100;
        } else {
            finalDiscount = discountVal;
        }

        let netTotal = price - finalDiscount;
        if (netTotal < 0) netTotal = 0;

        // อัปเดต UI
        rawPriceDisp.innerText = price.toLocaleString() + " บาท";
        discountDisp.innerText = "-" + finalDiscount.toLocaleString() + " บาท";
        amountDisp.innerText = netTotal.toLocaleString();
        qrImg.src = '../adminpage/sidebar/' + planOpt.dataset.qr;

        planInfo.classList.remove('d-none');
    }

    // เมื่อเปลี่ยนแพ็กเกจ หรือ เปลี่ยนโปรโมชั่น ให้คำนวณใหม่ทันที
    selectPlan.addEventListener('change', calculatePrice);
    selectPromo.addEventListener('change', calculatePrice);

    // Preview รูปสลิป
    document.getElementById('slipInput').onchange = evt => {
        const [file] = evt.target.files;
        if (file) {
            document.getElementById('imgPreview').src = URL.createObjectURL(file);
            document.getElementById('previewWrapper').style.display = 'block';
        }
    }

    // Loading ตอนกดส่ง
    document.getElementById('subForm').addEventListener('submit', () => {
        submitBtn.innerHTML = `<span class="spinner-border spinner-border-sm me-2"></span> กำลังส่งข้อมูล...`;
        submitBtn.disabled = true;
    });
</script>