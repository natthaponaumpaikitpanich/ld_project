
<?php
session_start();
require_once "../../../ld_db.php";

$staff_id   = $_SESSION['user_id'] ?? null;
$pickup_id  = $_POST['pickup_id'] ?? null;
$order_id   = $_POST['order_id'] ?? null;
$next_status = $_POST['next_status'] ?? null;

if (!$staff_id || !$order_id || !$next_status) {
    die("ข้อมูลไม่ครบ");
}

try {
    $pdo->beginTransaction();

    // 1) update order status
    $stmt = $pdo->prepare("
        UPDATE orders 
        SET status = ?
        WHERE id = ?
    ");
    $stmt->execute([$next_status, $order_id]);

    // 2) log status change
    $stmt = $pdo->prepare("
        INSERT INTO order_status_logs (
            id, order_id, status, changed_by
        ) VALUES (
            UUID(), ?, ?, ?
        )
    ");
    $stmt->execute([$order_id, $next_status, $staff_id]);

    // 3) ถ้างานเสร็จ
    if ($next_status === 'completed') {
        $stmt = $pdo->prepare("
            UPDATE pickups
            SET status = 'completed',
                completed_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$pickup_id]);
    }
    if ($next_status === 'completed') {

    // update pickup
    $stmt = $pdo->prepare("
        UPDATE pickups
        SET status = 'completed',
            completed_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$pickup_id]);

    // 🔥 ปลด machine
    $stmt = $pdo->prepare("
        UPDATE machine_orders
        SET active = 0
        WHERE order_id = ?
    ");
    $stmt->execute([$order_id]);
}

    $pdo->commit();

    header("Location:../../index.php?link=Tasks");
    exit;

} catch (Exception $e) {
    $pdo->rollBack();
    die("เกิดข้อผิดพลาด: " . $e->getMessage());
}
?>