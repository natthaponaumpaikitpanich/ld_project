<?php
require_once __DIR__ . '/../../../ld_db.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: index.php");
    exit;
}

try {
    $pdo->beginTransaction();

    // ลบ staff
    $pdo->prepare("DELETE FROM store_staff WHERE store_id=?")->execute([$id]);

    // ลบ subscriptions
    $pdo->prepare("DELETE FROM store_subscriptions WHERE store_id=?")->execute([$id]);

    // ลบ promotions
    $pdo->prepare("DELETE FROM promotions WHERE store_id=?")->execute([$id]);

    // ลบ orders (ถ้ามี FK อื่นค่อยขยาย)
    $pdo->prepare("DELETE FROM orders WHERE store_id=?")->execute([$id]);

    // ลบร้าน
    $pdo->prepare("DELETE FROM stores WHERE id=?")->execute([$id]);

    $pdo->commit();

} catch (Exception $e) {
    $pdo->rollBack();
    die("DELETE ERROR: " . $e->getMessage());
}

header("Location: ../sidebar.php?link=allstore");
exit;
