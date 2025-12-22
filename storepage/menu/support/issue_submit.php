    <?php
    session_start();
    require_once "../../../ld_db.php";

    $store_id = $_SESSION['store_id'] ?? null;

    if (!$store_id) {
        die("ไม่พบข้อมูลร้าน");
    }

    $title = $_POST['title'];
    $message = $_POST['message'];

    $sql = $pdo->prepare("
        INSERT INTO reports (store_id, title, message, status, created_at)
        VALUES (?, ?, ?, 'new', NOW())
    ");

    $sql->execute([$store_id, $title, $message]);

    header("Location: ../../index.php?link=orders");
    exit;