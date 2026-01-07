<?php
require_once __DIR__ . '/../../../ld_db.php';

function uuid_v4() {
    $data = random_bytes(16);
    $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
    $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

// รับค่า
$store_id = uuid_v4();
$owner_id = $_POST['owner_id'] ?? '';
$name = trim($_POST['name'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$address = trim($_POST['address'] ?? '');
$lat = $_POST['lat'] !== '' ? $_POST['lat'] : null;
$lng = $_POST['lng'] !== '' ? $_POST['lng'] : null;
$timezone = $_POST['timezone'] ?? 'Asia/Bangkok';
$status = $_POST['status'] ?? 'pending';
$billing_plan_id = $_POST['billing_plan_id'] ?? '';

try {
    $pdo->beginTransaction();

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
        ':id' => $store_id,
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

    $stmt = $pdo->prepare("
        INSERT INTO store_staff (id, store_id, user_id, role)
        VALUES (?, ?, ?, 'store_owner')
    ");
    $stmt->execute([
        uuid_v4(),
        $store_id,
        $owner_id
    ]);

    $pdo->commit();

    header("Location: ../sidebar.php?link=allstore");
    exit;

} catch (Exception $e) {
    $pdo->rollBack();
    die("ERROR: " . $e->getMessage());
}