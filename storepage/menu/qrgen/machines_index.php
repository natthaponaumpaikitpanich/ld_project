<?php


if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'store_owner') {
    die('ไม่มีสิทธิ์เข้าถึงหน้านี้');
}

$store_id = $_SESSION['store_id'] ?? null;
if (!$store_id) {
    die("ไม่พบร้านค้า");
}

/* ---------- ดึงเครื่องทั้งหมดของร้าน ---------- */
$stmt = $pdo->prepare("
    SELECT *
    FROM machines
    WHERE store_id = ?
    ORDER BY machine_no ASC
");
$stmt->execute([$store_id]);
$machines = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
</head>
<body>

<div class="container mt-4">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4>🧺 เครื่องซักผ้าในร้าน</h4>

        <!-- ปุ่มสร้างเครื่อง -->
        <button class="btn btn-primary"
                data-bs-toggle="modal"
                data-bs-target="#addMachineModal">
            ➕ เพิ่มเครื่อง
        </button>
    </div>

    <?php if (!$machines): ?>
        <div class="alert alert-warning">
            ยังไม่มีเครื่องในร้าน
        </div>
    <?php else: ?>
        <div class="row">
            <?php foreach ($machines as $m): ?>
                <div class="col-md-4">
                    <div class="card mb-3 shadow-sm">
                        <div class="card-body">

                            <h5><?= htmlspecialchars($m['machine_name']) ?></h5>

                            <p class="mb-1">
                                เครื่องที่: <b><?= $m['machine_no'] ?></b>
                            </p>

                            <span class="badge 
                                <?= $m['status'] === 'available' ? 'bg-success' : 
                                   ($m['status'] === 'in_use' ? 'bg-warning' : 'bg-danger') ?>">
                                <?= $m['status'] ?>
                            </span>

                            <?php if ($m['qr_code']): ?>
                                <div class="mt-3 text-center">
                                    <img src="<?= $m['qr_code'] ?>" width="120">
                                </div>
                                
                            <?php endif; ?>
                            <div class="d-flex justify-content-end">
<a href="menu/qrgen/qrcode_view.php?id=<?= $m['id'] ?>" class="btn btn-sm btn-success ">
                📎 QR Code
            </a></div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>

<!-- ===== Modal เพิ่มเครื่อง ===== -->
<div class="modal fade" id="addMachineModal">
  <div class="modal-dialog">
    <form method="post"
          action="menu/qrgen/machine_store.php"
          class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title">➕ เพิ่มเครื่องซักผ้า</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">

        <div class="mb-3">
            <label class="form-label">ชื่อเครื่อง</label>
            <input type="text"
                   name="machine_name"
                   class="form-control"
                   required>
        </div>

        <div class="mb-3">
            <label class="form-label">เครื่องที่ (หมายเลข)</label>
            <input type="number"
                   name="machine_no"
                   class="form-control"
                   required>
        </div>

      </div>

      <div class="modal-footer">
        <button class="btn btn-success">
            บันทึก
        </button>
      </div>

    </form>
    
  </div>
</div>

<script src="../../bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
