<?php



// Query: ตัวเลขบน Dashboard
$total_today = $conn->query("SELECT COUNT(*) AS num FROM orders WHERE DATE(created_at)=CURDATE()")->fetch_assoc()['num'];

$in_process = $conn->query("SELECT COUNT(*) AS num FROM orders WHERE status='in_process'")->fetch_assoc()['num'];

$ready = $conn->query("SELECT COUNT(*) AS num FROM orders WHERE status='ready'")->fetch_assoc()['num'];

$revenue = $conn->query("SELECT IFNULL(SUM(amount),0) AS total FROM payments WHERE DATE(paid_at)=CURDATE() AND status='success'")
                 ->fetch_assoc()['total'];

// Query: ตารางงานวันนี้
$today_orders = $conn->query("
    SELECT o.order_number, u.display_name AS customer_name, 
           o.pickup_time, o.status
    FROM orders o
    LEFT JOIN users u ON o.customer_id = u.id
    WHERE DATE(o.created_at)=CURDATE()
    ORDER BY o.pickup_time ASC
");
?>
<?php

    ?>
<div class="container mt-4">
    
    <h2 class="mb-4 fw-bold"> Dashboardผู้ดูแลระบบ</h2>

    <!-- Summary Cards -->
    <div class="row g-3">
        <div class="col-md-3">
            <div class="card text-bg-primary p-3">
                <h5>งานวันนี้</h5>
                <h2><?= $total_today ?></h2>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card text-bg-warning p-3">
                <h5>กำลังดำเนินการ</h5>
                <h2><?= $in_process ?></h2>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card text-bg-success p-3">
                <h5>พร้อมส่ง / พร้อมรับ</h5>
                <h2><?= $ready ?></h2>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card text-bg-dark p-3">
                <h5>รายได้วันนี้</h5>
                <h2><?= number_format($revenue,2) ?> ฿</h2>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="mt-4 d-flex gap-3">
        <a href="../promotion/index.php" class="btn btn-success btn-lg">📣ประกาศโปรโมชั่น/แจ้งเตือน</a>
    </div>

    <!-- Today's Orders Table -->
    <div class="card mt-4">
        <div class="card-header">
            <h5 class="m-0">รายการงานวันนี้</h5>
        </div>
        <div class="card-body">

            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>ลูกค้า</th>
                        <th>เวลารับผ้า</th>
                        <th>สถานะ</th>
                        <th>จัดการ</th>
                    </tr>
                </thead>
                <tbody>

                <?php while($row = $today_orders->fetch_assoc()) { ?>
                    <tr>
                        <td><?= $row['order_number'] ?></td>
                        <td><?= $row['customer_name'] ?></td>
                        <td><?= $row['pickup_time'] ?></td>
                        <td>
                            <?php
                                $status_color = match($row['status']) {
                                    'in_process' => 'warning',
                                    'ready' => 'success',
                                    'out_for_delivery' => 'primary',
                                    'completed' => 'secondary',
                                    default => 'dark'
                                };
                            ?>
                            <span class="badge bg-<?= $status_color ?>">
                                <?= $row['status'] ?>
                            </span>
                        </td>
                        <td>
                            <a href="update_order.php?order=<?= $row['order_number'] ?>" class="btn btn-sm btn-primary">
                                อัปเดตสถานะ
                            </a>
                        </td>
                    </tr>
                <?php } ?>

                </tbody>
            </table>

        </div>
    </div>
</div>
