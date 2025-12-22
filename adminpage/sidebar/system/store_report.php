
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
";
$sqll = "
SELECT
    s.id,
    s.name,
    s.phone
    FROM stores s";

$stmt = $pdo->prepare($sqll);
$stmt->execute([]);
$store = $stmt->fetch(PDO::FETCH_ASSOC);
$stmt = $pdo->prepare($sql);
$stmt->execute([]);
$report = $stmt->fetch(PDO::FETCH_ASSOC);

?>

<div class="container mt-4">
    <h4 class="mb-3">📩 รายละเอียดแจ้งปัญหา</h4>
    <div class="card shadow">
        <div class="card-body">
         
            <p><strong>ร้าน:</strong> <?= htmlspecialchars($store['name'] ?? 'ไม่ระบุชื่อ') ?></p>
            <p><strong>โทร:</strong> <?= htmlspecialchars($store['phone'] ?? 'ไม่ระบุเบอร์โทร') ?></p>
            <p><strong>หัวข้อ:</strong> <?= htmlspecialchars($report['title']) ?></p>

            <p><strong>รายละเอียด:</strong><br>
                <?= nl2br(htmlspecialchars($report['message'])) ?>
            </p>

            <p><strong>วันที่แจ้ง:</strong>
                <?= date('d/m/Y H:i', strtotime($report['created_at'])) ?>
            </p>

            <div class="mt-4 d-flex gap-2">
                <a href="system/report_action.php"
                   class="btn btn-success">
                    ✅ รับเรื่อง
                </a>

                <a href="system/report_action.php"
                   class="btn btn-danger">
                    ❌ ไม่อนุมัติ
                </a>
            </div>
        </div>
    </div>
</div>

