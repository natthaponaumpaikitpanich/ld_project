<?php

$user_id = $_SESSION['user_id'];


$stmt = $pdo->prepare("
    SELECT 
        s.name AS staff_name,
        s.address,
        s.phone,
        s.timezone,
        u.email
    FROM staff_staff s
    LEFT JOIN users u ON s.user_id = u.id
    WHERE s.id = ?
");
$stmt->execute([$staff_id]);
$staff = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$staff) {
    echo "ไม่พบข้อมูลร้าน";
    exit;
}

/* UPDATE */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['staff_name'];
    $address = $_POST['address'];
    $phone = $_POST['phone'];
    $timezone = $_POST['timezone'];

    $update = $pdo->prepare("
        UPDATE staff
        SET name = ?, address = ?, phone = ?, timezone = ?
        WHERE id = ?
    ");
    $update->execute([$name, $address, $phone, $timezone, $staff_id]);

    echo "<script>alert('บันทึกข้อมูลสำเร็จ');location.href='index.php?link=profile';</script>";
}
?>
<div class="container mt-4">
    <div class="card shadow">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">✏️ แก้ไขโปรไฟล์ร้าน</h5>
        </div>

        <div class="card-body">
            <form method="post">

                <div class="mb-3">
                    <label class="form-label">ชื่อร้าน</label>
                    <input type="text" name="staff_name" class="form-control"
                        value="<?= htmlspecialchars($staff['staff_name']) ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">ที่อยู่ร้าน</label>
                    <textarea name="address" class="form-control" rows="3"><?= htmlspecialchars($staff['address']) ?></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">เบอร์โทร</label>
                    <input type="text" name="phone" class="form-control"
                        value="<?= htmlspecialchars($staff['phone']) ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">Timezone</label>
                    <select name="timezone" class="form-select">
                        <option value="Asia/Bangkok" <?= $staff['timezone']=='Asia/Bangkok'?'selected':'' ?>>
                            Asia/Bangkok
                        </option>
                        <option value="UTC" <?= $staff['timezone']=='UTC'?'selected':'' ?>>
                            UTC
                        </option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">อีเมลเจ้าของร้าน</label>
                    <input type="email" class="form-control" value="<?= htmlspecialchars($staff['email']) ?>" disabled>
                </div>

                <div class="text-end">
                    <a href="index.php?link=profile" class="btn btn-secondary">ย้อนกลับ</a>
                    <button class="btn btn-primary">บันทึก</button>
                </div>

            </form>
        </div>
    </div>
</div>