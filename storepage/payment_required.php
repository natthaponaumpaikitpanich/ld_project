<?php
session_start();
require_once "../ld_db.php";

$store_id = $_SESSION['store_id'] ?? null;
if (!$store_id) {
    die('NO STORE');
}

/* ===== ดึง subscription ล่าสุด ===== */
$stmt = $pdo->prepare("
    SELECT id, status, slip
    FROM store_subscriptions
    WHERE store_id = ?
    ORDER BY created_at DESC
    LIMIT 1
");
$stmt->execute([$store_id]);
$sub = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$sub) {
    die('NO SUBSCRIPTION');
}

$status = $sub['status'];
$sub_id = $sub['id'];
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>ชำระค่าสมาชิก</title>
<link href="../bootstrap/css/bootstrap.min.css" rel="stylesheet">

<style>
#payment-overlay{
    position:fixed;
    inset:0;
    background:rgba(0,0,0,.65);
    z-index:100000;
    display:flex;
    align-items:center;
    justify-content:center;
}
#payment-box{
    background:#fff;
    border-radius:16px;
    width:420px;
    padding:24px;
    box-shadow:0 20px 40px rgba(0,0,0,.3);
}
.qr{
    width:100%;
    border-radius:12px;
}
</style>
</head>

<body>

<div id="payment-overlay">
<div id="payment-box">

<h5 class="fw-bold mb-2">🔒 ร้านของคุณยังไม่พร้อมใช้งาน</h5>

<?php if ($status === 'pending_payment'): ?>

<p class="text-muted mb-3">
กรุณาชำระค่าสมาชิกก่อนเข้าใช้งานระบบ
</p>

<img src="../assets/img/qr_admin.png" class="qr mb-3">

<form id="slipForm" enctype="multipart/form-data">
    <input type="hidden" name="sub_id" value="<?= $sub_id ?>">

    <div class="mb-3">
        <label class="form-label">อัปโหลดสลิป</label>
        <input type="file"
               name="slip"
               accept="image/*"
               class="form-control"
               required>
    </div>

    <button class="btn btn-primary w-100">
        ส่งสลิป
    </button>
</form>

<?php elseif ($status === 'pending_approve'): ?>

<div class="alert alert-info mb-0 text-center">
    ⏳ ส่งสลิปแล้ว<br>
    รอแอดมินตรวจสอบ
</div>

<?php endif; ?>

</div>
</div>

<script>
/* ===== upload slip ===== */
const form = document.getElementById('slipForm');
if(form){
    form.addEventListener('submit', e=>{
        e.preventDefault();
        const fd = new FormData(form);

        fetch('ajax/upload_slip.php',{
            method:'POST',
            body:fd
        }).then(r=>r.json())
        .then(res=>{
            if(res.ok){
                location.reload();
            }else{
                alert(res.error);
            }
        });
    });
}

/* ===== realtime check ===== */
setInterval(()=>{
    fetch('ajax/check_subscription.php')
    .then(r=>r.json())
    .then(res=>{
        if(res.status === 'active'){
            location.reload();
        }
    });
},3000);
</script>

</body>
</html>
