<?php

include_once '../assets/style.php';
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // ===== สร้าง UUID =====
    $id = bin2hex(random_bytes(16)); // 32 chars (พอสำหรับ CHAR(35))

    $title = $_POST['title'];
    $discount = $_POST['discount'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];

    // ===== Upload รูป =====
    $image = null;
    if (!empty($_FILES['image']['name'])) {

        $targetDir = "uploads/";
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        $fileName = time() . "_" . basename($_FILES["image"]["name"]);
        $targetFilePath = $targetDir . $fileName;

        move_uploaded_file($_FILES["image"]["tmp_name"], $targetFilePath);
        $image = $fileName;
    }

    // ===== INSERT =====
    $sql = $pdo->prepare("
        INSERT INTO promotions
        (id, title, discount, image, start_date, end_date)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    $sql->execute([
        $id,
        $title,
        $discount,
        $image,
        $start_date,
        $end_date
    ]);

    echo "<script>
        alert('เพิ่มโปรโมชั่นสำเร็จ!');
        window.location='index.php';
    </script>";
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>เพิ่มโปรโมชั่นใหม่</title>
    <link rel="stylesheet" href="../../bootstrap/css/bootstrap.min.css">
</head>

<body class="bg-light">

    <div class="container py-5">

        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h4 class="m-0">เพิ่มโปรโมชั่นใหม่</h4>
            </div>

            <div class="card-body">

                <form action="" method="POST" enctype="multipart/form-data" class="row g-3">

                    <div class="col-md-12">
                        <label class="form-label">ชื่อโปรโมชั่น</label>
                        <input type="text" name="title" class="form-control" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">ส่วนลด (%)</label>
                        <input type="number" name="discount" class="form-control" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">รูปภาพโปรโมชั่น</label>
                        <input type="file" name="image" class="form-control">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">วันที่เริ่ม</label>
                        <input type="date" name="start_date" class="form-control" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">วันที่สิ้นสุด</label>
                        <input type="date" name="end_date" class="form-control" required>
                    </div>

                    <div class="col-12 text-end mt-4">
                        <a href="index.php" class="btn btn-danger">ยกเลิก</a>
                        <button type="submit" class="btn btn-primary">บันทึกโปรโมชั่น</button>
                    </div>

                </form>
            </div>
        </div>

    </div>

    <!-- Bootstrap Script -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>