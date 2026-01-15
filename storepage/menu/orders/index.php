<?php


/* ===== AUTH ===== */
if (
    !isset($_SESSION['user_id']) ||
    !in_array($_SESSION['role'], ['store_owner','staff']) ||
    !isset($_SESSION['store_id'])
) {
    die('no permission');
}

$user_id  = $_SESSION['user_id'];
$store_id = $_SESSION['store_id'];

/* ===== ORDERS ===== */
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
$stmt->execute([
    ':store_id' => $_SESSION['store_id']
]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ===== STATUS UI ===== */
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
<link rel="icon" href="../../../image/3.jpg">

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

.empty-box{
    padding:60px 20px;
    text-align:center;
    color:#6c757d;
}
.empty-box i{
    font-size:48px;
    margin-bottom:12px;
    opacity:.6;
}
</style>
</head>

<body>

<div class="container py-4">

<!-- ===== HEADER ===== -->
<div class="page-header">
    <div>
        <h4 class="fw-bold mb-0">📦 งานซักของร้าน</h4>
        <small class="text-muted">
            ทั้งหมด <?= count($orders) ?> งาน
        </small>
    </div>
</div>

<div class="card shadow-sm">
<div class="card-body p-0">

<table class="table align-middle mb-0">
<thead>
<tr>
    <th width="50">#</th>
    <th>เลขงาน</th>
    <th>ลูกค้า</th>
    <th>สถานะ</th>
    <th>วันที่สร้าง</th>
    <th width="140"></th>
</tr>
</thead>
<tbody>

<?php if (!$orders): ?>
<tr>
<td colspan="6">
    <div class="empty-box">
        <i class="bi bi-inbox"></i>
        <div class="fw-semibold">ยังไม่มีงานซัก</div>
        <small>เมื่อมีลูกค้าสั่ง ระบบจะแสดงที่นี่</small>
    </div>
</td>
</tr>
<?php endif; ?>

<?php foreach ($orders as $i => $o): ?>
<tr>
    <td><?= $i+1 ?></td>

    <td class="order-id">
        <?= htmlspecialchars($o['order_number']) ?>
    </td>

    <td>
        <?= htmlspecialchars($o['customer_name'] ?? '-') ?>
    </td>

    <td>
        <span class="badge bg-<?= status_badge($o['status']) ?>">
            <?= status_label($o['status']) ?>
        </span>
    </td>

    <td>
        <?= date('d/m/Y H:i', strtotime($o['created_at'])) ?>
    </td>

    <td class="text-end">
        <a href="menu/orders/detail.php?id=<?= $o['id'] ?>"
           class="btn btn-sm btn-outline-primary">
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
// hover effect เพิ่มความรู้สึกเป็นระบบจริง
document.querySelectorAll('tbody tr').forEach(row=>{
    row.addEventListener('mouseenter',()=>row.style.cursor='pointer');
});
</script>

</body>
</html>
