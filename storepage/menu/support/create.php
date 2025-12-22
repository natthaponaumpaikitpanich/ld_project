<?php
$store_id = $_SESSION['store_id']; // ต้อง set ตอน login
$subject = $_POST['subject'];
$message = $_POST['message'];

$image = null;
if (!empty($_FILES['image']['name'])) {
    $dir = "uploads/";
    if (!is_dir($dir)) mkdir($dir, 0777, true);

    $image = time().'_'.$_FILES['image']['name'];
    move_uploaded_file($_FILES['image']['tmp_name'], $dir.$image);
}

$sql = $pdo->prepare("
    INSERT INTO support_tickets (id, store_id, subject, message, image)
    VALUES (UUID(), ?, ?, ?, ?)
");
$sql->execute([$store_id, $subject, $message, $image]);

header("Location: index.php?success=1");
exit;
?>
<h4>📩 แจ้งปัญหาถึงแอดมิน</h4>

<form method="post" enctype="multipart/form-data">
  <div class="mb-3">
    <label>หัวข้อ</label>
    <input type="text" name="subject" class="form-control" required>
  </div>

  <div class="mb-3">
    <label>รายละเอียดปัญหา</label>
    <textarea name="message" class="form-control" rows="4" required></textarea>
  </div>

  <div class="mb-3">
    <label>แนบรูป (ถ้ามี)</label>
    <input type="file" name="image" class="form-control">
  </div>

  <button class="btn btn-primary">ส่งเรื่อง</button>
</form>

