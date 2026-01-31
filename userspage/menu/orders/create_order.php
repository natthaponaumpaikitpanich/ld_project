<?php
session_start();
require_once "../../../ld_db.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: ../../../loginpage/login.php");
    exit;
}

function uuid() {
    return sprintf(
        '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0,0xffff), mt_rand(0,0xffff),
        mt_rand(0,0xffff),
        mt_rand(0,0x0fff)|0x4000,
        mt_rand(0,0x3fff)|0x8000,
        mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0xffff)
    );
}

$customer_id = $_SESSION['user_id'];
$errors = [];
$success = false;

/* ---------- stores ---------- */
$stmt = $pdo->query("
    SELECT id, name, address
    FROM stores
    WHERE status='active'
");
$stores = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ---------- submit ---------- */
if ($_SERVER['REQUEST_METHOD']==='POST') {

    $store_id = $_POST['store_id'] ?? '';
    $notes = trim($_POST['notes'] ?? '');
    $pickup_address = trim($_POST['pickup_address'] ?? '');
    $lat = $_POST['lat'] ?? null;
    $lng = $_POST['lng'] ?? null;

    if (!$store_id) $errors[] = "กรุณาเลือกร้าน";
    if (!$pickup_address) $errors[] = "กรุณากรอกที่อยู่รับผ้า";
    if (!$lat || !$lng) $errors[] = "กรุณาอนุญาต GPS";

    if (!$errors) {
        try {
            $pdo->beginTransaction();

            $order_id = uuid();
            $order_no = 'LD-'.date('ymd').'-'.rand(1000,9999);

            /* orders */
            $stmt = $pdo->prepare("
                INSERT INTO orders
                (id, customer_id, store_id, order_number, status, payment_status, notes)
                VALUES (?,?,?,?, 'created','pending',?)
            ");
            $stmt->execute([
                $order_id,
                $customer_id,
                $store_id,
                $order_no,
                $notes
            ]);

            /* pickups */
            $stmt = $pdo->prepare("
                INSERT INTO pickups
                (id, order_id, pickup_address, lat, lng, status)
                VALUES (?,?,?,?,?, 'scheduled')
            ");
            $stmt->execute([
                uuid(),
                $order_id,
                $pickup_address,
                $lat,
                $lng
            ]);

            /* log */
            $stmt = $pdo->prepare("
                INSERT INTO order_status_logs
                (id, order_id, status, changed_by)
                VALUES (?,?, 'created',?)
            ");
            $stmt->execute([uuid(), $order_id, $customer_id]);

            $pdo->commit();
            $success = true;

        } catch(Exception $e) {
            $pdo->rollBack();
            $errors[] = "เกิดข้อผิดพลาด";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>สร้างคำสั่งซัก | Laundry Platform</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<link href="../../../bootstrap/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="icon" href="../../../image/3.jpg">

<style>
body{
    font-family:'Kanit',sans-serif;
    background:#f6f7fb;
}

/* CARD */
.order-card{
    border-radius:22px;
    border:none;
    box-shadow:0 15px 35px rgba(0,0,0,.1);
}

/* HEADER */
.order-header{
    background:linear-gradient(135deg,#1e3c72,#2a5298);
    color:#fff;
    border-radius:22px 22px 0 0;
    padding:24px;
    text-align:center;
}

/* FORM */
.form-control,.form-select{
    border-radius:12px;
}

.form-control:focus,.form-select:focus{
    border-color:#2a5298;
    box-shadow:0 0 0 .2rem rgba(42,82,152,.25);
}

/* BUTTON */
.btn-main{
    background:#2a5298;
    color:#fff;
    border-radius:14px;
    font-weight:500;
}

.btn-main:hover{
    background:#1e3c72;
}
</style>
</head>

<body>

<div class="container py-4">
<div class="col-lg-6 mx-auto">

<div class="card order-card">

<!-- HEADER -->
<div class="order-header">
    <h5 class="fw-semibold mb-1">🧺 สั่งให้มารับผ้า</h5>
    <small class="opacity-75">กรอกข้อมูลเพื่อสร้างคำสั่งซัก</small>
</div>

<div class="card-body p-4">

<?php if ($success): ?>
<div class="alert alert-success text-center">
    🎉 สร้างคำสั่งซักสำเร็จแล้ว
</div>
<?php endif; ?>

<?php if ($errors): ?>
<div class="alert alert-danger">
<?php foreach($errors as $e): ?>
<div><?= htmlspecialchars($e) ?></div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<form method="post" id="orderForm">

<!-- STORE -->
<div class="mb-3">
<label class="form-label">🏪 เลือกร้านซัก</label>
<select name="store_id" class="form-select" required>
<option value="">เลือกร้าน</option>
<?php foreach($stores as $s): ?>
<option value="<?= $s['id'] ?>">
<?= htmlspecialchars($s['name']) ?> — <?= htmlspecialchars($s['address']) ?>
</option>
<?php endforeach; ?>
</select>
</div>

<!-- ADDRESS -->
<div class="mb-3">
<label class="form-label">📍 ที่อยู่รับผ้า</label>
<textarea name="pickup_address" id="pickup_address"
class="form-control" rows="3"
placeholder="กรอกที่อยู่ให้ละเอียด เช่น บ้านเลขที่ ซอย จุดสังเกต"
required></textarea>
</div>

<!-- GPS -->
<input type="hidden" name="lat" id="lat">
<input type="hidden" name="lng" id="lng">

<div class="mb-3 text-center">
<button type="button"
        onclick="getLocation()"
        class="btn btn-outline-success btn-sm">
📡 ใช้ตำแหน่งปัจจุบัน
</button>
<div id="gpsStatus" class="small text-muted mt-1"></div>
</div>

<!-- NOTES -->
<div class="mb-4">
<label class="form-label">📝 หมายเหตุถึงร้าน (ถ้ามี)</label>
<textarea name="notes"
class="form-control"
rows="2"
placeholder="เช่น ผ้าขาวแยกซัก, ผ้าเด็ก"></textarea>
</div>

<button class="btn btn-main w-100 py-2" id="submitBtn">
ยืนยันคำสั่งซัก
</button>

</form>

</div>
</div>

<a href="../../index.php" class="btn btn-outline-secondary mt-3 w-100">
← กลับหน้าหลัก
</a>

</div>
</div>

<script src="../../../bootstrap/js/bootstrap.bundle.min.js"></script>

<script>
function getLocation(){
    if(!navigator.geolocation){
        alert("อุปกรณ์ไม่รองรับ GPS");
        return;
    }

    gpsStatus.innerText = "กำลังขอพิกัด...";
    navigator.geolocation.getCurrentPosition(
        pos=>{
            lat.value = pos.coords.latitude;
            lng.value = pos.coords.longitude;
            gpsStatus.innerText = "✔️ ได้รับพิกัดแล้ว";
        },
        ()=>{
            gpsStatus.innerText = "❌ ไม่สามารถเข้าถึงตำแหน่งได้";
        }
    );
}

/* loading button */
const form = document.getElementById('orderForm');
const btn = document.getElementById('submitBtn');
form.addEventListener('submit',()=>{
    btn.innerText = 'กำลังสร้างคำสั่งซัก...';
    btn.disabled = true;
});
</script>

</body>
</html>
