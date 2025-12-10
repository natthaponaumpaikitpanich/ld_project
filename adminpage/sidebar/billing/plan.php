<?php

// ดึงร้านค้าทั้งหมด
$stmt = $pdo->query("SELECT * FROM billing_plans ORDER BY price ASC");
$plans = $stmt->fetchAll();

?>
<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $store_name = $_POST['store_name'];
    $owner_id = $_POST['owner_id'];
    $plan_id = $_POST['billing_plan_id'];
    $address = $_POST['address'];

    $stmt = $pdo->prepare("
        INSERT INTO stores (
            id,
            owner_id,
            name,
            address,
            billing_plan_id,
            billing_start,
            billing_end
        )
        VALUES (
            UUID(),
            :owner,
            :name,
            :address,
            :plan,
            CURDATE(),
            DATE_ADD(CURDATE(), INTERVAL 1 MONTH)
        )
    ");

    $stmt->execute([
        ':owner' => $owner_id,
        ':name' => $store_name,
        ':address' => $address,
        ':plan' => $plan_id
    ]);

    header("Location: index.php");
    exit;
}
?>
<div class="container mt-4">
    <h3 class="fw-bold">💳 แพ็กเกจรายเดือน</h3>

    <a href="billing/plan_create.php" class="btn btn-primary mb-3">
        + เพิ่มแพ็กเกจ
    </a>

    <table class="table table-bordered">
        <thead class="table-dark">
            <tr>
                <th>ชื่อแพ็กเกจ</th>
                <th>ราคา/เดือน</th>
                <th>สถานะ</th>
                <th>จัดการ</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach($plans as $p): ?>
            <tr>
                <td><?= $p['name'] ?></td>
                <td><?= number_format($p['price'],2) ?> ฿</td>
                <td>
                    <span class="badge bg-<?= $p['status']=='active'?'success':'secondary' ?>">
                        <?= $p['status'] ?>
                    </span>
                </td>
                <td>
                    <a href="plan_edit.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-warning">แก้ไข</a>
                </td>
            </tr>
        <?php endforeach ?>
        </tbody>
    </table>
</div>
