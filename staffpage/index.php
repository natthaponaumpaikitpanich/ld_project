<?php
session_start();
require_once "../ld_db.php"; // PDO
include_once "assets/boostap.php";
// --------------------
// ตรวจสิทธิ์
// --------------------
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    die('ไม่มีสิทธิ์เข้าถึงหน้านี้');
}
$user_id = $_SESSION['user_id'];

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

?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>งานจัดส่ง / รับผ้า</title>
    <style>
      body {
        background:#f4f6f9;
      }
        .staff-bottom-nav {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    height: 64px;
    background: #fff;
    border-top: 1px solid #eee;
    display: flex;
    justify-content: space-around;
    align-items: center;
    z-index: 999;
}

.staff-bottom-nav a {
    text-decoration: none;
    color: #999;
    font-size: 12px;
    display: flex;
    flex-direction: column;
    align-items: center;
}

.staff-bottom-nav a i {
    font-size: 20px;
    margin-bottom: 2px;
}


    </style>
    
</head>
<body>
  <div class="mt-3 d-flex justify-content-end p-3 ">
    <a href="../loginpage/logout.php"><div class="btn btn-danger bi bi-box-arrow-left"> ออกจากระบบ</div></a>
    </div>
<?php include_once "body.php"; ?>
<nav class="staff-bottom-nav">
  <a href="index.php?link=Home" class="active">
    <i class="bi bi-house"></i>
    <span>Home</span>
  </a>
  <a href="index.php?link=Tasks" class="active">
    <i class="bi bi-list-task"></i>
    <span>Tasks</span>
  </a>
  <a href="index.php?link=Map">
    <i class="bi bi-map"></i>
    <span>Map</span>
  </a>

  <a href="index.php?link=Profile">
    <i class="bi bi-person"></i>
    <span>Profile</span>
  </a>
</nav>
<script src="../bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
                                      