<?php
require_once '../../../ld_db.php';

/* ===== CHECK ID ===== */
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("ไม่พบแพ็กเกจที่ต้องการลบ");
}

$id = $_GET['id'];

/* ===== CHECK PLAN EXISTS ===== */
$stmt = $pdo->prepare("
    SELECT id, name, status
    FROM billing_plans
    WHERE id = ?
");
$stmt->execute([$id]);
$plan = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$plan) {
    die("ไม่พบข้อมูลแพ็กเกจ");
}

/* ===== CHECK SUBSCRIPTIONS USING THIS PLAN ===== */
$check = $pdo->prepare("
    SELECT COUNT(*)
    FROM store_subscriptions
    WHERE plan_id = ?
      AND status IN ('active','waiting_approve','expired')
");
$check->execute([$id]);
$inUse = (int)$check->fetchColumn();

/* ===== IF IN USE → DO NOT DELETE, JUST INACTIVE ===== */
if ($inUse > 0) {

    $stmt = $pdo->prepare("
        UPDATE billing_plans
        SET status = 'inactive', updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$id]);

    header("Location: ../sidebar.php?link=setting&msg=plan_inactivated");
    exit;
}

/* ===== IF NOT IN USE → SAFE TO INACTIVE (NOT DELETE) ===== */
$stmt = $pdo->prepare("
    UPDATE billing_plans
    SET status = 'inactive', updated_at = NOW()
    WHERE id = ?
");
$stmt->execute([$id]);

header("Location: ../sidebar.php?link=setting&msg=plan_inactivated");
exit;
