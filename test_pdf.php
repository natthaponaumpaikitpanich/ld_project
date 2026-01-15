<?php
require_once "vendor/tcpdf/tcpdf.php";

$pdf = new TCPDF();
$pdf->AddPage();
$pdf->SetFont('helvetica','',14);
$pdf->Cell(0,10,'TCPDF Ready!',0,1);
$pdf->Output();