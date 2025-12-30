<?php
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    die('ไม่มีสิทธิ์เข้าถึงหน้านี้');
}

$stmt = $pdo->prepare("
    SELECT 
        p.id AS pickup_id,
        p.order_id,
        p.status AS pickup_status,
        p.scheduled_at,
        o.order_number,
        o.status AS order_status,
        u.display_name AS customer_name
    FROM pickups p
    JOIN orders o ON p.order_id = o.id
    LEFT JOIN users u ON o.customer_id = u.id
    WHERE p.assigned_to = ?
      AND DATE(p.scheduled_at) = CURDATE()
    ORDER BY p.scheduled_at ASC
");
$stmt->execute([$_SESSION['user_id']]);
$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
</head>
<body>
<div class="container mt-3 mb-5">

<h5 class="fw-bold">📋 งานที่ต้องทำวันนี้</h5>

<?php if (!$tasks): ?>
<div class="alert alert-primary">ยังไม่มีงานวันนี้</div>
<?php endif; ?>

<?php foreach ($tasks as $task): ?>
<div class="card mb-3 shadow-sm">
<div class="card-body">

    <b><?= htmlspecialchars($task['customer_name'] ?? '-') ?></b><br>
    Order: <?= htmlspecialchars($task['order_number']) ?><br>
    <span class="badge bg-info"><?= htmlspecialchars($task['order_status']) ?></span>

    <!-- อัปเดตสถานะ -->
    <form method="post"
          action="menu/task/task_update_status.php"
          class="mt-2">

        <input type="hidden" name="pickup_id" value="<?= $task['pickup_id'] ?>">
        <input type="hidden" name="order_id" value="<?= $task['order_id'] ?>">

        <select name="next_status" class="form-select mb-2" required>
            <option value="">-- เลือกสถานะ --</option>
            <option value="picked_up">รับผ้า</option>
            <option value="in_process">ซัก</option>
            <option value="ready">อบ / พับ</option>
            <option value="out_for_delivery">กำลังส่ง</option>
            <option value="completed">ส่งสำเร็จ</option>
        </select>

        <button class="btn btn-success btn-sm w-100 mb-2">
            🔄 อัปเดตสถานะ
        </button>
    </form>

    <!-- ปุ่ม Scan QR -->
    <button class="btn btn-primary w-100"
            onclick="openQRScanner()">
        📷 Scan QR Code
    </button>

</div>
</div>
<?php endforeach; ?>

</div>

<!-- พื้นที่กล้อง (อยู่นอก loop เท่านั้น) -->
<div id="qr-reader" style="width:100%; max-width:400px; margin:20px auto;"></div>

<!-- Library -->
<script src="https://unpkg.com/html5-qrcode"></script>

<script>
function openQRScanner() {
    const qrReader = document.getElementById("qr-reader");
    qrReader.innerHTML = ""; // เคลียร์ก่อนเปิดใหม่

    const scanner = new Html5Qrcode("qr-reader");

    scanner.start(
        { facingMode: "environment" },
        { fps: 10, qrbox: 250 },
        (decodedText) => {
            scanner.stop();

            // decodedText = machine_id หรือ tag
            window.location.href =
                "../scan.php?machine_id=" +
                encodeURIComponent(decodedText);
        },
        (error) => {
            // ignore scan errors
        }
    );
}
</script>

<script src="../bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>

