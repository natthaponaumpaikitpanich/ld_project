<?php


$staff_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT
        p.id,
        p.pickup_address,
        p.lat,
        p.lng,
        o.order_number,
        u.display_name AS customer_name
    FROM pickups p
    JOIN orders o ON p.order_id = o.id
    JOIN users u ON o.customer_id = u.id
    WHERE p.assigned_to = ?
      AND p.status != 'completed'
");
$stmt->execute([$staff_id]);
$points = $stmt->fetchAll(PDO::FETCH_ASSOC); ?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="utf-8">
<title>แผนที่งานวันนี้</title>

<link rel="stylesheet"
      href="https://unpkg.com/leaflet/dist/leaflet.css"/>

<style>
#map {
    height: 85vh;
    border-radius: 12px;
}
</style>
</head>

<body style="margin:0;background:#f4f6f9">

<div class="container p-3">
    <h4 class="fw-bold mb-2">🗺️ เส้นทางงานวันนี้</h4>
    <div id="map"></div>
</div>
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

<script>
const map = L.map('map').setView([19.0297, 99.8973], 13); // พะเยา

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap'
}).addTo(map);

const points = <?= json_encode($points) ?>;

points.forEach(p => {

    if (!p.lat || !p.lng) return;

    const marker = L.marker([p.lat, p.lng]).addTo(map);

    marker.bindPopup(`
        <b>📦 ${p.order_number}</b><br>
        👤 ${p.customer_name}<br>
        📍 ${p.pickup_address}<br><br>

        <a href="https://www.google.com/maps/dir/?api=1&destination=${p.lat},${p.lng}"
           target="_blank"
           class="btn btn-sm btn-primary">
           🚗 นำทาง
        </a>
    `);
});
</script>

</body>
</html>
