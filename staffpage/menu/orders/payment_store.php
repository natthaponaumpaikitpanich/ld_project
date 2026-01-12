<?php
session_start();
require_once "../../../ld_db.php"; // PDO

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Method not allowed');
}

$order_id = $_POST['order_id'] ?? null;
$amount   = $_POST['amount'] ?? null;
$provider = $_POST['provider'] ?? null;
$note     = $_POST['note'] ?? null;

if (!$order_id || !$amount || !$provider) {
    die("ข้อมูลไม่ครบ");
}

// ✅ สร้าง UUID แบบไม่พัง
$payment_id = bin2hex(random_bytes(16));

$pdo->beginTransaction();

try {

    // 1) insert payment
    $stmt = $pdo->prepare("
        INSERT INTO payments (
            id, order_id, amount, provider, status, paid_at
        ) VALUES (?, ?, ?, ?, 'success', NOW())
    ");
    $stmt->execute([
        $payment_id,
        $order_id,
        $amount,
        $provider
    ]);

    // 2) update order
    $stmt = $pdo->prepare("
        UPDATE orders 
        SET payment_status = 'paid'
        WHERE id = ?
    ");
    $stmt->execute([$order_id]);

    $pdo->commit();

    header("Location: order_view.php?id=" . $order_id);
    exit;
} catch (Exception $e) {
    $pdo->rollBack();
    die("เกิดข้อผิดพลาด: " . $e->getMessage());
}
