<?php
if (
    !isset($_SESSION['user_id']) ||
    !in_array($_SESSION['role'], ['store_owner','staff']) ||
    !isset($_SESSION['store_id'])
) {
    die('no permission');
}

$user_id  = $_SESSION['user_id'];
$store_id = $_SESSION['store_id'];

$stmt = $pdo->prepare("
    SELECT
        o.id,
        o.order_number,
        o.status,
        o.notes,
        o.created_at,
        u.display_name AS customer_name
    FROM orders o
    LEFT JOIN users u ON u.id = o.customer_id
    WHERE o.store_id = :store_id
    ORDER BY o.created_at DESC
");
$stmt->execute([':store_id' => $_SESSION['store_id']]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

function status_badge($s) {
    return match($s) {
        'created'          => 'secondary',
        'picked_up'        => 'info',
        'in_process'       => 'warning',
        'ready'            => 'primary',
        'out_for_delivery' => 'dark',
        'completed'        => 'success',
        'cancelled'        => 'danger',
        default            => 'secondary'
    };
}
function status_label($s) {
    return match($s) {
        'created'          => 'สร้างงาน',
        'picked_up'        => 'รับผ้าแล้ว',
        'in_process'       => 'กำลังซัก',
        'ready'            => 'พร้อมส่ง',
        'out_for_delivery' => 'กำลังจัดส่ง',
        'completed'        => 'เสร็จสิ้น',
        'cancelled'        => 'ยกเลิก',
        default            => '-'
    };
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>งานซักของร้าน</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="../../../bootstrap/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

<style>
body {
    background:#f5f7fb;
    font-family:'Kanit',sans-serif;
}
.page-header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:16px;
}
.card {
    border:none;
    border-radius:14px;
}
.table thead th {
    font-weight:600;
    background:#f1f3f8;
}
.table tbody tr {
    transition:.15s;
}
.table tbody tr:hover {
    background:#f9fbff;
}
.order-id {
    font-weight:600;
}

/* ==== STATUS STRIP ==== */
tr[data-status="created"]{
    border-left:5px solid #6b7cb3;
}

tr[data-status="picked_up"],
tr[data-status="in_process"]{
    border-left:5px solid #5fa9ff;
}

tr[data-status="ready"]{
    border-left:5px solid #2a5298;
}

tr[data-status="completed"]{
    border-left:5px solid #1e3c72;
}


/* ==== DASHBOARD ==== */
.stat-box{
    border-radius:16px;
    padding:18px;
    color:#fff;
    box-shadow:0 10px 20px rgba(0,0,0,.08);
}
.stat-title{font-size:13px;opacity:.9}
.stat-value{font-size:28px;font-weight:700}
.stat-created{
    background:linear-gradient(135deg,#5981E6,#B4BBFA);
}

.stat-process{
    
   background:linear-gradient(135deg,#3F81CA,#8a9be1);
    
}

.stat-ready{
    background:linear-gradient(135deg,#1e3c72,#2a5298);
}

.stat-done{
    background:linear-gradient(135deg,#162447,#1e3c72);
}

</style>
</head>

<body>
<div class="container py-4">

<div class="page-header">
    <div>
        <h4 class="fw-bold mb-0">📦 งานซักของร้าน</h4>
        <small class="text-muted">ทั้งหมด <?= count($orders) ?> งาน</small>
    </div>
</div>

<!-- DASHBOARD -->
<div class="row g-2 mb-3">
    <div class="col-md-3">
        <div class="stat-box stat-created">
            <div class="stat-title">งานใหม่</div>
            <div class="stat-value" id="count-created">0</div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="stat-box stat-process">
            <div class="stat-title">กำลังซัก</div>
            <div class="stat-value" id="count-process">0</div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="stat-box stat-ready">
            <div class="stat-title">พร้อมส่ง</div>
            <div class="stat-value" id="count-ready">0</div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="stat-box stat-done">
            <div class="stat-title">เสร็จแล้ว</div>
            <div class="stat-value" id="count-done">0</div>
        </div>
    </div>
</div>


<div class="card shadow-sm">
<div class="card-body p-0">

<table class="table align-middle mb-0">
<thead>
<tr>
    <th>#</th>
    <th>เลขงาน</th>
    <th>ลูกค้า</th>
    <th>สถานะ</th>
    <th>วันที่</th>
    <th></th>
</tr>
</thead>
<tbody>

<?php foreach ($orders as $i => $o): ?>
<tr data-status="<?= $o['status'] ?>">
    <td><?= $i+1 ?></td>
    <td class="order-id"><?= htmlspecialchars($o['order_number']) ?></td>
    <td><?= htmlspecialchars($o['customer_name'] ?? '-') ?></td>
    <td><span class="badge bg-<?= status_badge($o['status']) ?>"><?= status_label($o['status']) ?></span></td>
    <td><?= date('d/m/Y H:i', strtotime($o['created_at'])) ?></td>
    <td class="text-end">
        <a href="menu/orders/detail.php?id=<?= $o['id'] ?>" class="btn btn-sm btn-outline-primary">
            <i class="bi bi-gear"></i> จัดการ
        </a>
    </td>
</tr>
<?php endforeach; ?>

</tbody>
</table>

</div>
</div>

</div>

<script src="../../../bootstrap/js/bootstrap.bundle.min.js"></script>

<script>
let created=0,process=0,ready=0,done=0;
document.querySelectorAll("tr[data-status]").forEach(r=>{
    const s=r.dataset.status;
    if(s==="created") created++;
    if(s==="picked_up"||s==="in_process") process++;
    if(s==="ready") ready++;
    if(s==="completed") done++;
});
document.getElementById("count-created").innerText = created;
document.getElementById("count-process").innerText = process;
document.getElementById("count-ready").innerText   = ready;
document.getElementById("count-done").innerText    = done;

</script>

</body>
</html>
