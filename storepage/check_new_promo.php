<?php
session_start();
require_once "../ld_db.php";

header('Content-Type: application/json');

if (!isset($_SESSION['store_id'])) {
    echo json_encode(null);
    exit;
}

$store_id = $_SESSION['store_id'];
$now = date('Y-m-d H:i:s');

// ค้นหาโปรโมชั่นล่าสุดที่อยู่ในช่วงเวลา
$sql = "SELECT id, title, image, end_date 
        FROM promotions 
        WHERE status = 'active' 
        AND (? BETWEEN start_date AND end_date)
        AND (audience = 'stores' OR (audience = 'store_specific' AND store_id = ?))
        ORDER BY created_at DESC 
        LIMIT 1";

$stmt = $pdo->prepare($sql);
$stmt->execute([$now, $store_id]);
$promo = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode($promo);