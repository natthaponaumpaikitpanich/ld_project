
<?php
$sql = "
SELECT
    r.id,
    r.title,
    r.message,
    r.status,
    r.created_at,
    s.name AS store_name,
    s.phone
FROM reports r
LEFT JOIN stores s ON r.store_id = s.id
ORDER BY r.created_at DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute();
$reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
$stmt = $pdo->query("
    SELECT r.*, s.name AS store_name, s.phone
    FROM reports r
    LEFT JOIN stores s ON r.store_id = s.id
    ORDER BY r.created_at DESC
    LIMIT 1
");

$report = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$report) {
    echo "<div class='alert alert-secondary text-center'>
            ไม่มีการรายงานปัญหา
          </div>";
    return;
}
?>

<?php foreach ($reports as $report): ?>
<div class="container mt-4">
    <div class="card shadow mb-3">
        <div class="card-body">
            <p><strong>ร้าน:</strong> <?= htmlspecialchars($report['store_name'] ?? 'ไม่ระบุ') ?></p>
            <p><strong>โทร:</strong> <?= htmlspecialchars($report['phone'] ?? '-') ?></p>
            <p><strong>หัวข้อ:</strong> <?= htmlspecialchars($report['title']) ?></p>

            <p><strong>รายละเอียด:</strong><br>
                <?= nl2br(htmlspecialchars($report['message'])) ?>
            </p>

            <p><strong>วันที่แจ้ง:</strong>
                <?= date('d/m/Y H:i', strtotime($report['created_at'])) ?>
            </p>

            <div class="mt-4 d-flex gap-2">
    <a href="system/report_action.php?action=accept&id=<?= $report['id'] ?>"
   class="btn btn-success">
   ✅ รับเรื่อง
</a>

<a href="system/report_action.php?action=reject&id=<?= $report['id'] ?>"
   class="btn btn-danger">
   ❌ ไม่อนุมัติ
</a>
</div>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>
<?php ?>
