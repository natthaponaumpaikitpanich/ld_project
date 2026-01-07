<?php
ob_start();


/* ================== FUNCTION ================== */
function uuid_v4()
{
    $data = random_bytes(16);
    $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
    $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

/* ================== LOAD DATA ================== */

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

/* ================== SUBMIT ================== */
$errors = [];

?>

<!doctype html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <title>สมัครร้านใหม่</title>
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

           <form method="post" action="stores/create_action.php" class="row g-3">

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
                    <input type="text" name="lat" class="form-control">
                </div>

                <div class="col-md-3">
                    <label class="form-label">Longitude</label>
                    <input type="text" name="lng" class="form-control">
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
                                <?= $p['name'] ?> (<?= number_format($p['price']) ?> ฿)
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
