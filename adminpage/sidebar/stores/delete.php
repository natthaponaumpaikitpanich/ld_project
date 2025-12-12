<?php
include_once '../../index.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: index.php");
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. ลบ subscription
    $pdo->prepare("DELETE FROM store_subscriptions WHERE store_id=?")
        ->execute([$id]);

    // 2. ลบ staff
    $pdo->prepare("DELETE FROM store_staff WHERE store_id=?")
        ->execute([$id]);

    // 3. ลบ tags
    $pdo->prepare("DELETE FROM tags WHERE store_id=?")
        ->execute([$id]);

    // 4. ลบ orders (ถ้ามี)
    $pdo->prepare("DELETE FROM orders WHERE store_id=?")
        ->execute([$id]);

    // 5. ลบร้าน
    $pdo->prepare("DELETE FROM stores WHERE id=?")
        ->execute([$id]);

    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
}

header("Location: ../sidebar.php?link=allstore");
exit;
