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
<title>สร้างคำสั่งซัก</title>
<link href="../../../bootstrap/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Kanit&display=swap" rel="stylesheet">
<style>
body{font-family:'Kanit',sans-serif}
</style>
</head>
<body class="bg-light">

<div class="container py-5">
<div class="col-md-6 mx-auto">

<div class="card shadow">
<div class="card-body p-4">

<h4 class="fw-bold mb-3 text-center">🧺 สั่งให้มารับผ้า</h4>

<?php if ($success): ?>
<div class="alert alert-success text-center">
    สร้างคำสั่งซักสำเร็จ 🎉
</div>
<?php endif; ?>

<?php if ($errors): ?>
<div class="alert alert-danger">
<?php foreach($errors as $e): ?>
<div><?= htmlspecialchars($e) ?></div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<form method="post">

<!-- store -->
<div class="mb-3">
<label class="form-label">🏪 ร้านซัก</label>
<select name="store_id" class="form-select" required>
<option value="">เลือกร้าน</option>
<?php foreach($stores as $s): ?>
<option value="<?= $s['id'] ?>">
<?= htmlspecialchars($s['name']) ?> — <?= htmlspecialchars($s['address']) ?>
</option>
<?php endforeach; ?>
</select>
</div>

<!-- address -->
<div class="mb-3">
<label class="form-label">📍 ที่อยู่รับผ้า</label>
<textarea name="pickup_address" id="pickup_address"
class="form-control" rows="3" required></textarea>
</div>

<!-- gps -->
<input type="hidden" name="lat" id="lat">
<input type="hidden" name="lng" id="lng">

<div class="mb-3 text-center">
<button type="button" onclick="getLocation()"
class="btn btn-outline-success btn-sm">
📡 ใช้ตำแหน่งปัจจุบัน
</button>
<div id="gpsStatus" class="small text-muted mt-1"></div>
</div>

<!-- notes -->
<div class="mb-3">
<label class="form-label">📝 หมายเหตุ</label>
<textarea name="notes" class="form-control" rows="2"></textarea>
</div>

<button class="btn btn-primary w-100">
ยืนยันคำสั่งซัก
</button>

</form>

</div>
</div>
<a href="../../index.php">
<button class="btn btn-warning mt-3">
กลับหน้าหลัก
</button></a>
</div>
</div>

<script>
function getLocation(){
    if(!navigator.geolocation){
        alert("อุปกรณ์ไม่รองรับ GPS");
        return;
    }
    navigator.geolocation.getCurrentPosition(
        pos=>{
            lat.value = pos.coords.latitude;
            lng.value = pos.coords.longitude;
            gpsStatus.innerText = "✔️ ได้รับพิกัดแล้ว";
        },
        ()=> alert("ไม่สามารถเข้าถึงตำแหน่งได้")
    );
}
</script>

</body>
</html>
