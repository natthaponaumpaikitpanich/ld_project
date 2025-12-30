<?php
session_start();
require_once "../../../ld_db.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    http_response_code(403);
    echo json_encode(['error' => 'no permission']);
    exit;
}

$stmt = $pdo->prepare("
    SELECT 
        p.id AS pickup_id,
        o.id AS order_id,
        o.order_number,
        o.status,
        u.display_name AS customer_name
    FROM pickups p
    JOIN orders o ON p.order_id = o.id
    LEFT JOIN users u ON o.customer_id = u.id
    WHERE p.assigned_to = ?
      AND DATE(p.scheduled_at) = CURDATE()
    ORDER BY p.scheduled_at ASC
");

$stmt->execute([$_SESSION['user_id']]);
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
