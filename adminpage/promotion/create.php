    <?php
    session_start();
    require_once "../../ld_db.php";

    /* ===== AUTH ===== */
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'platform_admin') {
        die('no permission');
    }

    $admin_id = $_SESSION['user_id'];

    /* ===== STORES ===== */
    $stmt = $pdo->prepare("
        SELECT id, name
        FROM stores
        WHERE status = 'active'
        ORDER BY name
    ");
    $stmt->execute();
    $stores = $stmt->fetchAll(PDO::FETCH_ASSOC);

    /* ===== SUBMIT ===== */
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        $id = sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0,0xffff), mt_rand(0,0xffff),
            mt_rand(0,0xffff),
            mt_rand(0,0x0fff) | 0x4000,
            mt_rand(0,0x3fff) | 0x8000,
            mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0xffff)
        );

        $title      = trim($_POST['title']);
        $summary    = trim($_POST['summary']);
        $message    = trim($_POST['message']);
        $discount   = (int)$_POST['discount'];
        $audience   = $_POST['audience']; // stores | store_specific
        $store_id   = $_POST['store_id'] ?: null;
        $status     = $_POST['status'];
        $start_date = $_POST['start_date'];
        $end_date   = $_POST['end_date'];

        if ($audience === 'store_specific' && !$store_id) {
            die('กรุณาเลือกร้านสำหรับโปรโมชั่นร้านเฉพาะ');
        }

        /* ===== UPLOAD IMAGE ===== */
        $imagePath = null;
        if (!empty($_FILES['image']['name'])) {

            $dir = "../../uploads/promotion/";
            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }

            $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $filename = uniqid('promo_') . '.' . $ext;

            if (move_uploaded_file($_FILES['image']['tmp_name'], $dir . $filename)) {
                $imagePath = "uploads/promotion/" . $filename;
            }
        }

        /* ===== INSERT ===== */
        $stmt = $pdo->prepare("
            INSERT INTO promotions (
                id,
                created_by,
                store_id,
                title,
                discount,
                summary,
                message,
                image,
                start_date,
                end_date,
                status,
                audience
            ) VALUES (
                ?,?,?,?,?,?,?,?,?,?,?,?
            )
        ");

        $stmt->execute([
            $id,
            $admin_id,
            $store_id,
            $title,
            $discount,
            $summary,
            $message,
            $imagePath,
            $start_date,
            $end_date,
            $status,
            $audience
        ]);

        header("Location: index.php?success=1");
        exit;
    }
    ?>
    <!DOCTYPE html>
    <html lang="th">
    <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="../../bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/style.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;600&display=swap" rel="stylesheet">
    <title>เพิ่มโปรโมชั่น</title>
    <link rel="icon" href="../../image/3.jpg">

    </head>

    <body>

   <div class="container-fluid px-4 py-4">

<div class="card shadow-sm border-0">

    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">
            📢 สร้างโปรโมชั่นใหม่ (Platform Admin)
        </h5>
    </div>

    <div class="card-body">

        <form method="post" enctype="multipart/form-data" class="row g-4">

            <div class="col-12">
                <label class="form-label fw-semibold">ชื่อโปรโมชั่น</label>
                <input type="text" name="title" class="form-control" required>
            </div>

            <div class="col-md-4">
                <label class="form-label">ส่วนลด / สิทธิพิเศษ</label>
                <input type="number" name="discount" class="form-control" value="0">
            </div>

            <div class="col-md-4">
                <label class="form-label">กลุ่มร้านค้า</label>
                <select name="audience" class="form-select">
                    <option value="stores">ร้านค้าทั้งหมด</option>
                    <option value="store_specific">ร้านค้าเฉพาะ</option>
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label">สถานะ</label>
                <select name="status" class="form-select">
                    <option value="active">เปิดใช้งาน</option>
                    <option value="inactive">ปิด</option>
                    <option value="draft">ร่าง</option>
                </select>
            </div>

            <div class="col-md-6">
                <label class="form-label">เลือกร้าน (ถ้าเป็นร้านเฉพาะ)</label>
                <select name="store_id" class="form-select">
                    <option value="">-- ไม่ระบุ --</option>
                    <?php foreach ($stores as $s): ?>
                        <option value="<?= $s['id'] ?>">
                            <?= htmlspecialchars($s['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-6">
                <label class="form-label">รูปโปรโมชั่น</label>
                <input type="file" name="image" class="form-control">
            </div>

            <div class="col-md-6">
                <label class="form-label">วันที่เริ่ม</label>
                <input type="datetime-local" name="start_date" class="form-control" required>
            </div>

            <div class="col-md-6">
                <label class="form-label">วันที่สิ้นสุด</label>
                <input type="datetime-local" name="end_date" class="form-control" required>
            </div>

            <div class="col-12">
                <label class="form-label">สรุปสั้น</label>
                <input type="text" name="summary" class="form-control">
            </div>

            <div class="col-12">
                <label class="form-label">รายละเอียดถึงร้านค้า</label>
                <textarea name="message" rows="4" class="form-control"></textarea>
            </div>

            <div class="col-12 text-end mt-3">
                <a href="index.php" class="btn btn-secondary">
                    ยกเลิก
                </a>
                <button class="btn btn-primary">
                    บันทึกโปรโมชั่น
                </button>
            </div>

        </form>

    </div>
</div>

</div>

    <script src="../../bootstrap/js/bootstrap.bundle.min.js"></script>
    </body>
    </html>
