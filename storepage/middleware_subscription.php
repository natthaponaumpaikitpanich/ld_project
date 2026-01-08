<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../ld_db.php";

$store_id = $_SESSION['store_id'] ?? null;
$STORE_LOCKED = true;

if ($store_id) {
    $stmt = $pdo->prepare("
        SELECT status
        FROM store_subscriptions
        WHERE store_id = ?
        ORDER BY created_at DESC
        LIMIT 1
    ");
    $stmt->execute([$store_id]);
    $sub = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($sub && $sub['status'] === 'active') {
        $STORE_LOCKED = false;
    }
}
