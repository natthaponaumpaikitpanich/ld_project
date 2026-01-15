<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../ld_db.php";

$store_id = $_SESSION['store_id'] ?? null;
$STORE_LOCKED = true;

if ($store_id) {

    /* ===== ดึง subscription ล่าสุด ===== */
    $stmt = $pdo->prepare("
        SELECT id, status, end_date
        FROM store_subscriptions
        WHERE store_id = ?
        ORDER BY created_at DESC
        LIMIT 1
    ");
    $stmt->execute([$store_id]);
    $sub = $stmt->fetch(PDO::FETCH_ASSOC);

    /* ===== AUTO EXPIRE (RUNTIME) ===== */
    if (
        $sub &&
        $sub['status'] === 'active' &&
        !empty($sub['end_date']) &&
        strtotime($sub['end_date']) < strtotime(date('Y-m-d'))
    ) {
        $stmt = $pdo->prepare("
            UPDATE store_subscriptions
            SET status = 'expired'
            WHERE id = ?
        ");
        $stmt->execute([$sub['id']]);

        $sub['status'] = 'expired';
    }

    /* ===== CHECK LOCK ===== */
    if ($sub && $sub['status'] === 'active') {
        $STORE_LOCKED = false;
    }
}
?>
