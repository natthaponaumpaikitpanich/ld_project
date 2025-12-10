<?php
include_once "../../index.php";
?>
<form method="post">
    <div class="mb-3">
        <label>ชื่อแพ็กเกจ</label>
        <input type="text" name="name" class="form-control" required>
    </div>

    <div class="mb-3">
        <label>ราคา / เดือน</label>
        <input type="number" name="price" class="form-control" required>
    </div>

    <div class="mb-3">
        <label>รายละเอียด</label>
        <textarea name="description" class="form-control"></textarea>
    </div>

    <button class="btn btn-success">บันทึก</button>
</form>
<?php
if($_POST){
    $stmt = $pdo->prepare("
        INSERT INTO billing_plans (name,price,description)
        VALUES (?,?,?)
    ");
    $stmt->execute([
        $_POST['name'],
        $_POST['price'],
        $_POST['description']
    ]);
    header("Location: ../sidebar.php?link=setting");
}

?>