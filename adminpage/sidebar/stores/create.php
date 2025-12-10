<?php
// ================== FUNCTION ==================
function uuid_v4()
{
    $data = random_bytes(16);
    $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
    $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

// ================== LOAD DATA ==================

// แพ็กเกจ
$plans = $pdo->query("
    SELECT id, name, price 
    FROM billing_plans 
    WHERE status = 'active'
")->fetchAll(PDO::FETCH_ASSOC);

// เจ้าของร้าน
$q = $pdo->prepare("
    SELECT id, display_name, email 
    FROM users 
    WHERE role = 'store_owner'
    ORDER BY display_name
");
$q->execute();
$owners = $q->fetchAll(PDO::FETCH_ASSOC);

// ================== SUBMIT ==================
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id = uuid_v4();
    $owner_id = $_POST['owner_id'] ?? '';
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $lat = $_POST['lat'] !== '' ? $_POST['lat'] : null;
    $lng = $_POST['lng'] !== '' ? $_POST['lng'] : null;
    $timezone = $_POST['timezone'] ?? 'Asia/Bangkok';
    $status = $_POST['status'] ?? 'pending';
    $billing_plan_id = $_POST['billing_plan_id'] ?? '';

    // validate
    if ($owner_id === '') $errors[] = "กรุณาเลือกเจ้าของร้าน";
    if ($name === '') $errors[] = "กรุณาใส่ชื่อร้าน";
    if ($phone === '') $errors[] = "กรุณาใส่เบอร์โทร";
    if ($billing_plan_id === '') $errors[] = "กรุณาเลือกแพ็กเกจรายเดือน";

    if (empty($errors)) {

        $stmt = $pdo->prepare("
            INSERT INTO stores (
                id, owner_id, name, address,
                lat, lng, phone, status,
                timezone, billing_plan_id,
                created_at, updated_at
            ) VALUES (
                :id, :owner_id, :name, :address,
                :lat, :lng, :phone, :status,
                :timezone, :billing_plan_id,
                NOW(), NOW()
            )
        ");

        $stmt->execute([
            ':id' => $id,
            ':owner_id' => $owner_id,
            ':name' => $name,
            ':address' => $address,
            ':lat' => $lat,
            ':lng' => $lng,
            ':phone' => $phone,
            ':status' => $status,
            ':timezone' => $timezone,
            ':billing_plan_id' => $billing_plan_id
        ]);

        header("Location:sidebar.php?link=create");
        exit;
    }
}
?>
<!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<title>สมัครร้านใหม่</title>
<link href="../../bootstrap-5.3.3-dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body style="margin-left:260px;">
<div class="container py-4">

    <h3 class="mb-3">สมัครร้านซักอบรีดใหม่</h3>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $e): ?>
                    <li><?= htmlspecialchars($e) ?></li>
                <?php endforeach ?>
            </ul>
        </div>
    <?php endif ?>

    <div class="card shadow-sm">
        <div class="card-body">

            <form method="post" class="row g-3">

                <div class="col-md-6">
                    <label class="form-label">เจ้าของร้าน *</label>
                    <select name="owner_id" class="form-select" required>
                        <option value="">-- เลือก --</option>
                        <?php foreach ($owners as $o): ?>
                            <option value="<?= $o['id'] ?>">
                                <?= htmlspecialchars($o['display_name']) ?>
                            </option>
                        <?php endforeach ?>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">ชื่อร้าน *</label>
                    <input type="text" name="name" class="form-control" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label">เบอร์โทร *</label>
                    <input type="text" name="phone" class="form-control" required>
                </div>

                <div class="col-12">
                    <label class="form-label">ที่อยู่</label>
                    <textarea name="address" class="form-control" rows="3"></textarea>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Latitude</label>
                    <input type="text" name="lat" class="form-control" placeholder="13.7563">
                </div>

                <div class="col-md-3">
                    <label class="form-label">Longitude</label>
                    <input type="text" name="lng" class="form-control" placeholder="100.5018">
                </div>

                <div class="col-md-6">
                    <label class="form-label">Timezone</label>
                    <input type="text" name="timezone" value="Asia/Bangkok" class="form-control">
                </div>

                <div class="col-md-6">
                    <label class="form-label">สถานะร้าน</label>
                    <select name="status" class="form-select">
                        <option value="pending">Pending</option>
                        <option value="active">Active</option>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">แพ็กเกจรายเดือน *</label>
                    <select name="billing_plan_id" class="form-select" required>
                        <option value="">-- เลือกแพ็กเกจ --</option>
                        <?php foreach ($plans as $p): ?>
                            <option value="<?= $p['id'] ?>">
                                <?= $p['name'] ?> (<?= number_format($p['price']) ?>฿)
                            </option>
                        <?php endforeach ?>
                    </select>
                </div>

                <div class="col-12 text-end">
                    <button class="btn btn-primary px-4">บันทึกข้อมูล</button>
                </div>

            </form>

        </div>
    </div>

</div>
</body>
</html>
