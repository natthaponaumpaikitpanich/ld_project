<?php
require_once __DIR__ . "../../../../ld_db.php";

// filter
$filter = $_GET['filter'] ?? 'all';

$where = '';
if ($filter === 'today') {
    $where = "WHERE DATE(pay.paid_at) = CURDATE()";
} elseif ($filter === 'month') {
    $where = "WHERE MONTH(pay.paid_at)=MONTH(CURDATE())
              AND YEAR(pay.paid_at)=YEAR(CURDATE())";
}

$sql = "
SELECT
    pay.id,
    s.name AS store_name,
    o.order_number,
    pay.amount,
    pay.provider,
    pay.status,
    pay.paid_at
FROM payments pay
LEFT JOIN orders o ON pay.order_id = o.id
LEFT JOIN stores s ON o.store_id = s.id
$where
ORDER BY pay.paid_at DESC
";

$rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

/* ====== CSV HEADER ====== */
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=transactions_'.$filter.'.csv');

$output = fopen('php://output', 'w');

/* หัวตาราง */
fputcsv($output, [
    'ร้านค้า',
    'เลขออเดอร์',
    'ยอดเงิน',
    'ช่องทาง',
    'สถานะ',
    'วันที่ชำระ'
]);

/* ข้อมูล */
foreach ($rows as $r) {
    fputcsv($output, [
        $r['store_name'],
        $r['order_number'],
        $r['amount'],
        $r['provider'],
        $r['status'],
        $r['paid_at']
    ]);
}

fclose($output);
exit;
