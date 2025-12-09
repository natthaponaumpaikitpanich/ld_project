<?php
require "../../ld_db.php";

// ตรวจสอบว่ามี id ส่งมาหรือไม่
if (!isset($_GET['id'])) {
    die("ไม่พบ ID โปรโมชั่น");
}

$id = $_GET['id'];

// ดึงข้อมูลเดิมของโปรโมชั่น
$sql = "SELECT * FROM promotions WHERE id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);
$promotion = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$promotion) {
    die("ไม่พบโปรโมชั่นนี้");
}

// หากมีการกดปุ่มบันทึก
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $title = $_POST["title"];
    $discount = $_POST["discount"];
    $start_date = $_POST["start_date"];
    $end_date = $_POST["end_date"];
    $status = $_POST["status"];

    // เก็บชื่อไฟล์รูปเก่าไว้
    $old_image = $promotion["image"];

    // ตรวจสอบรูปใหม่ (ถ้ามี)
    if (!empty($_FILES["image"]["name"])) {
        $fileName = time() . "_" . basename($_FILES["image"]["name"]);
        $targetPath = "uploads/" . $fileName;

        // อัปโหลดรูปใหม่
        if (move_uploaded_file($_FILES["image"]["tmp_name"], $targetPath)) {
            $image = $fileName;
        } else {
            $image = $old_image; // ถ้าอัปโหลด fail ใช้รูปเก่า
        }
    } else {
        $image = $old_image; // ไม่มีรูปใหม่ → ใช้รูปเดิม
    }

    // อัปเดตข้อมูล
    $updateSql = "UPDATE promotions 
                  SET title=?, discount=?, start_date=?, end_date=?, image=?, status=? 
                  WHERE id=?";
        
    $stmt = $pdo->prepare($updateSql);
    $stmt->execute([$title, $discount, $start_date, $end_date, $image, $status, $id]);

    header("Location: index.php?updated=1");
    exit;
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>แก้ไขโปรโมชั่น</title>
    <link rel="stylesheet" href="../../bootstrap-5.3.3-dist/css/bootstrap.min.css">
</head>

<body class="bg-light">

<div class="container mt-5">
    <div class="card shadow-sm p-4">
        <h3 class="mb-4">✏️ แก้ไขโปรโมชั่น</h3>

        <form action="" method="POST" enctype="multipart/form-data">

            <!-- ชื่อโปรโมชั่น -->
            <div class="mb-3">
                <label class="form-label">ชื่อโปรโมชั่น</label>
                <input type="text" name="title" class="form-control" value="<?= $promotion['title'] ?>" required>
            </div>

            <!-- ส่วนลด -->
            <div class="mb-3">
                <label class="form-label">ส่วนลด (%)</label>
                <input type="number" name="discount" class="form-control" value="<?= $promotion['discount'] ?>" required>
            </div>

            <!-- วันที่เริ่ม -->
            <div class="mb-3">
                <label class="form-label">วันที่เริ่ม</label>
                <input type="datetime-local" name="start_date" class="form-control"
                       value="<?= $promotion['start_date'] ? date('Y-m-d\TH:i', strtotime($promotion['start_date'])) : '' ?>"
            </div>

            <!-- วันที่สิ้นสุด -->
            <div class="mb-3">
                <label class="form-label">วันที่สิ้นสุด</label>
                <input type="datetime-local" name="end_date" class="form-control"
                       value="<?= $promotion['end_date'] ? date('Y-m-d\TH:i', strtotime($promotion['end_date'])) : '' ?>"
required>
            </div>

            <!-- รูปภาพเดิม -->
            <div class="mb-3">
                <label class="form-label">รูปภาพเดิม</label><br>
                <img src="uploads/<?= $promotion['image'] ?>" width="200" class="rounded border">
            </div>

            <!-- อัปโหลดรูปใหม่ -->
            <div class="mb-3">
                <label class="form-label">อัปโหลดรูปใหม่ (ถ้าต้องการ)</label>
                <input type="file" name="image" class="form-control">
            </div>

            <!-- สถานะ -->
            <div class="mb-3">
                <label class="form-label">สถานะ</label>
                <select name="status" class="form-select">
                    <option value="active" <?= $promotion['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="draft" <?= $promotion['status'] === 'draft' ? 'selected' : '' ?>>Draft</option>
                </select>
            </div>

            <!-- ปุ่มบันทึก -->
            <button type="submit" class="btn btn-primary">บันทึกการแก้ไข</button>
            <a href="index.php" class="btn btn-secondary">กลับ</a>

        </form>
    </div>
</div>

</body>
</html>
