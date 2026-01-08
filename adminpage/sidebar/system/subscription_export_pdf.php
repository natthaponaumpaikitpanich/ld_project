<?php
require_once __DIR__ . "/../../../mpdf/src/Autoloader.php";
\Mpdf\Autoloader::register();

use Mpdf\Mpdf;

$mpdf = new Mpdf([
    'mode' => 'utf-8',
    'default_font' => 'kanit',
    'fontDir' => [
        __DIR__ . '/../../../assets/fonts'
    ],
    'fontdata' => [
        'kanit' => [
            'R' => 'Kanit-Regular.ttf',
            'B' => 'Kanit-Bold.ttf',
        ]
    ],
]);

$html = '
<h2 style="text-align:center">รายงานรายได้จากการสมัครแพ็กเกจ</h2>
<p>ทดสอบภาษาไทย: ร้านซักอบรีด, รายได้, แพ็กเกจ</p>
';

$mpdf->WriteHTML($html);
$mpdf->Output("test.pdf", "I");
