<?php


// ฟังก์ชัน UUID
function uuid_v4() {
    $data = random_bytes(16);
    $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
    $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

// ดึงรายชื่อ owner ทั้งหมด
$owners = [];
$q = $pdo->prepare("
    SELECT id, display_name, email 
    FROM users 
    WHERE role = 'store_owner'
    ORDER BY display_name ASC
");

$q->execute();
$owners = $q->fetchAll(PDO::FETCH_ASSOC);

// เมื่อกด Submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $errors = [];

    // รับค่าฟอร์ม
    $id = uuid_v4();
    $owner_id = $_POST['owner_id'] ?? '';
    $name = trim($_POST['name']);
    $address = trim($_POST['address']);
    $lat = $_POST['lat'] !== '' ? $_POST['lat'] : null;
    $lng = $_POST['lng'] !== '' ? $_POST['lng'] : null;
    $phone = trim($_POST['phone']);
    $status = $_POST['status'];
    $timezone = $_POST['timezone'];

    // Validate
    if ($owner_id === '') $errors[] = "กรุณาเลือกเจ้าของร้าน";
    if ($name === '')     $errors[] = "กรุณาใส่ชื่อร้าน";
    if ($phone === '')    $errors[] = "กรุณาใส่เบอร์โทร";
    if ($lat === null || $lng === null) $errors[] = "กรุณาเลือกตำแหน่งพิกัดจากแผนที่";

    if (empty($errors)) {
        $sql = "INSERT INTO stores (id, owner_id, name, address, lat, lng, phone, status, timezone, created_at, updated_at)
                VALUES (:id, :owner_id, :name, :address, :lat, :lng, :phone, :status, :timezone, NOW(), NOW())";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':id' => $id,
            ':owner_id' => $owner_id,
            ':name' => $name,
            ':address' => $address,
            ':lat' => $lat,
            ':lng' => $lng,
            ':phone' => $phone,
            ':status' => $status,
            ':timezone' => $timezone
        ]);

        exit;
    }
}

?>
<!doctype html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <title>สมัครร้านค้าใหม่</title>
    
    <style>
        #map {
            width: 100%;
            height: 350px;
            border-radius: 8px;
        }
    </style>
</head>

<body style="margin-left:260px;">

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>สมัครร้านค้าใหม่</h3>

    </div>

    <div class="card shadow-sm">
        <div class="card-body">

            <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $e): ?>
                        <li><?= htmlspecialchars($e) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <form method="post" class="row g-3">

                <div class="col-md-6">
                    <label class="form-label">เจ้าของร้าน <span class="text-danger">*</span></label>
                    <select name="owner_id" class="form-select" required>
                        <option value="">-- เลือกเจ้าของร้าน --</option>
                        <?php foreach ($owners as $o): ?>
                        <option value="<?= $o['id'] ?>"><?= htmlspecialchars($o['display_name']." ({$o['email']})") ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">ชื่อร้าน <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label">เบอร์โทร <span class="text-danger">*</span></label>
                    <input type="text" name="phone" class="form-control" required>
                </div>

               <div class="col-12">
                    <label class="form-label">ที่อยู่</label>
                    <textarea name="address" class="form-control" rows="3"><?= htmlspecialchars($_POST['address'] ?? '') ?></textarea>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Latitude</label>
                    <input type="text" name="lat" class="form-control" value="<?= htmlspecialchars($_POST['lat'] ?? '') ?>" placeholder="13.7563">
                </div>

                <div class="col-md-3">
                    <label class="form-label">Longitude</label>
                    <input type="text" name="lng" class="form-control" value="<?= htmlspecialchars($_POST['lng'] ?? '') ?>" placeholder="100.5018">
                </div>

                <div class="col-md-6">
                    <label class="form-label">Timezone</label>
                    <input type="text" name="timezone" class="form-control" value="<?= htmlspecialchars($_POST['timezone'] ?? 'Asia/Bangkok') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Time Zone</label>
                    <input type="text" name="timezone" value="Asia/Bangkok" class="form-control">
                </div>

                <div class="col-md-6">
                    <label class="form-label">สถานะ</label>
                    <select name="status" class="form-select">
                        <option value="pending">Pending</option>
                        <option value="active">Active</option>
                        <option value="disabled">Disabled</option>
                    </select>
                </div>

                <div class="col-12 text-end">
                    <button class="btn btn-primary">บันทึกข้อมูล</button>
                </div>

            </form>
        </div>
    </div>
</div>

<script src="../../bootstrap-5.3.3-dist/js/bootstrap.bundle.min.js"></script>

<!-- GOOGLE MAPS -->
<script src="https://maps.googleapis.com/maps/api/js?key=YOUR_GOOGLE_MAP_API_KEY"></script>
<script>
let map, marker;

function initMap() {
    const center = { lat: 13.7563, lng: 100.5018 }; // กรุงเทพ

    map = new google.maps.Map(document.getElementById("map"), {
        center: center,
        zoom: 12
    });

    map.addListener("click", (e) => {
        placeMarker(e.latLng);
    });
}

function placeMarker(location) {
    if (!marker) {
        marker = new google.maps.Marker({
            position: location,
            map: map
        });
    } else {
        marker.setPosition(location);
    }

    document.getElementById("lat").value = location.lat();
    document.getElementById("lng").value = location.lng();
}

window.initMap = initMap;

</script>
</body>
</html>
