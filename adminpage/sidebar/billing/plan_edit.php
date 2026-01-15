<?php
include "../../../ld_db.php";

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

    $name     = trim($_POST['name']);
    $price    = trim($_POST['price']);
    $duration = trim($_POST['duration']);
    $status   = $_POST['status'];

    $sql = "UPDATE billing_plans 
            SET name=?, price=?, duration=?, status=?, updated_at=NOW()
            WHERE id=?";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$name, $price, $duration, $status, $id]);

    header("Location: ../sidebar.php?link=setting");
    exit;
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>แก้ไขแพ็กเกจรายเดือน</title>

<link href="../../../bootstrap/css/bootstrap.min.css" rel="stylesheet">
<link href="../../../bootstrap/bootstrap-icons.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;600&display=swap" rel="stylesheet">
<link rel="icon" href="../../../image/3.jpg">

<style>
body {
    background:#f4f6f9;
    font-family:'Kanit',sans-serif;
}

/* layout */
.main-wrapper {
    max-width: 900px;
    margin: 40px auto;
}

/* card */
.card {
    border-radius: 18px;
}

/* section */
.section-title {
    font-weight: 600;
    color: #0f172a;
    margin-bottom: 4px;
}

.section-desc {
    font-size: 14px;
    color: #64748b;
}

/* badge plan */
.plan-badge {
    background:#e0f2fe;
    color:#0369a1;
    padding:6px 12px;
    border-radius:999px;
    font-size:14px;
    font-weight:500;
    display:inline-block;
}

/* footer */
.form-footer {
    background:#f8fafc;
    padding:16px;
    border-top:1px solid #e5e7eb;
    border-radius:0 0 18px 18px;
}
</style>
</head>

<body>

<div class="main-wrapper">

<div class="card shadow-sm border-0">

    <!-- HEADER -->
    <div class="card-header bg-primary text-light py-3">
        <h4 class="mb-1">✏️ แก้ไขแพ็กเกจรายเดือน</h4>
        <small>
            กำลังแก้ไขแพ็กเกจ:
            <span class="plan-badge">
                <?= htmlspecialchars($plan['name']) ?>
            </span>
        </small>
    </div>

    <!-- BODY -->
    <div class="card-body px-4 py-4">

        <form method="post" class="row g-4">

            <!-- BASIC -->
            <div class="col-12">
                <div class="section-title">ข้อมูลแพ็กเกจ</div>
                <div class="section-desc">
                    ข้อมูลนี้จะถูกแสดงให้ร้านค้าเห็นในระบบ
                </div>
            </div>

            <div class="col-md-6">
                <label class="form-label">ชื่อแพ็กเกจ</label>
                <input type="text" name="name" class="form-control"
                       value="<?= htmlspecialchars($plan['name']) ?>" required>
            </div>

            <div class="col-md-3">
                <label class="form-label">ราคา (บาท)</label>
                <input type="number" name="price" class="form-control"
                       value="<?= htmlspecialchars($plan['price']) ?>" required>
            </div>

            <div class="col-md-3">
                <label class="form-label">สถานะ</label>
                <select name="status" class="form-select">
                    <option value="active" <?= $plan['status']=='active'?'selected':'' ?>>
                        Active
                    </option>
                    <option value="inactive" <?= $plan['status']=='inactive'?'selected':'' ?>>
                        Inactive
                    </option>
                </select>
            </div>

            <div class="col-12">
                <label class="form-label">รายละเอียด / ระยะเวลาการใช้งาน</label>
                <textarea name="duration" rows="3" class="form-control"
                    placeholder="เช่น ใช้งานได้ 30 วัน, รองรับ X ร้าน">
<?= htmlspecialchars($plan['duration']) ?>
                </textarea>
            </div>

            <!-- FOOTER -->
            <div class="col-12 form-footer d-flex justify-content-between align-items-center mt-4">
                <a href="../sidebar.php?link=setting" class="btn btn-outline-secondary">
                    ย้อนกลับ
                </a>
                <button class="btn btn-warning px-4">
                    <i class="bi bi-save me-1"></i> บันทึกการแก้ไข
                </button>
            </div>

        </form>

    </div>
</div>

</div>

<script src="../../../bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
