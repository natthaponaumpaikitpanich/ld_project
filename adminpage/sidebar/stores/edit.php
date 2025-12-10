<?php
include_once '../../index.php';

$id = $_GET['id'] ?? null;
if(!$id) exit;

$stmt = $pdo->prepare("SELECT * FROM stores WHERE id=?");
$stmt->execute([$id]);
$store = $stmt->fetch();

if($_SERVER['REQUEST_METHOD']=='POST'){
    $sql = $pdo->prepare("
        UPDATE stores SET 
        name=?, phone=?, address=?, status=? 
        WHERE id=?
    ");
    $sql->execute([
        $_POST['name'],
        $_POST['phone'],
        $_POST['address'],
        $_POST['status'],
        $id
    ]);

    header("Location: ../sidebar.php?link=allstore");
    exit;
}
?>

<form method="post" class="container mt-5">
    <h4>แก้ไขร้านค้า</h4>

    <input class="form-control mb-2" name="name" value="<?= $store['name'] ?>" required>
    <input class="form-control mb-2" name="phone" value="<?= $store['phone'] ?>">
    <textarea class="form-control mb-2" name="address"><?= $store['address'] ?></textarea>

    <select name="status" class="form-select mb-3">
        <option value="active" <?= $store['status']=='active'?'selected':'' ?>>Active</option>
        <option value="pending" <?= $store['status']=='pending'?'selected':'' ?>>Pending</option>
        <option value="disabled" <?= $store['status']=='disabled'?'selected':'' ?>>Disabled</option>
    </select>

    <button class="btn btn-success">บันทึก</button>
</form>
