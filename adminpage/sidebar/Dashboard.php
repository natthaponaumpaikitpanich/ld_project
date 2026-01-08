<?php
/* =================== DASHBOARD ADMIN =================== */

/* ---------- ร้านทั้งหมด ---------- */
$total_stores = $pdo->query("SELECT COUNT(*) FROM stores")->fetchColumn();

/* ---------- ร้านที่สมัครแพ็กเกจ ---------- */
$subscribed_stores = $pdo->query("
    SELECT COUNT(DISTINCT store_id)
    FROM store_subscriptions
")->fetchColumn();

/* ---------- ร้านที่ Active ---------- */
$active_subs = $pdo->query("
    SELECT COUNT(*)
    FROM store_subscriptions
    WHERE status='active'
")->fetchColumn();

/* ---------- ร้านรออนุมัติ ---------- */
$waiting_subs = $pdo->query("
    SELECT COUNT(*)
    FROM store_subscriptions
    WHERE status='waiting_approve'
")->fetchColumn();

/* ---------- รายได้รวมทั้งหมด (จากการสมัคร) ---------- */
$total_revenue = $pdo->query("
    SELECT IFNULL(SUM(monthly_fee),0)
    FROM store_subscriptions
    WHERE status IN ('waiting_approve','active')
")->fetchColumn();

/* ---------- รายได้เดือนนี้ ---------- */
$monthly_revenue = $pdo->query("
    SELECT IFNULL(SUM(monthly_fee),0)
    FROM store_subscriptions
    WHERE status IN ('waiting_approve','active')
      AND MONTH(created_at)=MONTH(CURDATE())
      AND YEAR(created_at)=YEAR(CURDATE())
")->fetchColumn();

/* ---------- รายการสมัครล่าสุด ---------- */
$latest_subs = $pdo->query("
    SELECT
        s.name AS store_name,
        ss.plan,
        ss.monthly_fee,
        ss.status,
        ss.created_at
    FROM store_subscriptions ss
    JOIN stores s ON ss.store_id = s.id
    ORDER BY ss.created_at DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mt-4">

<h2 class="fw-bold mb-4">📊 Dashboard การเงิน (สมัครสมาชิก)</h2>

<div class="row g-3 mb-4">

    <div class="col-md-3">
        <div class="card text-bg-primary p-3">
            <h6>ร้านทั้งหมด</h6>
            <h2><?= $total_stores ?></h2>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card text-bg-success p-3">
            <h6>ร้านสมัครแพ็กเกจ</h6>
            <h2><?= $subscribed_stores ?></h2>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card text-bg-warning p-3">
            <h6>รออนุมัติ</h6>
            <h2><?= $waiting_subs ?></h2>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card text-bg-dark p-3">
            <h6>Active</h6>
            <h2><?= $active_subs ?></h2>
        </div>
    </div>

</div>

<div class="row g-3 mb-4">

    <div class="col-md-6">
        <div class="card p-3 shadow">
            <h6 class="text-muted">รายได้รวมทั้งหมด</h6>
            <h3 class="fw-bold text-success">
                <?= number_format($total_revenue,2) ?> ฿
            </h3>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card p-3 shadow">
            <h6 class="text-muted">รายได้เดือนนี้</h6>
            <h3 class="fw-bold text-primary">
                <?= number_format($monthly_revenue,2) ?> ฿
            </h3>
        </div>
    </div>

</div>

<!-- สมัครล่าสุด -->
<div class="card shadow">
    <div class="card-header fw-bold">
        การสมัครแพ็กเกจล่าสุด
    </div>
    <div class="card-body">

        <table class="table table-striped align-middle">
            <thead class="table-light">
                <tr>
                    <th>ร้าน</th>
                    <th>แพ็กเกจ</th>
                    <th>จำนวนเงิน</th>
                    <th>สถานะ</th>
                    <th>วันที่สมัคร</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($latest_subs)): ?>
                <tr>
                    <td colspan="5" class="text-center text-muted">
                        ยังไม่มีข้อมูล
                    </td>
                </tr>
            <?php endif; ?>

            <?php foreach ($latest_subs as $r): ?>
                <tr>
                    <td><?= htmlspecialchars($r['store_name']) ?></td>
                    <td><?= htmlspecialchars($r['plan']) ?></td>
                    <td><?= number_format($r['monthly_fee'],2) ?> ฿</td>
                    <td>
                        <span class="badge bg-<?= 
                            $r['status']=='active'?'success':
                            ($r['status']=='waiting_approve'?'warning':'secondary')
                        ?>">
                            <?= $r['status'] ?>
                        </span>
                    </td>
                    <td><?= date('d/m/Y H:i', strtotime($r['created_at'])) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

    </div>
</div>

</div>
