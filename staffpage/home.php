<?php
$stmt = $pdo->prepare("
    SELECT 
        o.id AS order_id,
        o.order_number,
        o.status,
        o.created_at,
        u.display_name AS customer_name
    FROM orders o
    JOIN store_staff ss ON ss.store_id = o.store_id
    LEFT JOIN users u ON u.id = o.customer_id
    WHERE ss.user_id = ?
      AND o.status != 'completed'
    ORDER BY o.created_at ASC
");
$stmt->execute([$_SESSION['user_id']]);
$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM orders o
    JOIN store_staff ss ON ss.store_id = o.store_id
    WHERE ss.user_id = ?
      AND o.status = 'completed'
      AND DATE(o.updated_at) = CURDATE()
");
$stmt->execute([$_SESSION['user_id']]);
$completed_today = $stmt->fetchColumn();

?>
<style>
  .dashboard-header {
    background: linear-gradient(135deg, #0d6efd, #20c997);
    color: #fff;
    border-radius: 16px;
  }

  .task-card {
    transition: .2s;
  }

  .task-card:hover {
    transform: translateY(-2px);
  }
</style>

<div class="container py-4">

  <!-- ===== HEADER ===== -->
  <div class="dashboard-header p-4 mb-4 shadow-sm">
    <h4 class="fw-bold mb-1">👷 หน้าหลักของพนักงาน</h4>
    <div class="small opacity-75">
      งานประจำวันที่ <?= date('d/m/Y') ?>
    </div>
  </div>

  <!-- ===== SUMMARY ===== -->
  <div class="row g-3 mb-4">
    <div class="col-md-4">
      <div class="card shadow-sm">
        <div class="card-body text-center">
          <h3 class="fw-bold mb-0"><?= count($tasks) ?></h3>
          <small class="text-muted">งานวันนี้</small>
        </div>
      </div>
    </div>

    <div class="col-md-4">
      <div class="card shadow-sm">
        <div class="card-body text-center">
          <h3 class="fw-bold mb-0"><?= $completed_today ?></h3>
<small class="text-muted">เสร็จแล้ววันนี้</small>
        </div>
      </div>
    </div>

    <div class="col-md-4">
      <div class="card shadow-sm">
        <div class="card-body text-center">
          <h3 class="fw-bold mb-0">
            <?= count(array_filter($tasks, fn($t) => $t['status'] !== 'completed')) ?>
          </h3>
          <small class="text-muted">คงเหลือ</small>
        </div>
      </div>
    </div>
  </div>

  <!-- ===== TASK LIST ===== -->
  <div class="d-flex justify-content-between align-items-center mb-2">
    <h5 class="fw-bold mb-0">📋 งานที่ต้องทำวันนี้</h5>

    <a href="index.php?link=Tasks"
      class="btn btn-success btn-sm">
      🔄 อัปเดตสถานะงาน
    </a>
  </div>

  <?php if (!$tasks): ?>
    <div class="alert alert-info mt-3">
      ยังไม่มีงานที่ต้องทำวันนี้
    </div>
  <?php endif; ?>

  <?php foreach ($tasks as $task): ?>
    <div class="card task-card mb-2 shadow-sm">
      <div class="card-body">

        <div class="d-flex justify-content-between align-items-center">
          <div>
            <div class="fw-semibold">
              👤 <?= htmlspecialchars($task['customer_name']) ?>
            </div>
            <small class="text-muted">
              Order: <?= htmlspecialchars($task['order_number']) ?>
            </small>
          </div>

          <span class="badge rounded-pill
            bg-<?= $task['status'] == 'completed' ? 'success' : 'info' ?>">
            <?= strtoupper($task['status']) ?>
          </span>
        </div>

      </div>
    </div>
  <?php endforeach; ?>

  <!-- ===== MAP ===== -->
  <div class="card shadow-sm mt-4">
    <div class="card-body">
      <h6 class="fw-bold mb-2"><?php include "menu/map/map_index.php"; ?></h6>

    </div>
  </div>

</div>