<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>Scan QR</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
body {
    margin: 0;
    background: #000;
    color: #fff;
    text-align: center;
}
#reader {
    width: 100%;
}
.top-text {
    padding: 12px;
}
</style>
</head>
<body>

<div class="top-text">
    <h4>📷 สแกน QR เครื่องซัก</h4>
    <p>เล็งกล้องไปที่ QR Code</p>
</div>

<div id="reader"></div>

<script src="https://unpkg.com/html5-qrcode"></script>
<script>
const qr = new Html5Qrcode("reader");
qr.start(
    { facingMode: "environment" },
    { fps: 10, qrbox: 250 },
    (text) => {
        qr.stop();
        window.location.href = text;
    }
);
</script>

</body>
</html>
