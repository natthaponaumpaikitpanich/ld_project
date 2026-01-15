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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="../../bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="../../bootstrap/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/style.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Kanit:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
<title>เพิ่มโปรโมชั่น</title>
</head>
<div class="container-fluid px-4 mt-4">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h3 class="fw-bold mb-0">📢 จัดการโปรโมชั่น</h3>
            <small class="text-muted">
                โปรโมชั่นทั้งหมดที่ใช้กับร้านในระบบ
            </small>
        </div>

        <a href="create.php" class="btn btn-primary">
            <i class="bi bi-plus-circle me-1"></i> เพิ่มโปรโมชั่นใหม่
        </a>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">

            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>ชื่อโปรโมชั่น</th>
                        <th>ช่วงเวลา</th>
                        <th>กลุ่มเป้าหมาย</th>
                        <th>สถานะ</th>
                        <th class="text-end" width="180">จัดการ</th>
                    </tr>
                </thead>

                <tbody>
                <?php if (empty($result)): ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">
                            ยังไม่มีโปรโมชั่นในระบบ
                        </td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($result as $row): ?>
                    <tr>
                        <td class="fw-semibold">
                            <?= htmlspecialchars($row['title']) ?>
                        </td>

                        <td>
                            <small class="text-muted">
                                <?= date('d/m/Y', strtotime($row['start_date'])) ?>
                                –
                                <?= date('d/m/Y', strtotime($row['end_date'])) ?>
                            </small>
                        </td>

                        <td>
                            <span class="badge bg-info-subtle text-dark">
                                <?= htmlspecialchars($row['audience']) ?>
                            </span>
                        </td>

                        <td>
                            <span class="badge bg-<?= 
                                $row['status'] === 'active'
                                ? 'success'
                                : ($row['status'] === 'draft' ? 'secondary' : 'danger')
                            ?>">
                                <?= htmlspecialchars($row['status']) ?>
                            </span>
                        </td>

                        <td class="text-end">
                            <a href="edit.php?id=<?= $row['id'] ?>"
                               class="btn btn-sm btn-outline-warning">
                                แก้ไข
                            </a>

                            <a href="delete.php?id=<?= $row['id'] ?>"
                               class="btn btn-sm btn-outline-danger"
                               onclick="return confirm('ต้องการลบโปรโมชั่นนี้หรือไม่?')">
                                ลบ
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>

            </table>

        </div>
    </div>
 <div class="d-flex mt-3">
        <a href="../sidebar/sidebar.php?link=Dashboard" class="btn btn-warning ms-auto">กลับไปหน้าแรก</a>
    </div>
</div>

   