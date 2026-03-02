<?php
session_start();
require_once "../../../ld_db.php";

$store_id     = $_SESSION['store_id'] ?? null;
$plan_id      = $_POST['plan_id'] ?? null;
$promotion_id = $_POST['promotion_id'] ?? null; // รับค่า promotion_id ที่ส่งมาจากฟอร์ม

if (!$store_id || !$plan_id) {
    die('ข้อมูลไม่ครบ');
}

/* ===== 1. เริ่ม Transaction เพื่อความปลอดภัยของข้อมูล ===== */
$pdo->beginTransaction();

try {
    /* ===== 2. กันสมัครซ้ำ ===== */
    $chk = $pdo->prepare("
        SELECT id
        FROM store_subscriptions
        WHERE store_id = ?
          AND status IN ('waiting_approve','active')
        FOR UPDATE 
    "); // FOR UPDATE เพื่อ lock row ไว้กันการส่งซ้ำพร้อมกัน
    $chk->execute([$store_id]);

    if ($chk->fetch()) {
        throw new Exception('คุณได้ส่งคำขอสมัครไปแล้ว');
    }

    /* ===== 3. ตรวจสอบและตัดสิทธิ์โปรโมชั่น (ถ้ามีการใช้) ===== */
    if (!empty($promotion_id)) {
        // ตรวจสอบว่าโปรโมชั่นยังเหลือสิทธิ์ไหม
        $pStmt = $pdo->prepare("
            SELECT usage_limit, used_count 
            FROM promotions 
            WHERE id = ? AND status = 'active'
            FOR UPDATE
        ");
        $pStmt->execute([$promotion_id]);
        $promo = $pStmt->fetch();

        if ($promo) {
            // ถ้ามีการจำกัดสิทธิ์ และสิทธิ์เต็มแล้ว
            if ($promo['usage_limit'] !== null && $promo['used_count'] >= $promo['usage_limit']) {
                throw new Exception('ขออภัย สิทธิ์โปรโมชั่นนี้เต็มแล้ว');
            }

            // สั่งอัปเดตจำนวนการใช้งาน (+1)
            $updatePromo = $pdo->prepare("
                UPDATE promotions 
                SET used_count = used_count + 1 
                WHERE id = ?
            ");
            $updatePromo->execute([$promotion_id]);
        }
    }

    /* ===== 4. ตรวจไฟล์สลิป ===== */
    if (empty($_FILES['slip_image']['name'])) {
        throw new Exception('กรุณาอัปโหลดสลิป');
    }

    /* ===== 5. upload slip ===== */
    $uploadDir = "../../../uploads/slips/";
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $ext = pathinfo($_FILES['slip_image']['name'], PATHINFO_EXTENSION);
    $filename = 'slip_' . time() . '_' . rand(100,999) . '.' . $ext;
    $filepath = $uploadDir . $filename;

    if (!move_uploaded_file($_FILES['slip_image']['tmp_name'], $filepath)) {
        throw new Exception('อัปโหลดไฟล์ไม่สำเร็จ');
    }

    /* ===== 6. insert subscription ===== */
    $stmt = $pdo->prepare("
        INSERT INTO store_subscriptions
        (
            id,
            store_id,
            plan_id,
            promotion_id, -- เพิ่มฟิลด์นี้เพื่อให้รู้ว่าการสมัครนี้ใช้โปรโมชั่นอะไร
            plan,
            monthly_fee,
            slip_image,
            paid_at,
            status,
            created_at
        )
        SELECT
            UUID(),
            :store_id,
            p.id,
            :promo_id,
            p.name,
            p.amount,
            :slip_image,
            NOW(),
            'waiting_approve',
            NOW()
        FROM billing_plans p
        WHERE p.id = :plan_id
    ");

    $stmt->execute([
        ':store_id'   => $store_id,
        ':plan_id'    => $plan_id,
        ':promo_id'   => $promotion_id, // ใส่ค่า promotion_id ลงไปในประวัติการสมัครด้วย
        ':slip_image' => 'uploads/slips/' . $filename
    ]);

    // ถ้าทุกอย่างถูกต้อง บันทึกลง Database จริงๆ
    $pdo->commit();

    header("Location: ../../index.php");
    exit;

} catch (Exception $e) {
    // ถ้ามีอะไรผิดพลาด ยกเลิกทั้งหมดที่ทำมา (Rollback)
    $pdo->rollBack();
    die("เกิดข้อผิดพลาด: " . $e->getMessage());
}