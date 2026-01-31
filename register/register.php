<?php session_start(); ?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>สมัครสมาชิก | Laundry Platform</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<link href="../bootstrap/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="icon" href="../image/3.jpg">

<style>
body {
    font-family: 'Kanit', sans-serif;
    background: linear-gradient(135deg, rgb(45, 182, 255), #2a5298);
    min-height: 100vh;
    display: flex;
    align-items: center;
}

.register-card {
    background: rgba(255,255,255,0.96);
    border-radius: 18px;
    max-width: 460px;
    width: 100%;
    margin: auto;
    box-shadow: 0 20px 40px rgba(0,0,0,.15);
    animation: fadeUp .6s ease;
}

@keyframes fadeUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

.card-body {
    padding: 2rem;
}

.form-control, .form-select {
    border-radius: 10px;
}

.form-control:focus {
    border-color: #2a5298;
    box-shadow: 0 0 0 .2rem rgba(42,82,152,.25);
}

.btn-main {
    background: #2a5298;
    color: #fff;
    border-radius: 12px;
    font-weight: 500;
}

.btn-main:hover {
    background: #1e3c72;
}

.role-btn {
    border-radius: 14px;
    padding: 14px;
    font-size: 1rem;
    font-weight: 500;
}

.brand {
    text-align: center;
    margin-bottom: 1.5rem;
}

.brand h4 {
    font-weight: 700;
    color: #2a5298;
}

.small-link {
    font-size: 14px;
}
</style>
</head>

<body>

<div class="register-card">
<div class="card-body">

    <div class="brand">
        <h4>สมัครสมาชิก</h4>
        <small class="text-muted">Laundry Management Platform</small>
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
            <label class="form-label">ชื่อผู้ใช้งาน</label>
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
                      placeholder="เช่น ที่อยู่ หรือหมายเหตุ"></textarea>
        </div>

        <input type="hidden" name="role" id="roleInput">

        <button type="button"
                class="btn btn-main w-100 py-2"
                data-bs-toggle="modal"
                data-bs-target="#roleModal">
            สมัครสมาชิก
        </button>
    </form>

    <div class="text-center mt-3 small-link">
        มีบัญชีแล้ว?
        <a href="../loginpage/login.php">เข้าสู่ระบบ</a>
    </div>

</div>
</div>

<!-- MODAL ROLE -->
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
            🧑‍🔧 พนักงานร้าน
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

<script src="../bootstrap/js/bootstrap.bundle.js"></script>
</body>
</html>
