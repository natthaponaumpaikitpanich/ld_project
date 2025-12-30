<!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link href="../../../bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">   
<link rel="icon" href="../../../image/3.jpg">
<title>ระบบร้านซักอบรีด</title>
    </head>
    <style>
        body {
            font-family: 'Kanit', sans-serif;
        }
    </style>
    <body>
        <div class="d-flex justify-content-center align-items-center mt-5">
        <?php
        require_once "../../../ld_db.php";
$id = $_GET['id'];

$stmt = $pdo->prepare("SELECT * FROM machines WHERE id = ?");
$stmt->execute([$id]);
$machine = $stmt->fetch();

$qr_url = "https://yourdomain.com/scan.php?code=".$machine['qr_code'];
?>

<img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=<?= urlencode($qr_url) ?>">
</div>
<div class="d-flex justify-content-center align-items-center mt-3">
    <a href="../../index.php?link=qrcode">
<button class="btn btn-danger">กลับไปหน้าหลัก</button></a>
</div>

        <script src="../../../bootstrap/js/bootstrap.bundle.min.js"></script>
    </body>
    </html>



