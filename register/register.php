<?php
session_start();
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>สมัครสมาชิก</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<link href="../bootstrap/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="icon" href="../image/3.jpg">
<style>
body {
    font-family: 'Kanit', sans-serif;
    background: linear-gradient(135deg, #4eaadfff, #1cc88a);
    min-height: 100vh;
    display: flex;
    align-items: center;
}

.register-card {
    max-width: 460px;
    width: 100%;
    margin: auto;
    border-radius: 18px;
}

.register-card .card-body {
    padding: 2rem;
}

.form-control, .form-select {
    border-radius: 10px;
}

.btn-primary {
    border-radius: 12px;
    font-weight: 500;
}

.role-btn {
    border-radius: 14px;
    padding: 14px;
    font-size: 1rem;
}
</style>
</head>
<body>

<div class="card register-card shadow-lg">
<div class="card-body">

    <div class="text-center mb-4">
        <h4 class="fw-bold mb-1">🧺 สมัครสมาชิก</h4>
        <small class="text-muted">ระบบร้านซักอบรีด</small>
    </div>

    <form id="registerForm"
          method="post"
          action="register_action.php"
          enctype="multipart/form-data">

        <!-- รูป -->
        <div class="mb-3">
            <label class="form-label">รูปโปรไฟล์ (ไม่บังคับ)</label>
            <input type="file" name="profile_image" class="form-control">
        </div>

        <!-- ชื่อ -->
        <div class="mb-3">
            <label class="form-label">ชื่อ</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-person"></i></span>
                <input type="text" name="display_name" class="form-control" required>
            </div>
        </div>

        <!-- โทร -->
        <div class="mb-3">
            <label class="form-label">เบอร์โทร</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                <input type="text" name="phone" class="form-control">
            </div>
        </div>

        <!-- email -->
        <div class="mb-3">
            <label class="form-label">อีเมล</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                <input type="email" name="email" class="form-control" required>
            </div>
        </div>

        <!-- password -->
        <div class="mb-3">
            <label class="form-label">รหัสผ่าน</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                <input type="password" name="password" class="form-control" required>
            </div>
        </div>

        <!-- detail -->
        <div class="mb-3">
            <label class="form-label">รายละเอียดเพิ่มเติม (ไม่บังคับ)</label>
            <textarea name="detail"
                      class="form-control"
                      rows="2"
                      placeholder="เช่น ที่อยู่, หมายเหตุ"></textarea>
        </div>

        <!-- role -->
        <input type="hidden" name="role" id="roleInput">

        <button type="button"
                class="btn btn-primary w-100 py-2"
                data-bs-toggle="modal"
                data-bs-target="#roleModal">
            สมัครสมาชิก
        </button>
    </form>

    <p class="text-center mt-3 mb-0">
        มีบัญชีแล้ว? <a href="../loginpage/login.php">เข้าสู่ระบบ</a>
    </p>

</div>
</div>

<!-- MODAL -->
<div class="modal fade" id="roleModal" tabindex="-1">
<div class="modal-dialog modal-dialog-centered">
<div class="modal-content rounded-4">

    <div class="modal-header border-0">
        <h5 class="modal-title fw-bold">เลือกสถานะผู้ใช้งาน</h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
    </div>

    <div class="modal-body text-center">

        <button class="btn btn-outline-primary w-100 mb-3 role-btn"
                onclick="submitRole('customer')">
            👤 ลูกค้า
        </button>

        <button class="btn btn-outline-success w-100 mb-3 role-btn"
                onclick="submitRole('staff')">
            🧑‍🔧 พนักงาน
        </button>

        <button class="btn btn-outline-warning w-100 role-btn"
                onclick="submitRole('store_owner')">
            🏪 เจ้าของร้าน
        </button>

    </div>

</div>
</div>
</div>

<script>
function submitRole(role) {
    document.getElementById('roleInput').value = role;
    document.getElementById('registerForm').submit();
}
</script>

<script src="../bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
