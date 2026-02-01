<?php

$store_id = $_SESSION['store_id'] ?? null;
if (!$store_id) {
    die("ไม่พบร้าน");
}

/* ---------- รายได้วันนี้ ---------- */
$stmt = $pdo->prepare("
    SELECT IFNULL(SUM(p.amount),0)
    FROM payments p
    JOIN orders o ON p.order_id = o.id
    WHERE p.status = 'confirmed'
      AND o.store_id = ?
      AND DATE(p.confirmed_at) = CURDATE()
");
$stmt->execute([$store_id]);
$today_income = $stmt->fetchColumn();

/* ---------- รายได้เดือนนี้ ---------- */
$stmt = $pdo->prepare("
    SELECT IFNULL(SUM(p.amount),0)
    FROM payments p
    JOIN orders o ON p.order_id = o.id
    WHERE p.status = 'confirmed'
      AND o.store_id = ?
      AND MONTH(p.confirmed_at) = MONTH(CURDATE())
      AND YEAR(p.confirmed_at) = YEAR(CURDATE())
");
$stmt->execute([$store_id]);
$month_income = $stmt->fetchColumn();

/* ---------- รายการชำระเงิน ---------- */
$stmt = $pdo->prepare("
    SELECT 
        p.amount,
        p.provider,
        p.status,
        p.confirmed_at,
        o.order_number
    FROM payments p
    JOIN orders o ON p.order_id = o.id
    WHERE o.store_id = ?
      AND p.status = 'confirmed'
    ORDER BY p.confirmed_at DESC
");
$stmt->execute([$store_id]);
$payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
/* ---------- ข้อมูลรายรับย้อนหลัง 7 วัน (สำหรับกราฟ) ---------- */
$chart_labels = [];
$chart_data = [];

for ($i = 6; $i >= 0; $i--) {
    // หาความต่างของวัน
    $date = date('Y-m-d', strtotime("-$i days"));
    $display_name = ($i == 0) ? 'วันนี้' : date('D', strtotime($date)); // แสดงเป็น Mon, Tue... หรือ 'วันนี้'
    
    // Query ยอดรวมของวันนั้นๆ
    $stmt = $pdo->prepare("
        SELECT IFNULL(SUM(p.amount), 0) 
        FROM payments p
        JOIN orders o ON p.order_id = o.id
        WHERE o.store_id = ? 
          AND p.status = 'confirmed' 
          AND DATE(p.confirmed_at) = ?
    ");
    $stmt->execute([$store_id, $date]);
    $daily_sum = $stmt->fetchColumn();
    
    $chart_labels[] = $display_name;
    $chart_data[] = (float)$daily_sum;
}
?>

<div class="container mt-4">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
    :root {
        --primary-blue: #0084ff;
        --secondary-blue: #00d2ff;
        --light-bg: #f4f7ff;
    }

    body {
        background-color: var(--light-bg);
        font-family: 'Kanit', sans-serif;
    }

    /* Dashboard Cards */
    .stat-card {
        border: none;
        border-radius: 20px;
        transition: transform 0.3s;
        background: white;
    }

    .stat-card:hover {
        transform: translateY(-5px);
    }

    .icon-shape {
        width: 48px;
        height: 48px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 12px;
    }

    .bg-soft-primary { background: #e0f2ff; color: #0084ff; }
    .bg-soft-success { background: #e6fffa; color: #059669; }

    /* Chart Container */
    .chart-container {
        background: white;
        border-radius: 20px;
        padding: 20px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.05);
    }

    /* Table Styling */
    .custom-table-card {
        border-radius: 20px;
        border: none;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(0,0,0,0.05);
    }

    .table thead th {
        background-color: #f8fbff;
        border-bottom: 2px solid #eef2f7;
        color: #5e72e4;
        text-transform: uppercase;
        font-size: 0.8rem;
        letter-spacing: 1px;
    }

    .badge-income {
        background: linear-gradient(135deg, #2dce89, #2dcecc);
        border: none;
        padding: 8px 12px;
        border-radius: 10px;
    }

    .provider-pill {
        background: #f0f3f6;
        padding: 4px 12px;
        border-radius: 50px;
        font-size: 0.85rem;
        color: #525f7f;
    }
</style>

<div class="container mt-5 pb-5">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-0">💰 รายได้และสถิติ</h2>
            <p class="text-muted">สรุปภาพรวมการเงินของร้านคุณ</p>
        </div>
        <button class="btn btn-white shadow-sm border-0 rounded-pill px-4" onclick="window.print()">
            <i class="bi bi-printer me-2"></i> พิมพ์รายงาน
        </button>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="card stat-card shadow-sm p-4 border-0" style="background: linear-gradient(45deg, #ffffff, #f0f7ff);">
                <div class="d-flex align-items-center">
                    <div class="icon-shape bg-soft-success me-3">
                        <i class="bi bi-cash-stack fs-4"></i>
                    </div>
                    <div>
                        <small class="text-muted fw-medium">รายได้วันนี้</small>
                        <h2 class="fw-bold mb-0 text-success"><?= number_format($today_income, 2) ?> <span class="fs-6">฿</span></h2>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card stat-card shadow-sm p-4 border-0" style="background: linear-gradient(45deg, #ffffff, #eef5ff);">
                <div class="d-flex align-items-center">
                    <div class="icon-shape bg-soft-primary me-3">
                        <i class="bi bi-graph-up-arrow fs-4"></i>
                    </div>
                    <div>
                        <small class="text-muted fw-medium">รายได้เดือนนี้</small>
                        <h2 class="fw-bold mb-0 text-primary"><?= number_format($month_income, 2) ?> <span class="fs-6">฿</span></h2>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-5">
        <div class="col-12">
            <div class="chart-container shadow-sm border-0">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="fw-bold mb-0">แนวโน้มรายได้ (7 วันล่าสุด)</h5>
                    <span class="badge bg-soft-primary rounded-pill">Real-time Data</span>
                </div>
                <div style="height: 300px;">
                    <canvas id="incomeChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="card custom-table-card shadow-sm border-0">
        <div class="card-header bg-white py-4 px-4 border-0">
            <h5 class="fw-bold mb-0">ประวัติการชำระเงินล่าสุด</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 px-4">
                <thead>
                    <tr>
                        <th class="ps-4">Order Number</th>
                        <th>จำนวนเงิน</th>
                        <th>ช่องทาง</th>
                        <th>สถานะ</th>
                        <th class="text-end pe-4">วันที่ชำระ</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($payments)): ?>
                    <tr>
                        <td colspan="5" class="text-center py-5 text-muted">
                            <i class="bi bi-receipt fs-1 d-block mb-3 opacity-25"></i>
                            ยังไม่มีรายการชำระเงินในระบบ
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($payments as $p): ?>
                    <tr>
                        <td class="ps-4">
                            <span class="fw-bold text-dark">#<?= htmlspecialchars($p['order_number']) ?></span>
                        </td>
                        <td>
                            <span class="text-primary fw-bold fs-5">+<?= number_format($p['amount'], 2) ?></span>
                        </td>
                        <td>
                            <span class="provider-pill">
                                <i class="bi bi-wallet2 me-1 small"></i> <?= htmlspecialchars($p['provider']) ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge badge-income text-white">
                                <i class="bi bi-check-circle-fill me-1 small"></i> รับเงินแล้ว
                            </span>
                        </td>
                        <td class="text-end pe-4 text-muted small">
                            <?= date('d M Y', strtotime($p['confirmed_at'])) ?><br>
                            <?= date('H:i', strtotime($p['confirmed_at'])) ?> น.
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    const ctx = document.getElementById('incomeChart').getContext('2d');
    
    // สร้าง Gradient สำหรับกราฟ
    const gradient = ctx.createLinearGradient(0, 0, 0, 300);
    gradient.addColorStop(0, 'rgba(0, 132, 255, 0.2)');
    gradient.addColorStop(1, 'rgba(0, 132, 255, 0)');

    new Chart(ctx, {
        type: 'line',
        data: {
            // ดึงค่าจาก PHP Array ที่เราเตรียมไว้
            labels: <?= json_encode($chart_labels) ?>, 
            datasets: [{
                label: 'รายได้ (บาท)',
                data: <?= json_encode($chart_data) ?>, 
                borderColor: '#0084ff',
                backgroundColor: gradient,
                fill: true,
                tension: 0.4,
                borderWidth: 3,
                pointBackgroundColor: '#fff',
                pointBorderColor: '#0084ff',
                pointHoverRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return 'รายได้: ' + context.parsed.y.toLocaleString() + ' ฿';
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: '#f0f0f0' },
                    ticks: { 
                        font: { family: 'Kanit' },
                        callback: function(value) {
                            return value.toLocaleString();
                        }
                    }
                },
                x: {
                    grid: { display: false },
                    ticks: { font: { family: 'Kanit' } }
                }
            }
        }
    });
</script>