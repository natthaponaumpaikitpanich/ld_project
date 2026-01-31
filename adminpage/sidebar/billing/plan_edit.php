<?php
include "../../../ld_db.php";

if (!isset($_GET['id'])) {
    die("ไม่พบแพ็กเกจที่ต้องการแก้ไข");
}

$id = $_GET['id'];

$stmt = $pdo->prepare("SELECT * FROM billing_plans WHERE id = ?");
$stmt->execute([$id]);
$plan = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$plan) {
    die("ไม่พบข้อมูลแพ็กเกจ");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name']);
    $price    = trim($_POST['price']);
    $duration = trim($_POST['duration']);
    $status   = $_POST['status'];

    $sql = "UPDATE billing_plans 
            SET name=?, price=?, duration=?, status=?, updated_at=NOW()
            WHERE id=?";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$name, $price, $duration, $status, $id]);

    header("Location: ../sidebar.php?link=setting");
    exit;
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edit Plan | <?= htmlspecialchars($plan['name']) ?></title>

    <link href="../../../bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="../../../bootstrap/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Anuphan:wght@300;400;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --edit-theme:  rgba(255, 255, 255, 0.95);
            --edit-gradient: linear-gradient(135deg, #6366f1 0%, #4338ca 100%);
        }

        body {
            background: #f8fafc;
            font-family: 'Anuphan', sans-serif;
            overflow-x: hidden;
        }

        .edit-container {
            max-width: 850px;
            margin: 60px auto;
            position: relative;
        }

        /* กล่องแก้ไขสไตล์ Modern */
        .premium-card {
            border: none;
            border-radius: 30px;
            background: #ffffff;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }

        /* Header แบบ Glassmorphism */
        .edit-header {
            background: var(--edit-gradient);
            padding: 40px;
            color: white;
            position: relative;
        }

        .edit-header::after {
            content: "";
            position: absolute;
            bottom: -20px;
            left: 0;
            right: 0;
            height: 40px;
            background: white;
            border-radius: 50% 50% 0 0;
        }

        .plan-badge-top {
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            padding: 8px 20px;
            border-radius: 50px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            font-size: 0.9rem;
        }

        /* Status Pulse Animation */
        .status-pulse {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 8px;
            position: relative;
        }

        .pulse-active {
            background: #10b981;
            box-shadow: 0 0 0 rgba(16, 185, 129, 0.4);
            animation: pulse-green 2s infinite;
        }

        .pulse-inactive {
            background: #ef4444;
        }

        @keyframes pulse-green {
            0% {
                box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
            }

            70% {
                box-shadow: 0 0 0 10px rgba(16, 185, 129, 0);
            }

            100% {
                box-shadow: 0 0 0 0 rgba(16, 185, 129, 0);
            }
        }

        /* Form Styling */
        .form-label {
            font-weight: 700;
            color: #334155;
            margin-bottom: 10px;
        }

        .form-control,
        .form-select {
            border-radius: 15px;
            padding: 14px;
            border: 2px solid #f1f5f9;
            transition: all 0.3s;
        }

        .form-control:focus {
            border-color: var(--edit-theme);
           box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
        }

        /* ปุ่มบันทึกสุดอลัง */
        .btn-save {
            background: var(--edit-gradient);
            border: none;
            border-radius: 18px;
            padding: 15px 40px;
            color: white;
            font-weight: 700;
            font-size: 1.1rem;
            position: relative;
            overflow: hidden;
            transition: all 0.4s;
        }

        .btn-save:hover {
            transform: scale(1.02);
            box-shadow: 0 15px 30px -5px rgba(99, 102, 241, 0.4);
        }

        .btn-save::before {
            content: "";
            position: absolute;
            top: 0;
            left: -100%;
            width: 50%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
            transition: 0.5s;
        }

        .btn-save:hover::before {
            left: 150%;
        }

        .price-tag {
            font-size: 2rem;
            font-weight: 800;
            color: var(--edit-theme);
        }
    </style>
</head>

<body>

    <div class="edit-container">
        <div class="card premium-card border-0">
            <div class="edit-header text-center">
                <div class="mb-2">
                    <span class="plan-badge-top">
                        ID: #<?= str_pad($plan['id'], 4, '0', STR_PAD_LEFT) ?>
                    </span>
                </div>
                <h1 class="display-6 fw-bold mb-0">แก้ไขข้อมูลแพ็กเกจ</h1>
                <p class="opacity-75 mb-0">ปรับแต่งรายละเอียดและราคาตามกลยุทธ์ของคุณ</p>
            </div>

            <div class="card-body p-4 p-md-5">
                <form method="post" id="editForm">
                    <div class="row g-4">

                        <div class="col-md-8">
                            <div class="mb-4">
                                <label class="form-label">ชื่อแพ็กเกจที่ต้องการแสดง</label>
                                <input type="text" name="name" class="form-control form-control-lg"
                                    value="<?= htmlspecialchars($plan['name']) ?>" required>
                            </div>

                            <div>
                                <label class="form-label">สิทธิประโยชน์ (แยกตามบรรทัด)</label>
                                <textarea name="duration" rows="5" class="form-control"
                                    placeholder="ระบุสิ่งที่ลูกค้าจะได้รับ..."><?= htmlspecialchars($plan['duration']) ?></textarea>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="p-4 rounded-4 bg-light mb-4">
                                <label class="form-label d-block mb-3">สถานะปัจจุบัน</label>
                                <div class="mb-3 d-flex align-items-center">
                                    <span id="statusIcon" class="status-pulse <?= $plan['status'] == 'active' ? 'pulse-active' : 'pulse-inactive' ?>"></span>
                                    <strong id="statusText" class="text-uppercase"><?= $plan['status'] ?></strong>
                                </div>
                                <select name="status" class="form-select border-0 shadow-sm" id="statusSelect">
                                    <option value="active" <?= $plan['status'] == 'active' ? 'selected' : '' ?>>ใช้งาน (Active)</option>
                                    <option value="inactive" <?= $plan['status'] == 'inactive' ? 'selected' : '' ?>>ปิดไว้ (Inactive)</option>
                                </select>
                            </div>

                            <div class="p-4 rounded-4 bg-light">
                                <label class="form-label">ราคาปรับปรุง</label>
                                <div class="input-group mb-2">
                                    <span class="input-group-text border-0 bg-transparent ps-0">฿</span>
                                    <input type="number" name="price" class="form-control border-0 bg-transparent fs-2 fw-bold text-primary"
                                        value="<?= htmlspecialchars($plan['price']) ?>" required id="priceInput">
                                </div>
                                <small class="text-muted">ราคาเดิม: <?= number_format($plan['price'], 2) ?> บาท</small>
                            </div>
                        </div>

                        <div class="col-12 mt-5 border-top pt-4">
                            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
                                <a href="../sidebar.php?link=setting" class="btn btn-link text-decoration-none text-muted fw-bold">
                                    <i class="bi bi-x-circle me-2"></i> ยกเลิกการแก้ไข
                                </a>
                                <button type="submit" class="btn btn-save px-5 shadow">
                                    <i class="bi bi-cloud-check-fill me-2"></i> อัปเดตแพ็กเกจทันที
                                </button>
                            </div>
                        </div>

                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // ลูกเล่นเปลี่ยนสถานะ Real-time
        const statusSelect = document.getElementById('statusSelect');
        const statusIcon = document.getElementById('statusIcon');
        const statusText = document.getElementById('statusText');

        statusSelect.addEventListener('change', function() {
            if (this.value === 'active') {
                statusIcon.className = 'status-pulse pulse-active';
                statusText.innerText = 'Active';
                statusText.style.color = '#10b981';
            } else {
                statusIcon.className = 'status-pulse pulse-inactive';
                statusText.innerText = 'Inactive';
                statusText.style.color = '#ef4444';
            }
        });

        // แสดงแจ้งเตือนเมื่อราคามีการเปลี่ยนแปลง
        const priceInput = document.getElementById('priceInput');
        const originalPrice = <?= $plan['price'] ?>;

        priceInput.addEventListener('input', function() {
            if (parseFloat(this.value) > originalPrice) {
                this.style.color = '#10b981'; // สีเขียวถ้าแพงขึ้น
            } else if (parseFloat(this.value) < originalPrice) {
                this.style.color = '#ef4444'; // สีแดงถ้าถูกลง
            } else {
                this.style.color = '#f59e0b'; // สีส้มถ้าเท่าเดิม
            }
        });
    </script>

    <script src="../../../bootstrap/js/bootstrap.bundle.min.js"></script>
</body>

</html>