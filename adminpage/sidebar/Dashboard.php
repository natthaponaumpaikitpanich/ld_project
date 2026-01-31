<?php
// --- Logic PHP ของคุณเหมือนเดิมเป๊ะ ห้ามขยับ ---
$total_stores = $pdo->query("SELECT COUNT(*) FROM stores")->fetchColumn();
$subscribed_stores = $pdo->query("SELECT COUNT(DISTINCT store_id) FROM store_subscriptions")->fetchColumn();
$active_subs = $pdo->query("SELECT COUNT(*) FROM store_subscriptions WHERE status='active'")->fetchColumn();
$waiting_subs = $pdo->query("SELECT COUNT(*) FROM store_subscriptions WHERE status='waiting_approve'")->fetchColumn();
$total_revenue = $pdo->query("SELECT IFNULL(SUM(monthly_fee),0) FROM store_subscriptions WHERE status IN ('waiting_approve','active')")->fetchColumn();
$monthly_revenue = $pdo->query("SELECT IFNULL(SUM(monthly_fee),0) FROM store_subscriptions WHERE status IN ('waiting_approve','active') AND MONTH(created_at)=MONTH(CURDATE()) AND YEAR(created_at)=YEAR(CURDATE())")->fetchColumn();
$latest_subs = $pdo->query("SELECT s.name AS store_name, ss.plan, ss.monthly_fee, ss.status, ss.created_at FROM store_subscriptions ss JOIN stores s ON ss.store_id = s.id ORDER BY ss.created_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);

$chart_revenue = array_fill(1, 12, 0);
$stmt = $pdo->query("SELECT MONTH(created_at) m, SUM(monthly_fee) total FROM store_subscriptions WHERE status IN ('waiting_approve','active') AND YEAR(created_at)=YEAR(CURDATE()) GROUP BY MONTH(created_at)");
while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $chart_revenue[(int)$r['m']] = (float)$r['total'];
}

$chart_sub_count = array_fill(1, 12, 0);
$stmt = $pdo->query("SELECT MONTH(created_at) m, COUNT(*) total FROM store_subscriptions WHERE YEAR(created_at)=YEAR(CURDATE()) GROUP BY MONTH(created_at)");
while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $chart_sub_count[(int)$r['m']] = (int)$r['total'];
}

$store_status = $pdo->query("SELECT status, COUNT(*) total FROM stores GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);
$store_active   = $store_status['active'] ?? 0;
$store_pending  = $store_status['pending'] ?? 0;
$store_disabled = $store_status['disabled'] ?? 0;
?>

<style>
    /* ใช้เฉพาะ Style ที่จำเป็น เพื่อความสวยงามและไม่กระทบ Sidebar */
    .dashboard-container {
        padding: 15px;
    }

    .kpi-card {
        background: #fff;
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        height: 100%;
    }

    .kpi-icon {
        width: 48px;
        height: 48px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 22px;
    }

    .bg-blue {
        background: #3b82f6
    }

    .bg-green {
        background: #22c55e
    }

    .bg-orange {
        background: #f59e0b
    }

    .bg-purple {
        background: #8b5cf6
    }

    .dashboard-card {
        background: #fff;
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        margin-bottom: 20px;
    }

    .table-dashboard {
        font-size: 14px;
    }
</style>

<div class="dashboard-container">
    <div class="mb-4">
        <h2 class="fw-bold">📊 Platform Dashboard</h2>
        <p class="text-muted">ภาพรวมระบบให้เช่าบริการซักอบรีด (SaaS)</p>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="kpi-card d-flex align-items-center justify-content-between">
                <div><small class="text-muted">ร้านทั้งหมด</small>
                    <h3 class="mb-0"><?= $total_stores ?></h3>
                </div>
                <div class="kpi-icon bg-blue"><i class="bi bi-shop"></i></div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="kpi-card d-flex align-items-center justify-content-between">
                <div><small class="text-muted">Active</small>
                    <h3 class="mb-0"><?= $active_subs ?></h3>
                </div>
                <div class="kpi-icon bg-green"><i class="bi bi-check-circle"></i></div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="kpi-card d-flex align-items-center justify-content-between">
                <div><small class="text-muted">รออนุมัติ</small>
                    <h3 class="mb-0"><?= $waiting_subs ?></h3>
                </div>
                <div class="kpi-icon bg-orange"><i class="bi bi-hourglass-split"></i></div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="kpi-card d-flex align-items-center justify-content-between">
                <div><small class="text-muted">รายได้รวม</small>
                    <h3 class="mb-0"><?= number_format($total_revenue, 0) ?> ฿</h3>
                </div>
                <div class="kpi-icon bg-purple"><i class="bi bi-cash-stack"></i></div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-8">
            <div class="dashboard-card">
                <h6 class="fw-bold mb-3">📈 รายได้รายเดือน</h6>
                <canvas id="revenueChart" height="100"></canvas>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="dashboard-card text-center">
                <h6 class="fw-bold mb-3">🏪 สถานะร้าน</h6>
                <canvas id="storeChart"></canvas>
            </div>
        </div>
    </div>

    <div class="dashboard-card">
        <h6 class="fw-bold mb-3">🧾 สมัครแพ็กเกจล่าสุด</h6>
        <div class="table-responsive">
            <table class="table table-hover table-dashboard align-middle">
                <thead class="table-light">
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
                            <td><strong><?= htmlspecialchars($r['store_name']) ?></strong></td>
                            <td><?= htmlspecialchars($r['plan']) ?></td>
                            <td><?= number_format($r['monthly_fee'], 2) ?> ฿</td>
                            <td>
                                <span class="badge bg-<?= $r['status'] == 'active' ? 'success' : ($r['status'] == 'waiting_approve' ? 'warning' : 'secondary') ?>">
                                    <?= $r['status'] ?>
                                </span>
                            </td>
                            <td class="text-muted"><?= date('d/m/Y H:i', strtotime($r['created_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    (function() {
        const months = ['ม.ค', 'ก.พ', 'มี.ค', 'เม.ย', 'พ.ค', 'มิ.ย', 'ก.ค', 'ส.ค', 'ก.ย', 'ต.ค', 'พ.ย', 'ธ.ค'];

        new Chart(document.getElementById('revenueChart'), {
            type: 'line',
            data: {
                labels: months,
                datasets: [{
                    label: 'รายได้',
                    data: <?= json_encode(array_values($chart_revenue)) ?>,
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });

        new Chart(document.getElementById('storeChart'), {
            type: 'doughnut',
            data: {
                labels: ['Active', 'Pending', 'Disabled'],
                datasets: [{
                    data: [<?= $store_active ?>, <?= $store_pending ?>, <?= $store_disabled ?>],
                    backgroundColor: ['#22c55e', '#f59e0b', '#ef4444']
                }]
            }
        });
    })();
</script>