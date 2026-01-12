<?php
session_start();
require_once "../ld_db.php";

/* ===== AUTH ===== */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    die('no permission');
}

$staff_id = $_SESSION['user_id'];

/* ===== ดึงงานวันนี้ของ staff ===== */
$stmt = $pdo->prepare("
    SELECT
        p.id,
        p.pickup_address,
        p.lat,
        p.lng,
        p.status,
        o.order_number,
        u.display_name AS customer_name
    FROM pickups p
    JOIN orders o ON p.order_id = o.id
    JOIN users u ON o.customer_id = u.id
    WHERE p.assigned_to = ?
      AND DATE(p.scheduled_at) = CURDATE()
");
$stmt->execute([$staff_id]);
$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>แผนที่งานวันนี้</title>

<link href="../bootstrap/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

<style>
body { background:#f4f6f9; }
#map { height: 75vh; width:100%; border-radius:12px; }
.map-header {
    background:linear-gradient(135deg,#0d6efd,#20c997);
    color:#fff;
    border-radius:16px;
}
</style>
</head>

<body>

<div class="container py-3">

    <div class="map-header p-3 mb-3 shadow-sm">
        <h5 class="fw-bold mb-0">🗺️ แผนที่งานของวันนี้</h5>
        <small class="opacity-75">
            <?= count($tasks) ?> งาน
        </small>
    </div>

    <?php if (!$tasks): ?>
        <div class="alert alert-info">
            วันนี้ยังไม่มีงานรับ–ส่ง
        </div>
    <?php else: ?>
        <div id="map"></div>
    <?php endif; ?>

    <a href="index.php?link=Home" class="btn btn-outline-secondary mt-3">
        ← กลับหน้าหลัก
    </a>

</div>

<script src="https://maps.googleapis.com/maps/api/js?key=YOUR_GOOGLE_MAP_KEY"></script>

<script>
const tasks = <?= json_encode($tasks, JSON_UNESCAPED_UNICODE) ?>;

function initMap() {

    if (!navigator.geolocation) {
        alert("อุปกรณ์ไม่รองรับ GPS");
        return;
    }

    navigator.geolocation.getCurrentPosition(pos => {

        const staffPos = {
            lat: pos.coords.latitude,
            lng: pos.coords.longitude
        };

        const map = new google.maps.Map(document.getElementById('map'), {
            center: staffPos,
            zoom: 13
        });

        /* ===== Marker พนักงาน ===== */
        new google.maps.Marker({
            position: staffPos,
            map,
            icon: {
                url: "https://maps.google.com/mapfiles/ms/icons/blue-dot.png"
            },
            title: "ตำแหน่งของคุณ"
        });

        /* ===== Marker งานแต่ละจุด ===== */
        tasks.forEach((t, i) => {

            if (!t.lat || !t.lng) return;

            const pos = {
                lat: parseFloat(t.lat),
                lng: parseFloat(t.lng)
            };

            const marker = new google.maps.Marker({
                position: pos,
                map,
                label: (i+1).toString()
            });

            const info = `
                <b>Order:</b> ${t.order_number}<br>
                <b>ลูกค้า:</b> ${t.customer_name}<br>
                <b>สถานะ:</b> ${t.status}<br>
                <button class="btn btn-sm btn-success mt-2"
                    onclick="navigateTo(${t.lat},${t.lng})">
                    🚗 นำทาง
                </button>
            `;

            const infoWindow = new google.maps.InfoWindow({
                content: info
            });

            marker.addListener('click', () => {
                infoWindow.open(map, marker);
            });
        });

    }, () => {
        alert("ไม่สามารถเข้าถึงตำแหน่งปัจจุบันได้");
    });
}

function navigateTo(lat, lng) {
    navigator.geolocation.getCurrentPosition(pos => {
        const url =
            `https://www.google.com/maps/dir/?api=1` +
            `&origin=${pos.coords.latitude},${pos.coords.longitude}` +
            `&destination=${lat},${lng}` +
            `&travelmode=driving`;
        window.open(url,'_blank');
    });
}

initMap();
</script>

</body>
</html>
