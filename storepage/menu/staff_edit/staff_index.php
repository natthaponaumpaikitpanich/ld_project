<?php
$store_id = $_SESSION['store_id'] ?? null;
if (!$store_id) {
    die("ไม่พบร้าน");
}

/* ---------- ดึงพนักงาน ---------- */
$stmt = $pdo->prepare("
    SELECT ss.id AS staff_id,
           u.display_name,
           u.email,
           u.phone,
           ss.role,
           ss.created_at
    FROM store_staff ss
    JOIN users u ON ss.user_id = u.id
    WHERE ss.store_id = ?
");
$stmt->execute([$store_id]);
$staffs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mt-4">

    <h4>👥 จัดการพนักงานร้าน</h4>

    <a href="menu/staff_edit/staff_add.php" class="btn btn-primary mb-3">
        ➕ เพิ่มพนักงาน
    </a>

    <div class="card">
        <div class="card-body">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>ชื่อ</th>
                        <th>Email</th>
                        <th>เบอร์</th>
                        <th>บทบาท</th>
                        <th>วันที่เพิ่ม</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>

                <?php if (empty($staffs)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted">
                            ยังไม่มีพนักงาน
                        </td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($staffs as $s): ?>
                    <tr>
                        <td><?= htmlspecialchars($s['display_name']) ?></td>
                        <td><?= htmlspecialchars($s['email']) ?></td>
                        <td><?= htmlspecialchars($s['phone']) ?></td>
                        <td>
                            <span class="badge bg-<?= $s['role']=='store_owner'?'success':'info' ?>">
                                <?= $s['role'] ?>
                            </span>
                        </td>
                        <td><?= date('d/m/Y', strtotime($s['created_at'])) ?></td>
                        <td>
                            <?php if ($s['role'] !== 'store_owner'): ?>
                                <a href="menu/staff_edit/staff_delete.php?id=<?= $s['staff_id'] ?>"
                                   class="btn btn-sm btn-danger"
                                   onclick="return confirm('ลบพนักงานคนนี้?')">
                                   ลบ
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>

                </tbody>
            </table>
        </div>
    </div>
</div>
