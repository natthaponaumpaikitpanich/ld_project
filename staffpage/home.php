<?php
// --- LOGIC เดิมของคุณ (ปรับปรุงประสิทธิภาพเล็กน้อย) ---
$user_id = $_SESSION['user_id'];

// ดึงงานที่ยังไม่เสร็จ
$stmt = $pdo->prepare("
    SELECT 
        o.id AS order_id,
        o.order_number,
        o.status,
        o.created_at,
        u.display_name AS customer_name,
        u.phone AS customer_phone
    FROM orders o
    JOIN store_staff ss ON ss.store_id = o.store_id
    LEFT JOIN users u ON u.id = o.customer_id
    WHERE ss.user_id = ?
      AND o.status != 'completed'
    ORDER BY o.created_at ASC
");
$stmt->execute([$user_id]);
$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ดึงจำนวนงานที่เสร็จแล้ววันนี้
$stmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM orders o
    JOIN store_staff ss ON ss.store_id = o.store_id
    WHERE ss.user_id = ?
      AND o.status = 'completed'
      AND DATE(o.updated_at) = CURDATE()
");
$stmt->execute([$user_id]);
$completed_today = $stmt->fetchColumn();

// คำนวณเปอร์เซ็นต์ความสำเร็จ (ลูกเล่นใหม่)
$total_work = count($tasks) + $completed_today;
$progress_percent = ($total_work > 0) ? round(($completed_today / $total_work) * 100) : 0;
?>

<style>
  /* Custom Theme */
  :root {
    --primary-blue: #007bff;
    --light-bg: #f8fbff;
    --card-border: rgba(0, 123, 255, 0.08);
  }

  /* Header Styling */
  .dashboard-header {
    background: linear-gradient(135deg, #0061ff 0%, #60a5fa 100%);
    color: #fff;
    border-radius: 24px;
    position: relative;
    box-shadow: 0 10px 20px rgba(0, 97, 255, 0.15);
  }

  /* Summary Card Styling */
  .stat-card {
    border: none;
    border-radius: 20px;
    background: #fff;
    transition: 0.3s;
    border-bottom: 4px solid transparent;
  }

  .stat-card:hover {
    transform: translateY(-5px);
  }

  .stat-active {
    border-color: #007bff;
  }

  .stat-success {
    border-color: #20c997;
  }

  .stat-pending {
    border-color: #ffc107;
  }

  /* Task Card Styling */
  .task-card {
    border: 1px solid var(--card-border);
    border-radius: 18px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    background: #fff;
    margin-bottom: 12px;
  }

  .task-card:hover {
    border-color: var(--primary-blue);
    box-shadow: 0 8px 15px rgba(0, 0, 0, 0.05);
  }

  .icon-circle {
    width: 45px;
    height: 45px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
  }

  /* Status Badge Modern */
  .badge-soft {
    padding: 8px 12px;
    border-radius: 10px;
    font-weight: 500;
    font-size: 0.75rem;
  }

  .bg-soft-info {
    background: #e0f2fe;
    color: #0369a1;
  }

  .bg-soft-success {
    background: #dcfce7;
    color: #15803d;
  }

  /* Map Card */
  .map-container {
    border-radius: 24px;
    overflow: hidden;
    border: 2px solid #fff;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
  }
</style>

<div class="container py-3">

  <div class="dashboard-header p-4 mb-4">
    <div class="row align-items-center">
      <div class="col-8">
        <h5 class="fw-bold mb-0">สวัสดีครับ 👋</h5>
        <p class="small mb-0 opacity-75">วันนี้งานเยอะไหม? สู้ๆ นะครับ!</p>
      </div>
      <div class="col-4 text-end">
        <div class="progress-circle text-center">
          <span class="d-block h4 fw-bold mb-0"><?= $progress_percent ?>%</span>
          <small style="font-size: 9px;">Done</small>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-3 mb-4">
    <div class="col-4">
      <div class="card stat-card stat-active shadow-sm">
        <div class="card-body p-2 text-center">
          <div class="text-primary fw-bold h4 mb-0"><?= count($tasks) ?></div>
          <div class="text-muted" style="font-size: 10px;">งานใหม่</div>
        </div>
      </div>
    </div>
    <div class="col-4">
      <div class="card stat-card stat-success shadow-sm">
        <div class="card-body p-2 text-center">
          <div class="text-success fw-bold h4 mb-0"><?= $completed_today ?></div>
          <div class="text-muted" style="font-size: 10px;">สำเร็จแล้ว</div>
        </div>
      </div>
    </div>
    <div class="col-4">
      <div class="card stat-card stat-pending shadow-sm">
        <div class="card-body p-2 text-center">
          <div class="text-warning fw-bold h4 mb-0"><?= count(array_filter($tasks, fn($t) => $t['status'] !== 'completed')) ?></div>
          <div class="text-muted" style="font-size: 10px;">กำลังทำ</div>
        </div>
      </div>
    </div>
  </div>

  <div class="d-flex justify-content-between align-items-center mb-3">
    <h6 class="fw-bold text-dark mb-0"><i class="bi bi-list-stars me-2 text-primary"></i>รายการงานที่ต้องจัดการ</h6>
    <a href="index.php?link=Tasks" class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm" style="font-size: 11px;">
      <i class="bi bi-arrow-repeat me-1"></i> จัดการงาน
    </a>
  </div>

  <?php if (!$tasks): ?>
    <div class="text-center py-5">
      <img src="https://cdn-icons-png.flaticon.com/512/7486/7486744.png" width="80" class="mb-3 opacity-50">
      <p class="text-muted">ว้าว! วันนี้งานเสร็จหมดแล้วพักผ่อนได้</p>
    </div>
  <?php endif; ?>

  <div class="task-container">
    <?php foreach ($tasks as $task): ?>
      <div class="card task-card shadow-sm border-0">
        <div class="card-body p-3">
          <div class="d-flex align-items-center">
            <div class="icon-circle bg-soft-info text-info me-3">
              <i class="bi bi-box-seam-fill"></i>
            </div>

            <div class="flex-grow-1">
              <div class="d-flex justify-content-between align-items-start">
                <div>
                  <div class="fw-bold text-dark mb-0" style="font-size: 0.95rem;">
                    <?= htmlspecialchars($task['customer_name']) ?>
                  </div>
                  <div class="text-muted" style="font-size: 0.8rem;">
                    #<?= htmlspecialchars($task['order_number']) ?> • <i class="bi bi-clock me-1"></i><?= date('H:i', strtotime($task['created_at'])) ?>
                  </div>
                </div>
                <span class="badge-soft bg-soft-info">
                  <?= strtoupper($task['status']) ?>
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="mt-4">
    <h6 class="fw-bold text-dark mb-3"><i class="bi bi-map-fill me-2 text-primary"></i>ตำแหน่งงานในพื้นที่</h6>
    <div class="map-container shadow-sm p-2 bg-white">
      <div id="map-render" style="border-radius: 18px; overflow: hidden;">
        <?php include "menu/map/map_index.php"; ?>
      </div>
    </div>
  </div>

</div>

<script>
  // เพิ่ม Interaction เล็กน้อยเมื่อโหลดเสร็จ
  document.addEventListener('DOMContentLoaded', function() {
    const cards = document.querySelectorAll('.task-card');
    cards.forEach((card, index) => {
      card.style.opacity = '0';
      card.style.transform = 'translateX(-20px)';
      setTimeout(() => {
        card.style.transition = 'all 0.4s ease';
        card.style.opacity = '1';
        card.style.transform = 'translateX(0)';
      }, index * 100);
    });
  });
</script>