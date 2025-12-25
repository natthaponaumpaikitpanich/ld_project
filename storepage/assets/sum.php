<?php
$store_id = $_SESSION['store_id'] ?? null;
if (!$store_id) {
    die("ไม่พบร้าน");
}

/* ---------- รายได้วันนี้ ---------- */
$stmt = $pdo->prepare("
    SELECT SUM(p.amount) 
    FROM payments p
    JOIN orders o ON p.order_id = o.id
    WHERE p.status = 'success'
      AND o.store_id = ?
      AND DATE(p.paid_at) = CURDATE()
");
$stmt->execute([$store_id]);
$today_income = $stmt->fetchColumn() ?? 0;

/* ---------- รายได้เดือนนี้ ---------- */
$stmt = $pdo->prepare("
    SELECT SUM(p.amount)
    FROM payments p
    JOIN orders o ON p.order_id = o.id
    WHERE p.status = 'success'
      AND o.store_id = ?
      AND MONTH(p.paid_at) = MONTH(CURDATE())
      AND YEAR(p.paid_at) = YEAR(CURDATE())
");
$stmt->execute([$store_id]);
$month_income = $stmt->fetchColumn() ?? 0;

/* ---------- รายการชำระเงิน ---------- */
$stmt = $pdo->prepare("
    SELECT 
        p.amount,
        p.provider,
        p.status,
        p.paid_at,
        o.order_number
    FROM payments p
    JOIN orders o ON p.order_id = o.id
    WHERE o.store_id = ?
    ORDER BY p.paid_at DESC
");
$stmt->execute([$store_id]);
$payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>