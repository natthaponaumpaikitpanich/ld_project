<?php
include_once '../../assets/style.php';
// ตรวจสอบว่ามี id ส่งมารึยัง
if (!isset($_GET['id'])) {
    die("ไม่พบแพ็กเกจที่ต้องการแก้ไข");
}

$id = $_GET['id'];

// ดึงข้อมูลแพ็กเกจ
$stmt = $pdo->prepare("SELECT * FROM billing_plans WHERE id = ?");
$stmt->execute([$id]);
$plan = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$plan) {
    die("ไม่พบข้อมูลแพ็กเกจ");
}

// เมื่อกดบันทึก
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name']);
    $price = trim($_POST['price']);
    $duration = trim($_POST['duration']);
    $status = $_POST['status'];

    $sql = "UPDATE billing_plans 
            SET name=?, price=?, duration=?, status=?, updated_at=NOW()
            WHERE id=?";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$name, $price, $duration, $status, $id]);

    header("Location: ../sidebar.php?link=setting");
    exit;
}
?>



<head>
    <meta charset="UTF-8">
    <title>แก้ไขแพ็กเกจ</title>
</head>

<body style="margin-left:260px;">

    <div class="container mt-4">
        <h3>แก้ไขแพ็กเกจรายเดือน</h3>

        <div class="card shadow-sm mt-3">
            <div class="card-body">

                <form method="post">

                    <div class="mb-3">
                        <label class="form-label">ชื่อแพ็กเกจ</label>
                        <input type="text" name="name" class="form-control"
                            value="<?= htmlspecialchars($plan['name']) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">ราคา (บาท)</label>
                        <input type="number" name="price" class="form-control"
                            value="<?= htmlspecialchars($plan['price']) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">จำนวนวันที่ใช้งาน</label>
                        <input type="text" name="duration" class="form-control"
                            value="<?= htmlspecialchars($plan['duration']) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">สถานะ</label>
                        <select name="status" class="form-select">
                            <option value="active" <?= $plan['status'] == 'active' ? 'selected' : '' ?>>Active</option>
                            <option value="inactive" <?= $plan['status'] == 'inactive' ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </div>

                    <div class="text-end">
                        <a href="../sidebar.php?link=setting" class="btn btn-secondary">ย้อนกลับ</a>
                        <button class="btn btn-primary">บันทึกการแก้ไข</button>

                    </div>

                </form>

            </div>
        </div>
    </div>

    </body>

</html>