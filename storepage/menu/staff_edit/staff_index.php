<?php
$store_id = $_SESSION['store_id'] ?? null;
if (!$store_id) {
    die("ไม่พบร้าน");
}

/* ---------- ดึงพนักงาน ---------- */
$stmt = $pdo->prepare("
    SELECT 
        ss.id AS staff_id,
        u.display_name,
        u.email,
        u.phone,
        u.profile_image,
        ss.role,
        ss.created_at
    FROM store_staff ss
    JOIN users u ON ss.user_id = u.id
    WHERE ss.store_id = ?
      AND ss.role != 'store_owner'
    ORDER BY ss.created_at DESC
");
$stmt->execute([$store_id]);
$staffs = $stmt->fetchAll(PDO::FETCH_ASSOC);

function staff_image($img){
    if ($img) {
        $path = '/ld_project/' . ltrim($img,'/');
        if (file_exists($_SERVER['DOCUMENT_ROOT'].$path)) {
            return $path;
        }
    }
    return '/ld_project/assets/img/user.png';
}
?>

<div class="container mt-4">

<h4 class="fw-bold mb-3">👥 จัดการพนักงานร้าน</h4>

<button class="btn btn-primary mb-3"
        data-bs-toggle="modal"
        data-bs-target="#addStaffModal">
    ➕ เพิ่มพนักงาน
</button>

<div class="card shadow-sm border-0">
<div class="card-body p-0">

<table class="table table-hover align-middle mb-0">
<thead class="table-light">
<tr>
    <th>พนักงาน</th>
    <th>เบอร์โทร</th>
    <th>บทบาท</th>
    <th>วันที่เพิ่ม</th>
    <th class="text-end"></th>
</tr>
</thead>

<tbody>

<?php if (empty($staffs)): ?>
<tr>
    <td colspan="5" class="text-center text-muted py-4">
        ยังไม่มีพนักงาน
    </td>
</tr>
<?php endif; ?>

<?php foreach ($staffs as $s): ?>
<tr>

<td>
    <div class="d-flex align-items-center gap-3">
        <img src="<?= staff_image($s['profile_image']) ?>"
             class="rounded-circle"
             style="width:44px;height:44px;object-fit:cover">

        <div>
            <div class="fw-semibold">
                <?= htmlspecialchars($s['display_name']) ?>
            </div>
            <small class="text-muted">
                <?= htmlspecialchars($s['email']) ?>
            </small>
        </div>
    </div>
</td>

<td><?= htmlspecialchars($s['phone']) ?></td>

<td>
    <span class="badge rounded-pill bg-info">
        <?= strtoupper($s['role']) ?>
    </span>
</td>

<td class="text-muted">
    <?= date('d/m/Y', strtotime($s['created_at'])) ?>
</td>

<td class="text-end">
    <a href="menu/staff_edit/staff_delete.php?id=<?= $s['staff_id'] ?>"
       class="btn btn-sm btn-outline-danger"
       onclick="return confirm('ลบพนักงานคนนี้?')">
       ลบ
    </a>
</td>

</tr>
<?php endforeach; ?>

</tbody>
</table>

</div>
</div>
</div>

<!-- ===== Add Staff Modal ===== -->
<div class="modal fade" id="addStaffModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">

      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title">➕ เพิ่มพนักงาน</h5>
        <button type="button"
                class="btn-close btn-close-white"
                data-bs-dismiss="modal"></button>
      </div>

      <form method="post" action="menu/staff_edit/staff_add.php">
        <div class="modal-body">

          <div class="mb-3">
            <label class="form-label">อีเมลพนักงาน</label>
            <input type="email"
                   name="email"
                   class="form-control"
                   required>
          </div>

          <div class="mb-3">
            <label class="form-label">เบอร์โทร</label>
            <input type="text"
                   name="phone"
                   class="form-control"
                   required>
          </div>

        </div>

        <div class="modal-footer">
          <button type="button"
                  class="btn btn-secondary"
                  data-bs-dismiss="modal">
            ยกเลิก
          </button>
          <button type="submit"
                  class="btn btn-primary">
            บันทึก
          </button>
        </div>
      </form>

    </div>
  </div>
</div>
