<?php
require_once "../../../ld_db.php";
require_once "../../../vendor/tcpdf/tcpdf.php";

/* ===== ADD FONT (compatible ทุกเวอร์ชัน) ===== */
$fontFile = __DIR__ . "/../../../fonts/THSarabunNew.ttf";
$tcpdfFont = __DIR__ . "/../../../vendor/tcpdf/fonts/thsarabunnew.php";

if (!file_exists($tcpdfFont)) {
    TCPDF_FONTS::addTTFfont(
        $fontFile,
        'TrueTypeUnicode',
        '',
        32
    );
}

/* ===== FILTER ===== */
$filter = $_GET['filter'] ?? 'all';
$where = "WHERE ss.status IN ('active','waiting_approve')";

if ($filter === 'today') {
    $where .= " AND DATE(ss.paid_at) = CURDATE()";
} elseif ($filter === 'month') {
    $where .= " AND MONTH(ss.paid_at) = MONTH(CURDATE())
                AND YEAR(ss.paid_at) = YEAR(CURDATE())";
}

/* ===== DATA ===== */
$sql = "
SELECT
    s.name AS store_name,
    ss.plan,
    ss.monthly_fee,
    ss.status,
    ss.paid_at
FROM store_subscriptions ss
JOIN stores s ON ss.store_id = s.id
$where
ORDER BY ss.paid_at DESC
";
$rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

/* ===== SUMMARY ===== */
$summarySql = "
SELECT
    COUNT(*) AS total_txn,
    SUM(monthly_fee) AS total_amount
FROM store_subscriptions ss
$where
";
$summary = $pdo->query($summarySql)->fetch(PDO::FETCH_ASSOC);

/* ===== TCPDF ===== */
$pdf = new TCPDF('P','mm','A4');
$pdf->SetCreator('Laundry Platform');
$pdf->SetAuthor('Laundry Platform');
$pdf->SetTitle('Transaction Report');

$pdf->SetMargins(15,20,15);
$pdf->AddPage();

/* ===== LOGO ===== */
$logo = '../../../image/3.jpg'; // ปรับ path ตามโลโก้คุณ
if (file_exists($logo)) {
    $pdf->Image($logo, 15, 10, 20);
}

$pdf->SetFont('thsarabunnew','',14);
$pdf->Cell(0,10,'รายงานธุรกรรมการสมัครแพ็กเกจ',0,1,'C');
$pdf->SetFont('thsarabunnew','',14);
$pdf->Cell(0,8,'แพลตฟอร์มบริหารร้านซักอบรีด',0,1,'C');
$pdf->Ln(5);

/* ===== SUMMARY ===== */
$pdf->SetFont('thsarabunnew','',14);
$pdf->Cell(0,8,'สรุปรายได้',0,1);

$pdf->SetFont('thsarabunnew','',13);
$pdf->Cell(60,8,'จำนวนธุรกรรมทั้งหมด',1);
$pdf->Cell(0,8, number_format($summary['total_txn']),1,1);

$pdf->Cell(60,8,'รายได้รวม',1);
$pdf->Cell(0,8, number_format($summary['total_amount'],2).' บาท',1,1);

$pdf->Ln(5);

/* ===== TABLE HEADER ===== */
$pdf->SetFont('thsarabunnew','B',13);
$pdf->Cell(10,8,'#',1);
$pdf->Cell(55,8,'ร้านค้า',1);
$pdf->Cell(35,8,'แพ็กเกจ',1);
$pdf->Cell(30,8,'ยอดเงิน',1);
$pdf->Cell(30,8,'สถานะ',1);
$pdf->Cell(30,8,'วันที่ชำระ',1);
$pdf->Ln();

$pdf->SetFont('thsarabunnew','I',11);
$i = 1;
foreach ($rows as $r) {
    $pdf->Cell(10,8,$i++,1);
    $pdf->Cell(55,8,$r['store_name'],1);
    $pdf->Cell(35,8,$r['plan'],1);
    $pdf->Cell(30,8,number_format($r['monthly_fee'],2),1);
    $pdf->Cell(30,8,
        $r['status']=='active'?'อนุมัติ':'รอตรวจสอบ',1
    );
    $pdf->Cell(30,8,
        $r['paid_at'] ? date('d/m/Y',strtotime($r['paid_at'])) : '-',1
    );
    $pdf->Ln();
}

/* ===== FOOTER ===== */
$pdf->Ln(8);
$pdf->SetFont('thsarabunnew','I',11);
$pdf->Cell(0,8,'ออกรายงานเมื่อ '.date('d/m/Y H:i').' น.',0,1,'R');

/* ===== OUTPUT ===== */
$pdf->Output('transaction_report.pdf','I');
