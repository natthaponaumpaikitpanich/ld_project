<?php
// 1. เริ่มต้น Session และตั้งค่า Timezone
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
date_default_timezone_set('Asia/Bangkok');

$store_id = $_SESSION['store_id'] ?? null;
$now = date('Y-m-d H:i:s');

/* ===== 2. ตรวจสอบสถานะการสมัครสมาชิก ===== */
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

// กรณีรออนุมัติ: แสดงการ์ดขนาดเล็กกะทัดรัด
if ($sub && $sub['status'] === 'waiting_approve'): ?>
    <div id="subscription-overlay">
        <div class="sub-card-compact">
            <div class="wait-header">
                <div class="spinner-ring"></div>
                <span class="wait-emoji">⏳</span>
            </div>

            <h4 class="fw-bold text-dark mt-3 mb-1">รอการยืนยัน</h4>
            <p class="text-muted mb-4" style="font-size: 13px;">แอดมินกำลังตรวจสอบยอดโอนของคุณ</p>

            <div class="mini-info-pill">
                <i class="bi bi-box-seam me-2"></i>แพ็กเกจ: <?= htmlspecialchars($sub['plan']) ?>
            </div>

            <?php if (!empty($sub['slip_image'])): ?>
                <div class="mini-slip-section">
                    <p class="small-label">หลักฐานการโอน</p>
                    <div class="mini-slip-frame" onclick="window.open('../<?= htmlspecialchars($sub['slip_image']) ?>', '_blank')">
                        <img src="../<?= htmlspecialchars($sub['slip_image']) ?>">
                        <div class="mini-slip-hover"><i class="bi bi-zoom-in"></i></div>
                    </div>
                </div>
            <?php endif; ?>

            <div class="admin-status-bar">
                <div class="pulse-dot"></div>
                <div class="text-start ms-3">
                    <div class="fw-bold text-primary" style="font-size: 12px;">กำลังตรวจสอบข้อมูล...</div>
                    <div class="text-muted" style="font-size: 10px;">เฉลี่ยไม่เกิน 1 ชม. (08:00 - 22:00)</div>
                </div>
            </div>

            <button onclick="location.reload()" class="btn-refresh-simple">
                <i class="bi bi-arrow-clockwise me-1"></i> รีเฟรชสถานะ
            </button>
        </div>
    </div>
<?php
    return;
endif;

/* ===== 3. ดึงข้อมูลแพ็กเกจและโปรโมชั่น (หน้าปกติ) ===== */
try {
    $planStmt = $pdo->query("SELECT id, name, price, amount, qr_image FROM billing_plans WHERE status = 'active' ORDER BY price ASC");
    $plans = $planStmt->fetchAll(PDO::FETCH_ASSOC);

    $promoStmt = $pdo->prepare("
        SELECT id, title, discount, discount_type, is_flash_sale, usage_limit, used_count, end_date
        FROM promotions 
        WHERE status = 'active' 
        AND (audience = 'stores' OR audience = 'all' OR store_id = ?)
        AND start_date <= ? AND end_date >= ?
        AND (usage_limit IS NULL OR used_count < usage_limit)
    ");
    $promoStmt->execute([$store_id, $now, $now]);
    $available_promotions = $promoStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>

<style>
    /* บังคับให้อยู่กึ่งกลางหน้าจอเสมอ */
    #subscription-overlay {
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, 0.9);
        backdrop-filter: blur(12px);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 9999999;
        padding: 15px;
        font-family: 'Kanit', sans-serif;
    }

    /* ปรับขนาดการ์ดให้กะทัดรัด (Compact) ไม่คับจอ */
    .sub-card-compact {
        background: #fff;
        width: 100%;
        max-width: 360px;
        /* จำกัดความกว้างไว้ที่ 360px พอดีสายตามือถือและคอม */
        border-radius: 30px;
        padding: 30px 25px;
        text-align: center;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        animation: cardAppear 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
    }

    /* หัวข้อหน้า Waiting */
    .wait-header {
        position: relative;
        width: 65px;
        height: 65px;
        margin: 0 auto;
    }

    .wait-emoji {
        font-size: 30px;
        position: relative;
        z-index: 2;
        line-height: 65px;
    }

    .spinner-ring {
        position: absolute;
        inset: 0;
        border: 3px solid #f1f5f9;
        border-top: 3px solid #2563eb;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }

    .mini-info-pill {
        display: inline-block;
        background: #eff6ff;
        color: #1e40af;
        padding: 6px 16px;
        border-radius: 50px;
        font-size: 13px;
        font-weight: 600;
        margin-bottom: 20px;
    }

    /* ปรับขนาดรูปสลิปให้เล็กลงมาก (Thumbnail style) */
    .mini-slip-section {
        background: #f8fafc;
        border-radius: 20px;
        padding: 12px;
        margin-bottom: 20px;
        border: 1px solid #e2e8f0;
    }

    .small-label {
        font-size: 10px;
        color: #94a3b8;
        text-transform: uppercase;
        font-weight: 700;
        margin-bottom: 8px;
        letter-spacing: 0.5px;
    }

    .mini-slip-frame {
        width: 110px;
        height: 150px;
        margin: 0 auto;
        border-radius: 12px;
        overflow: hidden;
        position: relative;
        cursor: pointer;
        border: 1px solid #cbd5e1;
    }

    .mini-slip-frame img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .mini-slip-hover {
        position: absolute;
        inset: 0;
        background: rgba(0, 0, 0, 0.4);
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: 0.3s;
    }

    .mini-slip-frame:hover .mini-slip-hover {
        opacity: 1;
    }

    .admin-status-bar {
        background: #f1f5f9;
        padding: 12px 16px;
        border-radius: 16px;
        display: flex;
        align-items: center;
        margin-bottom: 20px;
    }

    .pulse-dot {
        width: 8px;
        height: 8px;
        background: #2563eb;
        border-radius: 50%;
        animation: blink 1s infinite;
    }

    .btn-refresh-simple {
        width: 100%;
        border: 1.5px solid #d1d5db;
        background: #fff;
        padding: 12px;
        border-radius: 14px;
        font-weight: 600;
        color: #4b5563;
        font-size: 14px;
        transition: 0.2s;
    }

    .btn-refresh-simple:hover {
        background: #f9fafb;
        border-color: #9ca3af;
    }

    /* CSS หน้าสมัครสมาชิก */
    .amount-box-mini {
        background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
        border-radius: 18px;
        padding: 15px;
        margin: 15px 0;
        border: 1px dashed #3b82f6;
    }

    .qr-container-mini {
        background: white;
        padding: 10px;
        border-radius: 20px;
        display: inline-block;
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.05);
    }

    .btn-submit-premium {
        background: linear-gradient(135deg, #2563eb, #1e40af);
        color: #fff;
        width: 100%;
        padding: 14px;
        border-radius: 16px;
        border: none;
        font-weight: 700;
        box-shadow: 0 10px 20px -5px rgba(37, 99, 235, 0.4);
    }

    @keyframes spin {
        to {
            transform: rotate(360deg);
        }
    }

    @keyframes blink {

        0%,
        100% {
            opacity: 1;
        }

        50% {
            opacity: 0.3;
        }
    }

    @keyframes cardAppear {
        from {
            opacity: 0;
            transform: scale(0.9);
        }

        to {
            opacity: 1;
            transform: scale(1);
        }
    }
</style>

<div id="subscription-overlay">
    <div class="sub-card-compact">
        <div class="mb-3">
            <h4 class="fw-bold text-dark mb-1">เปิดใช้งานระบบ</h4>
            <p class="text-muted small">เลือกแพ็กเกจเพื่อเริ่มต้นจัดการร้านค้า</p>
        </div>

        <form method="post" action="menu/subscription/subscribe_action.php" enctype="multipart/form-data" id="subForm">
            <div class="text-start mb-2">
                <label class="small fw-bold ms-1">แพ็กเกจ</label>
                <select class="form-select rounded-3 shadow-sm" name="plan_id" id="planSelect" required>
                    <option value="">-- เลือกแพ็กเกจ --</option>
                    <?php foreach ($plans as $p): ?>
                        <option value="<?= $p['id'] ?>" data-amount="<?= $p['amount'] ?>" data-qr="<?= htmlspecialchars($p['qr_image']) ?>">
                            <?= htmlspecialchars($p['name']) ?> (<?= number_format($p['price'], 0) ?>.-)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="text-start mb-3">
                <label class="small fw-bold ms-1">ส่วนลด</label>
                <select class="form-select form-select-sm rounded-3" name="promotion_id" id="promoSelect">
                    <option value="" data-discount="0" data-type="fixed">ไม่ใช้โปรโมชั่น</option>
                    <?php foreach ($available_promotions as $promo): ?>
                        <option value="<?= $promo['id'] ?>"
                            data-discount="<?= $promo['discount'] ?>"
                            data-type="<?= $promo['discount_type'] ?>"
                            data-percent="<?= ($promo['usage_limit'] > 0) ? ($promo['used_count'] / $promo['usage_limit'] * 100) : 0 ?>">
                            <?= ($promo['is_flash_sale'] ? '🔥 ' : '') . htmlspecialchars($promo['title']) ?>
                            (-<?= number_format($promo['discount']) ?><?= $promo['discount_type'] == 'percentage' ? '%' : '฿' ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div id="planDetailArea" class="d-none">
                <div class="amount-box-mini">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="small text-muted">ยอดสุทธิที่ต้องโอน:</span>
                        <h3 class="fw-bold text-primary mb-0">฿<span id="finalAmt">0</span></h3>
                    </div>
                </div>

                <div class="qr-container-mini mb-3">
                    <img id="qrPreview" src="" style="width: 130px; height: 130px; object-fit: contain;">
                </div>

                <div class="text-start mb-3">
                    <label class="small fw-bold ms-1">แนบสลิป</label>
                    <input type="file" name="slip_image" id="slipInput" class="form-control form-control-sm rounded-3" accept="image/*" required>
                    <div id="filePreview" class="mt-2 text-center d-none">
                        <div class="mini-slip-frame" style="width: 80px; height: 110px;">
                            <img id="imgPre" src="">
                        </div>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn-submit-premium" id="btnSub">
                ยืนยันการสมัครสมาชิก
            </button>
        </form>
    </div>
</div>

<script>
    const pSel = document.getElementById('planSelect');
    const prSel = document.getElementById('promoSelect');
    const area = document.getElementById('planDetailArea');

    function updatePrice() {
        const pOpt = pSel.selectedOptions[0];
        const prOpt = prSel.selectedOptions[0];

        if (!pOpt.value) {
            area.classList.add('d-none');
            return;
        }

        let price = parseFloat(pOpt.dataset.amount);
        let disc = parseFloat(prOpt.dataset.discount);
        let type = prOpt.dataset.type;
        let final = (type === 'percentage') ? (price - (price * disc / 100)) : (price - disc);

        document.getElementById('finalAmt').innerText = Math.max(0, final).toLocaleString();
        document.getElementById('qrPreview').src = '../adminpage/sidebar/' + pOpt.dataset.qr;
        area.classList.remove('d-none');
    }

    pSel.addEventListener('change', updatePrice);
    prSel.addEventListener('change', updatePrice);

    document.getElementById('slipInput').onchange = e => {
        const [file] = e.target.files;
        if (file) {
            document.getElementById('imgPre').src = URL.createObjectURL(file);
            document.getElementById('filePreview').classList.remove('d-none');
        }
    }

    document.getElementById('subForm').onsubmit = () => {
        const b = document.getElementById('btnSub');
        b.disabled = true;
        b.innerHTML = `<span class="spinner-border spinner-border-sm me-2"></span>รอดำเนินการ...`;
    }
</script>