<?php
require_once "../../ld_db.php";

// ดึงรายการโปรโมชั่นทั้งหมด (PDO)
$stmt = $pdo->prepare("
    SELECT *
    FROM promotions
    ORDER BY created_at DESC
");
$stmt->execute();
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<head>
    <meta charset="UTF-8">
    <link rel="icon" href="../../image/3.jpg">
    </link>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="../../bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="../../bootstrap/bootstrap-icons.css" rel="stylesheet">

    
</head>
<?php
include_once '../assets/style.php'; ?>
<div class="container mt-4">

    <div class="d-flex justify-content-between align-items-center">
        <h3 class="fw-bold">📢 จัดการโปรโมชั่น</h3>
        <a href="create.php" class="btn btn-primary">➕ เพิ่มโปรโมชั่นใหม่</a>
    </div>

    <div class="card mt-3">
        <div class="card-body">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>ชื่อโปรโมชั่น</th>
                        <th>ช่วงเวลา</th>
                        <th>สถานะ</th>
                        <th width="180">จัดการ</th>
                    </tr>
                </thead>
                <tbody>

                    <?php foreach ($result as $row) { ?>
                        <tr>
                            <td><?= $row['title'] ?></td>
                            <td>
                                <?= $row['start_date'] ?> - <?= $row['end_date'] ?>
                            </td>
                            <td>
                                <span class="badge bg-<?= $row['status'] == 'active' ? 'success' : 'secondary' ?>">
                                    <?= $row['status'] ?>
                                </span>
                            </td>
                            <td>
                                <a class="btn btn-sm btn-warning" href="edit.php?id=<?= $row['id'] ?>">แก้ไข</a>
                                <a class="btn btn-sm btn-danger" href="delete.php?id=<?= $row['id'] ?>"
                                    onclick="return confirm('ต้องการลบหรือไม่?')">
                                    ลบ
                                </a>
                            </td>
                        </tr>
                    <?php } ?>

                </tbody>
            </table>
        </div>
    </div>
</div>
<div class="container mt-4">
    <div class="d-flex">
        <a href="../sidebar/sidebar.php?link=Dashboard" class="btn btn-primary ms-auto">กลับไปหน้าแรก</a>
    </div>