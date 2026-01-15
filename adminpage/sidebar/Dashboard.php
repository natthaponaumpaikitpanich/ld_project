<?php // ร้านทั้งหมด
$total_stores = $pdo->query("SELECT COUNT(*) FROM stores")->fetchColumn();

// ร้านสมัครแพ็กเกจทั้งหมด
$subscribed_stores = $pdo->query("
    SELECT COUNT(DISTINCT store_id)
    FROM store_subscriptions
")->fetchColumn();

// Active
$active_subs = $pdo->query("
    SELECT COUNT(*)
    FROM store_subscriptions
    WHERE status='active'
")->fetchColumn();

// รออนุมัติ
$waiting_subs = $pdo->query("
    SELECT COUNT(*)
    FROM store_subscriptions
    WHERE status='waiting_approve'
")->fetchColumn();

// รายได้รวมจากค่าสมัคร
$total_revenue = $pdo->query("
    SELECT IFNULL(SUM(monthly_fee),0)
    FROM store_subscriptions
    WHERE status IN ('waiting_approve','active')
")->fetchColumn();

// รายได้เดือนนี้
$monthly_revenue = $pdo->query("
    SELECT IFNULL(SUM(monthly_fee),0)
    FROM store_subscriptions
    WHERE status IN ('waiting_approve','active')
      AND MONTH(created_at)=MONTH(CURDATE())
      AND YEAR(created_at)=YEAR(CURDATE())
")->fetchColumn();

// สมัครล่าสุด
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

// ================== CHART (EXTEND) ==================

// รายได้ค่าสมัครรายเดือน
$chart_revenue = array_fill(1, 12, 0);
$stmt = $pdo->query("
    SELECT MONTH(created_at) m, SUM(monthly_fee) total
    FROM store_subscriptions
    WHERE status IN ('waiting_approve','active')
      AND YEAR(created_at)=YEAR(CURDATE())
    GROUP BY MONTH(created_at)
");
while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $chart_revenue[(int)$r['m']] = (float)$r['total'];
}

// จำนวนร้านสมัครรายเดือน
$chart_sub_count = array_fill(1, 12, 0);
$stmt = $pdo->query("
    SELECT MONTH(created_at) m, COUNT(*) total
    FROM store_subscriptions
    WHERE YEAR(created_at)=YEAR(CURDATE())
    GROUP BY MONTH(created_at)
");
while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $chart_sub_count[(int)$r['m']] = (int)$r['total'];
}

// สถานะร้าน
$store_status = $pdo->query("
    SELECT status, COUNT(*) total
    FROM stores
    GROUP BY status
")->fetchAll(PDO::FETCH_KEY_PAIR);

$store_active   = $store_status['active'] ?? 0;
$store_pending  = $store_status['pending'] ?? 0;
$store_disabled = $store_status['disabled'] ?? 0;
?>

<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>Platform Dashboard</title>

<link href="../../bootstrap/css/bootstrap.min.css" rel="stylesheet">
<link href="../../bootstrap/bootstrap-icons.css" rel="stylesheet">

<style>
  
.dashboard-wrapper { padding: 24px 28px; }
.dashboard-header h2 { font-weight:600; }
.dashboard-header p { color:#64748b; }

.kpi-card {
    background:#fff;
    border-radius:14px;
    padding:20px;
    box-shadow:0 8px 24px rgba(15,23,42,.06);
}

.kpi-card small { color:#64748b; }
.kpi-card h3 { font-weight:700; }

.kpi-icon {
    width:44px;height:44px;border-radius:12px;
    display:flex;align-items:center;justify-content:center;
    color:#fff;font-size:20px;
}
.bg-blue{background:#3b82f6}
.bg-green{background:#22c55e}
.bg-orange{background:#f59e0b}
.bg-purple{background:#8b5cf6}

.dashboard-card {
    background:#fff;
    border-radius:16px;
    padding:20px;
    box-shadow:0 8px 24px rgba(15,23,42,.06);
}

.table-dashboard th {
    font-size:14px;
    font-weight:600;
    color:#475569;
}
.table-dashboard td {
    font-size:14px;
}
</style>
</head>

<body>

<div class="dashboard-wrapper">

    <div class="dashboard-header mb-4">
        <h2>📊 Platform Dashboard</h2>
        <p>ภาพรวมระบบให้เช่าบริการซักอบรีด (SaaS)</p>
    </div>

    <!-- KPI -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="kpi-card d-flex justify-content-between align-items-center">
                <div>
                    <small>ร้านทั้งหมด</small>
                    <h3><?= $total_stores ?></h3>
                </div>
                <div class="kpi-icon bg-blue"><i class="bi bi-shop"></i></div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="kpi-card d-flex justify-content-between align-items-center">
                <div>
                    <small>Active</small>
                    <h3><?= $active_subs ?></h3>
                </div>
                <div class="kpi-icon bg-green"><i class="bi bi-check-circle"></i></div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="kpi-card d-flex justify-content-between align-items-center">
                <div>
                    <small>รออนุมัติ</small>
                    <h3><?= $waiting_subs ?></h3>
                </div>
                <div class="kpi-icon bg-orange"><i class="bi bi-hourglass-split"></i></div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="kpi-card d-flex justify-content-between align-items-center">
                <div>
                    <small>รายได้รวม</small>
                    <h3><?= number_format($total_revenue,2) ?> ฿</h3>
                </div>
                <div class="kpi-icon bg-purple"><i class="bi bi-cash-stack"></i></div>
            </div>
        </div>
    </div>

    <!-- Charts -->
    <div class="row g-4 mb-4">
        <div class="col-md-8">
            <div class="dashboard-card">
                <h6 class="fw-semibold mb-3">📈 รายได้จากค่าสมัคร (รายเดือน)</h6>
                <canvas id="revenueChart" height="120"></canvas>
            </div>
        </div>

        <div class="col-md-4">
            <div class="dashboard-card">
                <h6 class="fw-semibold mb-3">🏪 สถานะร้าน</h6>
                <canvas id="storeChart"></canvas>
            </div>
        </div>
    </div>

    <div class="dashboard-card mb-4">
        <h6 class="fw-semibold mb-3">🧾 ร้านสมัครแพ็กเกจ (รายเดือน)</h6>
        <canvas id="subscriptionChart" height="90"></canvas>
    </div>

    <!-- Latest -->
    <div class="dashboard-card">
        <h6 class="fw-semibold mb-3">สมัครแพ็กเกจล่าสุด</h6>
        <table class="table table-hover table-dashboard">
            <thead>
                <tr>
                    <th>ร้าน</th>
                    <th>แพ็กเกจ</th>
                    <th>ราคา</th>
                    <th>สถานะ</th>
                    <th>วันที่</th>
                </tr>
            </thead>
            <tbody>
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

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const months = ['ม.ค','ก.พ','มี.ค','เม.ย','พ.ค','มิ.ย','ก.ค','ส.ค','ก.ย','ต.ค','พ.ย','ธ.ค'];

new Chart(revenueChart,{
    type:'line',
    data:{labels:months,datasets:[{
        data:<?= json_encode(array_values($chart_revenue)) ?>,
        borderWidth:2,tension:.4,fill:true
    }]},
    options:{plugins:{legend:{display:false}}}
});

new Chart(subscriptionChart,{
    type:'bar',
    data:{labels:months,datasets:[{
        data:<?= json_encode(array_values($chart_sub_count)) ?>
    }]},
    options:{plugins:{legend:{display:false}}}
});

new Chart(storeChart,{
    type:'doughnut',
    data:{
        labels:['Active','Pending','Disabled'],
        datasets:[{data:[
            <?= $store_active ?>,
            <?= $store_pending ?>,
            <?= $store_disabled ?>
        ]}]
    }
});
</script>

</body>
</html>