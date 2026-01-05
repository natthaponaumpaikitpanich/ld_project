<?php

/* ---------- จำนวนร้านทั้งหมด ---------- */
$stmt = $pdo->prepare("SELECT COUNT(*) FROM stores");
$stmt->execute();
$total_stores = $stmt->fetchColumn();

/* ---------- ร้านที่ active ---------- */
$stmt = $pdo->prepare("SELECT COUNT(*) FROM stores WHERE status = 'active'");
$stmt->execute();
$active_stores = $stmt->fetchColumn();

/* ---------- ร้านค้างจ่าย ---------- */
$stmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM store_subscriptions 
    WHERE status != 'active'
");
$stmt->execute();
$overdue_stores = $stmt->fetchColumn();

/* ---------- รายได้เดือนปัจจุบัน ---------- */
$stmt = $pdo->prepare("
    SELECT IFNULL(SUM(amount),0)
    FROM payments
    WHERE status = 'success'
      AND MONTH(paid_at) = MONTH(CURDATE())
      AND YEAR(paid_at) = YEAR(CURDATE())
");
$stmt->execute();
$monthly_revenue = $stmt->fetchColumn();

/* ---------- รายการร้านค้างจ่าย ---------- */
$stmt = $pdo->prepare("
    SELECT 
        s.name AS store_name, 
        ss.plan, 
        ss.monthly_fee, 
        ss.start_date
    FROM store_subscriptions ss
    JOIN stores s ON ss.store_id = s.id
    WHERE ss.status != 'active'
    ORDER BY ss.start_date ASC
    LIMIT 5
");
$stmt->execute();
$overdue_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<?php

?>
<div class="container mt-4">

    <h2 class="mb-4 fw-bold">Dashboard ผู้ดูแลระบบ (Platform Admin)</h2>

    <!-- Summary Cards -->
    <div class="row g-3">

        <div class="col-md-3">
            <div class="card text-bg-primary p-3">
                <h5>ร้านค้าทั้งหมด</h5>
                <h2><?= $total_stores ?></h2>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card text-bg-success p-3">
                <h5>ร้านที่ Active</h5>
                <h2><?= $active_stores ?></h2>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card text-bg-warning p-3">
                <h5>ร้านค้างจ่าย</h5>
                <h2><?= $overdue_stores ?></h2>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card text-bg-dark p-3">
                <h5>รายได้เดือนนี้</h5>
                <h2><?= number_format($monthly_revenue, 2) ?> ฿</h2>
            </div>
        </div>

    </div>

    <!-- Action Buttons -->
    <div class="mt-4 d-flex gap-3">
        <a href="sidebar.php?link=allstore" class="btn btn-primary btn-lg">
            🏪 จัดการร้านค้า
        </a>

        <a href="../promotion/index.php" class="btn btn-success btn-lg">
            📣 ประกาศโปรโมชั่นระบบ
        </a>
    </div>

</div>
<div class="card mt-4">
    <div class="card-header fw-bold">
        ร้านค้าที่ค้างชำระ (ล่าสุด)
    </div>
    <div class="card-body">

        <table class="table table-bordered table-striped">
            <thead class="table-light">
                <tr>
                    <th>ชื่อร้าน</th>
                    <th>แพ็กเกจ</th>
                    <th>ค่าบริการ/เดือน</th>
                    <th>เริ่มใช้งาน</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($overdue_list as $row) { ?>
                    <tr>
                        <td><?= $row['store_name'] ?></td>
                        <td><?= $row['plan'] ?></td>
                        <td><?= number_format($row['monthly_fee'], 2) ?> ฿</td>
                        <td><?= date('d/m/Y', strtotime($row['start_date'])) ?></td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>

    </div>
</div>
<div class="card mt-4">
    <div class="card-body">
        <h5 class="fw-bold">สรุประบบวันนี้</h5>
        <ul>
            <li>ร้านที่เปิดใช้งานอยู่: <b><?= $active_stores ?></b> ร้าน</li>
            <li>ร้านที่ค้างชำระ: <b><?= $overdue_stores ?></b> ร้าน</li>
            <li>รายได้รวมเดือนนี้: <b><?= number_format($monthly_revenue, 2) ?> ฿</b></li>
        </ul>
    </div>
</div>

</div>

</tbody>
</table>

</div>
</div>
</div>