<?php
include_once '../../assets/style.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name     = trim($_POST['name']);
    $price    = (float)$_POST['price'];
    $amount   = (float)$_POST['amount'];
    $duration = trim($_POST['duration']);

    // ===== upload qr =====
    $qr_path = null;
    if (!empty($_FILES['qr_image']['name'])) {

        $dir = __DIR__ . '/uploads/';
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $ext = pathinfo($_FILES['qr_image']['name'], PATHINFO_EXTENSION);
        $filename = 'qr_' . time() . '.' . $ext;
        $target = $dir . $filename;

        if (move_uploaded_file($_FILES['qr_image']['tmp_name'], $target)) {
            $qr_path = 'billing/uploads/' . $filename;
        }
    }

    $stmt = $pdo->prepare("
        INSERT INTO billing_plans (name, price, amount, duration, qr_image, status)
        VALUES (?, ?, ?, ?, ?, 'active')
    ");
    $stmt->execute([
        $name,
        $price,
        $amount,
        $duration,
        $qr_path
    ]);

    header("Location: ../sidebar.php?link=setting");
    exit;
}
?>

<body style="margin-left:260px;">
<div class="container mt-5">

    <h3 class="fw-bold mb-4">💳 เพิ่มแพ็กเกจรายเดือน</h3>

    <form method="post" enctype="multipart/form-data">

        <div class="mb-3">
            <label class="form-label">ชื่อแพ็กเกจ</label>
            <input type="text" name="name" class="form-control" required>
        </div>

        <div class="mb-3">
            <label class="form-label">ราคาแพ็กเกจ (แสดง)</label>
            <input type="number" name="price" class="form-control" required>
        </div>

        <div class="mb-3">
            <label class="form-label">จำนวนเงินที่ต้องโอนจริง</label>
            <input type="number" name="amount" class="form-control" required>
        </div>

        <div class="mb-3">
            <label class="form-label">รายละเอียด</label>
            <textarea name="duration" class="form-control"></textarea>
        </div>

        <div class="mb-3">
            <label class="form-label">QR Code (ของแอดมิน)</label>
            <input type="file" name="qr_image" class="form-control" accept="image/*" required>
        </div>

        <button class="btn btn-success px-4">บันทึกแพ็กเกจ</button>

    </form>

</div>
</body>
