<?php
// ... ส่วน AUTH และดึงข้อมูลคงเดิมตามที่คุณเขียนไว้ ...
$staff_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("
    SELECT
        p.id, p.pickup_address, p.lat, p.lng, p.status,
        o.order_number, u.display_name AS customer_name, u.phone AS customer_phone
    FROM pickups p
    JOIN orders o ON p.order_id = o.id
    JOIN users u ON o.customer_id = u.id
    WHERE p.assigned_to = ? AND DATE(p.scheduled_at) = CURDATE()
");
$stmt->execute([$staff_id]);
$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Route Map - Laundry Delivery</title>
    <link href="../bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <style>
        :root {
            --primary-blue: #007bff;
            --glass-bg: rgba(255, 255, 255, 0.9);
        }

        body {
            background: #f0f5fa;
            font-family: 'Kanit', sans-serif;
            overflow: hidden;
        }

        /* แผนที่เต็มจอ */
        #map {
            height: 100vh;
            width: 100%;
            position: absolute;
            top: 0;
            left: 0;
            z-index: 1;
        }

        /* Overlay Header */
        .map-overlay-header {
            position: absolute;
            top: 20px;
            left: 15px;
            right: 15px;
            z-index: 10;
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        /* Task List Bottom Sheet */
        .task-sheet {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            z-index: 10;
            background: #fff;
            border-radius: 30px 30px 0 0;
            max-height: 40vh;
            overflow-y: auto;
            box-shadow: 0 -10px 25px rgba(0, 0, 0, 0.1);
            padding: 20px;
        }

        .handle-bar {
            width: 40px;
            height: 5px;
            background: #dee2e6;
            border-radius: 10px;
            margin: -5px auto 15px auto;
        }

        .route-card {
            border: 1px solid #f0f0f0;
            border-radius: 15px;
            padding: 12px;
            margin-bottom: 10px;
            transition: 0.3s;
        }

        .route-card:active {
            background: #f8fbff;
        }

        /* Floating Nav Button */
        .btn-nav-float {
            width: 45px;
            height: 45px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #28a745;
            color: white;
            text-decoration: none;
            box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
        }

        .back-fab {
            position: absolute;
            top: 85px;
            right: 15px;
            z-index: 10;
            background: #fff;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            color: #666;
        }
    </style>
</head>

<body>

    <div class="map-overlay-header p-3 shadow-sm d-flex justify-content-between align-items-center">
        <div>
            <h6 class="fw-bold mb-0 text-primary"><i class="bi bi-geo-alt-fill me-1"></i> เส้นทางงานวันนี้</h6>
            <small class="text-muted">พบทั้งสิ้น <?= count($tasks) ?> จุดรับ-ส่ง</small>
        </div>
        <div class="badge bg-primary rounded-pill px-3 py-2">Staff Mode</div>
    </div>

    <a href="index.php?link=Home" class="back-fab text-decoration-none">
        <i class="bi bi-arrow-left"></i>
    </a>

    <div id="map"></div>

    <div class="task-sheet">
        <div class="handle-bar"></div>
        <?php if (!$tasks): ?>
            <div class="text-center py-4">
                <i class="bi bi-emoji-slight-smile text-muted h1"></i>
                <p class="text-muted">วันนี้ไม่มีคิวงานครับ พักผ่อนได้!</p>
            </div>
        <?php else: ?>
            <div class="row g-2">
                <?php foreach ($tasks as $i => $t): ?>
                    <div class="col-12">
                        <div class="route-card d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center">
                                <div class="bg-primary text-white rounded-circle me-3 d-flex align-items-center justify-content-center" style="width:35px; height:35px; font-weight:bold;">
                                    <?= $i + 1 ?>
                                </div>
                                <div>
                                    <div class="fw-bold text-dark" style="font-size: 0.9rem;"><?= htmlspecialchars($t['customer_name']) ?></div>
                                    <div class="text-muted small"><i class="bi bi-upc-scan"></i> <?= $t['order_number'] ?></div>
                                </div>
                            </div>
                            <a href="javascript:void(0)" onclick="navigateTo(<?= $t['lat'] ?>, <?= $t['lng'] ?>)" class="btn-nav-float">
                                <i class="bi bi-cursor-fill"></i>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://maps.googleapis.com/maps/api/js?key=YOUR_KEY"></script>
    <script>
        const tasks = <?= json_encode($tasks) ?>;
        let map, infoWindow;

        function initMap() {
            navigator.geolocation.getCurrentPosition(pos => {
                const staffPos = {
                    lat: pos.coords.latitude,
                    lng: pos.coords.longitude
                };

                map = new google.maps.Map(document.getElementById('map'), {
                    center: staffPos,
                    zoom: 14,
                    disableDefaultUI: true, // ปิดปุ่มรกๆ ของ Google
                    styles: [ /* สามารถเพิ่ม Map Style แบบ Minimal สีฟ้าขาวได้ที่นี่ */ ]
                });

                // Marker พนักงาน (Blue Dot)
                new google.maps.Marker({
                    position: staffPos,
                    map,
                    icon: {
                        path: google.maps.SymbolPath.CIRCLE,
                        scale: 10,
                        fillColor: "#0d6efd",
                        fillOpacity: 1,
                        strokeWeight: 3,
                        strokeColor: "#fff",
                    },
                    title: "ตำแหน่งของคุณ"
                });

                const bounds = new google.maps.LatLngBounds();
                bounds.extend(staffPos);

                tasks.forEach((t, i) => {
                    if (!t.lat || !t.lng) return;
                    const p = {
                        lat: parseFloat(t.lat),
                        lng: parseFloat(t.lng)
                    };
                    bounds.extend(p);

                    const marker = new google.maps.Marker({
                        position: p,
                        map,
                        label: {
                            text: (i + 1).toString(),
                            color: "white",
                            fontWeight: "bold"
                        },
                        icon: {
                            path: google.maps.SymbolPath.BACKWARD_CLOSED_ARROW,
                            scale: 6,
                            fillColor: "#00bfa5",
                            fillOpacity: 0.9,
                            strokeWeight: 2,
                            strokeColor: "#fff",
                        }
                    });

                    marker.addListener('click', () => {
                        if (infoWindow) infoWindow.close();
                        infoWindow = new google.maps.InfoWindow({
                            content: `<div class="p-2"><strong>${t.customer_name}</strong><br><small>${t.pickup_address}</small></div>`
                        });
                        infoWindow.open(map, marker);
                    });
                });

                // Auto-fit แผนที่ให้เห็นครบทุกจุด
                map.fitBounds(bounds);

            });
        }

        function navigateTo(lat, lng) {
            // ใช้ Google Maps Intent สำหรับมือถือ จะเปิดแอป Maps ทันที
            window.open(`https://www.google.com/maps/dir/?api=1&destination=${lat},${lng}&travelmode=driving`, '_blank');
        }

        window.onload = initMap;
    </script>
</body>

</html>