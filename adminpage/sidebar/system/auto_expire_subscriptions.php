<?php
require_once "../../../ld_db.php";

/* ===== AUTO EXPIRE SUBSCRIPTIONS ===== */
$sql = "
UPDATE store_subscriptions
SET status = 'expired'
WHERE status = 'active'
  AND end_date IS NOT NULL
  AND end_date < CURDATE()
";

$stmt = $pdo->prepare($sql);
$stmt->execute();

echo "Expired subscriptions updated: " . $stmt->rowCount();
