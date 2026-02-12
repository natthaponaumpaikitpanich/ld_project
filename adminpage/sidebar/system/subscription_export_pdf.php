<?php
require_once "../../../ld_db.php";
require_once "../../../vendor/tcpdf/tcpdf.php";

/* ===== FONT SETUP ===== */
$fontFile = __DIR__ . "/../../../fonts/THSarabunNew.ttf";
$tcpdfFont = __DIR__ . "/../../../vendor/tcpdf/fonts/thsarabunnew.php";
if (!file_exists($tcpdfFont)) {
    TCPDF_FONTS::addTTFfont($fontFile, 'TrueTypeUnicode', '', 32);
}

/* ===== FILTER & DATA (คงเดิม) ===== */
$filter = $_GET['filter'] ?? 'all';
$where = "WHERE ss.status IN ('active','waiting_approve')";
if ($filter === 'today') { $where .= " AND DATE(ss.paid_at) = CURDATE()"; } 
elseif ($filter === 'month') { $where .= " AND MONTH(ss.paid_at) = MONTH(CURDATE()) AND YEAR(ss.paid_at) = YEAR(CURDATE())"; }

$sql = "SELECT s.name AS store_name, ss.plan, ss.monthly_fee, ss.status, ss.paid_at FROM store_subscriptions ss JOIN stores s ON ss.store_id = s.id $where ORDER BY ss.paid_at DESC";
$rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

$summarySql = "SELECT COUNT(*) AS total_txn, SUM(monthly_fee) AS total_amount FROM store_subscriptions ss $where";
$summary = $pdo->query($summarySql)->fetch(PDO::FETCH_ASSOC);

/* ===== TCPDF SETTINGS ===== */
class MYPDF extends TCPDF {
    public function Header() {
        // วาดแถบสีฟ้าด้านบนเป็น Design
        $this->SetFillColor(37, 99, 235);
        $this->Rect(0, 0, 210, 15, 'F');
    }
}

$pdf = new MYPDF('P', 'mm', 'A4');
$pdf->SetCreator('Laundry Platform');
$pdf->SetTitle('Transaction Report');
$pdf->SetMargins(15, 25, 15);
$pdf->SetAutoPageBreak(TRUE, 20);
$pdf->AddPage();

/* ===== HEADER SECTION ===== */
$logo = '../../../image/3.jpg';
if (file_exists($logo)) {
    $pdf->Image($logo, 15, 20, 22);
}

$pdf->SetFont('thsarabunnew', 'B', 22);
$pdf->SetTextColor(30, 41, 59);
$pdf->Cell(0, 10, 'รายงานธุรกรรมการสมัครแพ็กเกจ', 0, 1, 'R');
$pdf->SetFont('thsarabunnew', '', 14);
$pdf->SetTextColor(100, 116, 139);
$pdf->Cell(0, 5, 'Laundry Management System Platform', 0, 1, 'R');
$pdf->Cell(0, 5, 'ช่วงเวลา: ' . ($filter == 'today' ? 'วันนี้' : ($filter == 'month' ? 'เดือนนี้' : 'ทั้งหมด')), 0, 1, 'R');

$pdf->Ln(10);

/* ===== SUMMARY CARDS (Blue Theme) ===== */
$html = '
<table cellspacing="0" cellpadding="10" border="0">
    <tr>
        <td width="50%" style="background-color: #eff6ff; border: 1px solid #dbeafe; border-radius: 10px;">
            <span style="font-size: 12pt; color: #1e40af;">จำนวนธุรกรรมทั้งหมด</span><br>
            <span style="font-size: 20pt; font-weight: bold; color: #2563eb;">' . number_format($summary['total_txn']) . ' <span style="font-size: 12pt;">รายการ</span></span>
        </td>
        <td width="4%"></td>
        <td width="46%" style="background-color: #2563eb; border-radius: 10px;">
            <span style="font-size: 12pt; color: #ffffff;">รายได้รวมทั้งหมด</span><br>
            <span style="font-size: 20pt; font-weight: bold; color: #ffffff;">฿ ' . number_format($summary['total_amount'], 2) . '</span>
        </td>
    </tr>
</table>';
$pdf->writeHTML($html);

/* ===== MINI GRAPH (Simple Bar Chart) ===== */
$pdf->Ln(5);
$pdf->SetFont('thsarabunnew', 'B', 14);
$pdf->SetTextColor(30, 41, 59);
$pdf->Cell(0, 10, 'กราฟวิเคราะห์สถานะธุรกรรม', 0, 1);

// แยกข้อมูลทำกราฟ
$activeCount = 0; $waitingCount = 0;
foreach($rows as $r) { $r['status'] == 'active' ? $activeCount++ : $waitingCount++; }
$max = max($activeCount, $waitingCount, 1);

// วาดกราฟแท่งแบบ Manual
$pdf->SetFillColor(59, 130, 246); // Blue
$pdf->Rect(20, $pdf->GetY()+2, ($activeCount/$max)*150, 6, 'F');
$pdf->SetFont('thsarabunnew', '', 11);
$pdf->Text(20, $pdf->GetY()+8, "อนุมัติแล้ว ($activeCount รายการ)");

$pdf->SetFillColor(203, 213, 225); // Gray-Blue
$pdf->Rect(20, $pdf->GetY()+13, ($waitingCount/$max)*150, 6, 'F');
$pdf->Text(20, $pdf->GetY()+19, "รอการตรวจสอบ ($waitingCount รายการ)");

$pdf->Ln(25);

/* ===== TABLE DATA ===== */
$pdf->SetFont('thsarabunnew', 'B', 13);
$tbl = '
<table border="0" cellpadding="8" cellspacing="0">
    <thead>
        <tr style="background-color: #334155; color: #ffffff; text-align: center;">
            <th width="8%">#</th>
            <th width="32%">ชื่อร้านค้า</th>
            <th width="20%">แพ็กเกจ</th>
            <th width="15%">ยอดเงิน</th>
            <th width="12%">สถานะ</th>
            <th width="13%">วันที่</th>
        </tr>
    </thead>
    <tbody>';

$i = 1;
foreach ($rows as $r) {
    $bgColor = ($i % 2 == 0) ? '#f8fafc' : '#ffffff';
    $statusColor = ($r['status'] == 'active') ? '#16a34a' : '#ea580c';
    $statusText = ($r['status'] == 'active') ? 'อนุมัติ' : 'รอเช็ค';
    
    $tbl .= '
    <tr style="background-color: '.$bgColor.'; color: #334155;">
        <td align="center">'.$i++.'</td>
        <td>'.$r['store_name'].'</td>
        <td align="center">'.$r['plan'].'</td>
        <td align="right">'.number_format($r['monthly_fee'], 2).'</td>
        <td align="center" style="color: '.$statusColor.'; font-weight: bold;">'.$statusText.'</td>
        <td align="center">'.($r['paid_at'] ? date('d/m/Y', strtotime($r['paid_at'])) : '-').'</td>
    </tr>';
}

$tbl .= '</tbody></table>';

$pdf->writeHTML($tbl, true, false, false, false, '');

/* ===== FOOTER ===== */
$pdf->SetY(-20);
$pdf->SetFont('thsarabunnew', 'I', 10);
$pdf->SetTextColor(148, 163, 184);
$pdf->Cell(0, 10, 'เอกสารนี้สร้างโดยระบบอัตโนมัติเมื่อวันที่ '.date('d/m/Y H:i').' น.', 0, 0, 'C');

$pdf->Output('transaction_report.pdf', 'I');