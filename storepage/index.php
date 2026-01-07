<?php
include 'assets/boostap.php';
include 'assets/style.php';
include 'assets/sum.php';

/* ================= PROMOTIONS (ของแอดมิน) ================= */
$sql = "
SELECT title, image
FROM promotions
WHERE status = 'active'
  AND start_date <= CURDATE()
  AND end_date >= CURDATE()
ORDER BY created_at DESC
";
$promos = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
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
/* ================= TOTAL USERS (ลูกค้าที่เคยใช้ร้านนี้) ================= */
/* ใช้ distinct customer_id จาก orders */
$userStmt = $pdo->query("
    SELECT COUNT(*) 
    FROM users
    WHERE role NOT IN ( 'platform_admin', 'store_owner')
");
$total_users = (int)$userStmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="th">

<head>
  <meta charset="UTF-8" />
  <title>ร้านซักอบรีด</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />

  <!-- Bootstrap Offline -->
  <link href="../bootstrap/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.css" rel="stylesheet">

  <style>
    body {
      background: #f5f7fb;
      font-family: 'Kanit', sans-serif;
    }
    .card { border: none; border-radius: 14px; }
    .stat-card { display: flex; justify-content: space-between; align-items: center; }
    .stat-icon {
      width: 48px; height: 48px; border-radius: 12px;
      display: flex; align-items: center; justify-content: center;
      font-size: 22px; color: #fff;
    }
    .quick-btn {
      border-radius: 16px; color: #fff; padding: 22px;
      text-align: center; font-weight: 600;
      box-shadow: 0 10px 20px rgba(0, 0, 0, 0.08);
      transition: transform .2s ease;
    }
    .quick-btn:hover { transform: translateY(-3px); }
    .bg-grad-orders { background: linear-gradient(135deg, #2097ffff, #52ebf3ff, #00ffa2ff); }
    .bg-grad-delivery { background: linear-gradient(135deg, #4facfe, #00f2fe); }
    .bg-grad-revenue { background: linear-gradient(135deg, #43e97b, #38f9d7); }
    .bg-grad-staff { background: linear-gradient(135deg, #ac5ff9ff, #ff009dff); }
    .bg-grad-qrcode { background: linear-gradient(135deg, #5fd8f9ff, #c300ffff); }
    .bg-grad-promotion { background: linear-gradient(135deg, #ff06d5ff, #00d9ffff); }
    .issue-btn {
      position: fixed; bottom: 30px; right: 30px;
      width: 60px; height: 60px; border-radius: 50%;
      background: linear-gradient(135deg, #ff9800, #f44336);
      color: #fff; font-size: 26px; border: none;
      box-shadow: 0 8px 20px rgba(0,0,0,0.3);
      cursor: pointer; z-index: 9999;
    }
    .issue-btn:hover { transform: scale(1.1); }
  </style>
</head>

<body>

<div class="container py-4">

  <!-- HEADER -->
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h4 class="mb-0">Dashboard ของเจ้าของร้าน</h4>
      <small class="text-muted">บริหารจัดการธุรกิจบริการซักรีดของคุณ</small>
    </div>
    <div class="d-flex align-items-center gap-3">
      <a href="../loginpage/logout.php" class="btn btn-danger bi bi-box-arrow-left"> ออกจากระบบ</a>
      <a href="index.php?link=profile" class="btn btn-primary bi bi-person"> แก้ไขโปรไฟล์</a>
    </div>
  </div>

  <!-- PROMOTION -->
  <?php if (!empty($promos)): ?>
  <div id="promoCarousel" class="carousel slide mb-4" data-bs-ride="carousel">
    <div class="carousel-inner rounded shadow">
      <?php foreach ($promos as $i => $p): ?>
      <div class="carousel-item <?= $i == 0 ? 'active' : '' ?>">
        <img src="../adminpage/promotion/uploads/<?= htmlspecialchars($p['image']) ?>"
             class="d-block w-100" style="height:400px; object-fit:cover;">
      </div>
      <?php endforeach; ?>
    </div>
    <button class="carousel-control-prev" type="button" data-bs-target="#promoCarousel" data-bs-slide="prev">
      <span class="carousel-control-prev-icon"></span>
    </button>
    <button class="carousel-control-next" type="button" data-bs-target="#promoCarousel" data-bs-slide="next">
      <span class="carousel-control-next-icon"></span>
    </button>
  </div>
  <?php endif; ?>

  <!-- SUMMARY -->
  <div class="row g-3 mb-4">
    <div class="col-md-4">
      <div class="card p-3 shadow-sm stat-card">
        <div>
          <small>รายได้วันนี้</small>
          <h4 class="">
                    <?= number_format($today_income, 2) ?> ฿
                </h4>
        </div>
        <div class="stat-icon bg-primary"><i class="bi bi-currency-dollar"></i></div>
      </div>
    </div>

    <div class="col-md-4">
      <div class="card p-3 shadow-sm stat-card">
        <div>
          <small>รายได้ประจำเดือนนี้</small>
         <h4 class="">
                    <?= number_format($month_income, 2) ?> ฿
                </h4>
        </div>
        <div class="stat-icon bg-info"><i class="bi bi-graph-up"></i></div>
      </div>
    </div>

    <div class="col-md-4">
      <div class="card p-3 shadow-sm stat-card">
        <div>
          <small>ยอดผู้ใช้ทั้งหมด</small>
          <h4><?= number_format($total_users) ?> คน</h4>
        </div>
        <div class="stat-icon bg-success"><i class="bi bi-person"></i></div>
      </div>
    </div>
  </div>

  <!-- QUICK ACTIONS -->
  <div class="row g-1 mb-4">
    <div class="col-md-2"><a href="index.php?link=orders" class="btn quick-btn bg-grad-orders w-100">ออเดอร์</a></div>
    <div class="col-md-2"><a href="index.php?link=delivery" class="btn quick-btn bg-grad-delivery w-100">การจัดส่ง</a></div>
    <div class="col-md-2"><a href="index.php?link=revenue" class="btn quick-btn bg-grad-revenue w-100">รายได้</a></div>
    <div class="col-md-3"><a href="index.php?link=promotion" class="btn quick-btn bg-grad-promotion w-100">ประกาศโปรโมชั่น</a></div>
    <div class="col-md-3"><a href="index.php?link=management" class="btn quick-btn bg-grad-staff w-100">การจัดการพนักงาน</a></div>
  </div>

  <?php include_once 'body.php'; ?>
</div>

<!-- ISSUE -->
<button class="issue-btn" data-bs-toggle="modal" data-bs-target="#issueModal">⚠️</button>

<div class="modal fade" id="issueModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title">แจ้งปัญหา / ติดต่อแอดมิน</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="post" action="menu/support/issue_submit.php">
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">หัวข้อปัญหา</label>
            <input type="text" name="title" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">รายละเอียด</label>
            <textarea name="message" class="form-control" rows="4" required></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
          <button type="submit" class="btn btn-danger">ส่งเรื่อง</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="../bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
