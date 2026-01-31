<?php
require_once "../../ld_db.php";

$stmt = $pdo->prepare("SELECT * FROM promotions ORDER BY created_at DESC");
$stmt->execute();
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <link rel="icon" href="../../image/3.jpg">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="../../bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@200;300;400;500;600&display=swap" rel="stylesheet">

    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <title>Promotion Management - Modern Admin</title>

    <style>
        body {
            font-family: 'Kanit', sans-serif;
            background-color: #f8f9fa;
        }

        .main-card {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(15px);
            border-radius: 25px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.3);
            margin-top: -60px;
        }

        .page-header {
            background: linear-gradient(135deg, #4361ee 0%, #7209b7 100%);
            padding: 80px 0 140px 0;
            color: white;
            position: relative;
            overflow: hidden;
        }

        /* ลูกเล่นวงกลมพื้นหลัง */
        .page-header::before {
            content: '';
            position: absolute;
            top: -50px;
            right: -50px;
            width: 300px;
            height: 300px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }

        .glass-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.02);
            transition: all 0.3s ease;
            border: 1px solid #f1f3f5;
        }

        .glass-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(67, 97, 238, 0.15);
        }

        .custom-table tbody tr {
            transition: all 0.2s;
            cursor: pointer;
        }

        .custom-table tbody tr:hover {
            background-color: rgba(67, 97, 238, 0.03) !important;
            transform: scale(1.005);
        }

        .promo-thumb {
            width: 55px;
            height: 55px;
            border-radius: 15px;
            object-fit: cover;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            transition: 0.3s;
        }

        .promo-thumb:hover {
            transform: scale(1.2);
        }

        /* ตกแต่ง Badge ให้ดูแพง */
        .badge-active {
            background: linear-gradient(45deg, #10b981, #34d399);
            color: white;
            border: none;
            box-shadow: 0 4px 10px rgba(16, 185, 129, 0.3);
        }

        .badge-draft {
            background: #e2e8f0;
            color: #475569;
            border: none;
        }

        /* ปรับแต่ง Search Box ของ DataTable */
        .dataTables_filter input {
            border-radius: 50px;
            padding: 8px 20px;
            border: 1px solid #dee2e6;
            outline: none;
        }
    </style>
</head>

<body>

    <div class="page-header">
        <div class="container-fluid px-5">
            <div class="d-flex justify-content-between align-items-center" data-aos="fade-down">
                <div>
                    <h1 class="fw-bold mb-1">
                        <i class="bi bi-megaphone-fill me-2"></i> Promotion Center
                    </h1>
                    <p class="mb-0 text-white-50">จัดการและติดตามแคมเปญโปรโมชั่นของคุณได้อย่างอัจฉริยะ</p>
                </div>
                <a href="../promotion/create.php" class="btn btn-light rounded-pill px-4 py-2 fw-bold shadow-sm hover-push">
                    <i class="bi bi-plus-circle-fill me-2 text-primary"></i> สร้างโปรโมชั่นใหม่
                </a>
            </div>
        </div>
    </div>

    <div class="container-fluid px-5 mb-5">
        <div class="main-card p-4" data-aos="fade-up" data-aos-delay="200">
            <div class="row mb-4 px-3">
                <div class="col-md-3">
                    <div class="glass-card p-4">
                        <div class="d-flex align-items-center">
                            <div class="bg-primary bg-opacity-10 p-3 rounded-circle me-3">
                                <i class="bi bi-grid-fill text-primary fs-4"></i>
                            </div>
                            <div>
                                <div class="text-muted small">โปรโมชั่นทั้งหมด</div>
                                <div class="h3 fw-bold mb-0 text-primary"><?= count($result) ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="table-container">
                <div class="table-responsive">
                    <table id="promoTable" class="table custom-table align-middle" style="width:100%">
                        <thead>
                            <tr>
                                <th>ชื่อโปรโมชั่น</th>
                                <th>ระยะเวลาแคมเปญ</th>
                                <th>กลุ่มลูกค้า</th>
                                <th>สถานะ</th>
                                <th class="text-end">การจัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($result as $row): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <?php if (!empty($row['image'])): ?>
                                                <img src="../../<?= $row['image'] ?>" class="promo-thumb me-3">
                                            <?php else: ?>
                                                <div class="promo-thumb me-3 bg-light d-flex align-items-center justify-content-center text-muted">
                                                    <i class="bi bi-image"></i>
                                                </div>
                                            <?php endif; ?>
                                            <div>
                                                <div class="fw-bold text-dark"><?= htmlspecialchars($row['title']) ?></div>
                                                <div class="text-muted small">ID: #<?= sprintf('%05d', $row['id']) ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="small">
                                            <span class="d-block"><i class="bi bi-calendar2-check text-primary me-2"></i><?= date('d M Y', strtotime($row['start_date'])) ?></span>
                                            <span class="text-muted"><i class="bi bi-arrow-right-short me-2"></i><?= date('d M Y', strtotime($row['end_date'])) ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge rounded-pill bg-light text-dark fw-normal border px-3">
                                            <i class="bi bi-people me-1"></i> <?= $row['audience'] === 'stores' ? 'ร้านค้าทั่วไป' : 'กลุ่มเฉพาะ' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($row['status'] === 'active'): ?>
                                            <span class="badge badge-active px-3 py-2"><i class="bi bi-lightning-charge-fill me-1"></i> Active</span>
                                        <?php else: ?>
                                            <span class="badge badge-draft px-3 py-2"><i class="bi bi-pause-fill me-1"></i> Draft</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group">
                                            <a href="../promotion/edit.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-primary border-0 rounded-circle mx-1 p-2" title="แก้ไข">
                                                <i class="bi bi-pencil-square fs-5"></i>
                                            </a>
                                           <button type="button" class="btn-delete btn btn-sm btn-outline-danger border-0 rounded-circle mx-1 p-2" data-id="<?= $row['id'] ?>" title="ลบ">
    <i class="bi bi-trash3 fs-5"></i>
</button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

<script src="https://code.jquery.com/jquery-3.7.0.js"></script>

<script src="../../bootstrap/js/bootstrap.bundle.min.js"></script>

<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>

<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

   <script>
    // 1. ย้ายฟังก์ชันลบมาไว้ข้างบนสุด (นอก Ready Function)
  $(document).ready(function() {
    // ตั้งค่า DataTable
    var table = $('#promoTable').DataTable({
        "language": { "url": "//cdn.datatables.net/plug-ins/1.13.6/i18n/th.json" },
        "retrieve": true
    });

    // ดักจับการคลิกที่ปุ่ม class .btn-delete 
    // ใช้ $(document).on เพื่อให้ปุ่มในหน้า 2, 3, 4 ของตารางกดได้ด้วย
    $(document).on('click', '.btn-delete', function() {
        const promoId = $(this).data('id'); // ดึง ID จาก data-id
        
        Swal.fire({
            title: 'ยืนยันการลบ?',
            text: "ข้อมูลนี้จะถูกลบถาวร!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef233c',
            confirmButtonText: 'ใช่, ลบเลย!',
            cancelButtonText: 'ยกเลิก'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = '../promotion/delete.php?id=' + promoId;
            }
        });
    });

    AOS.init();
});

    // 2. ส่วนการตั้งค่า UI อื่นๆ
    $(document).ready(function() {
        // เริ่มทำงาน AOS
        AOS.init({
            duration: 800,
            once: true
        });

        // เริ่มทำงาน DataTables แบบเช็กก่อนว่าเคยรันหรือยัง
        if ($.fn.DataTable.isDataTable('#promoTable')) {
            $('#promoTable').DataTable().destroy(); // ทำลายตัวเก่าทิ้งก่อน (ถ้ามี)
        }

        $('#promoTable').DataTable({
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.13.6/i18n/th.json"
            },
            "pageLength": 10,
            "order": [[0, "desc"]],
            "drawCallback": function() {
                $('.dataTables_paginate > .pagination').addClass('pagination-rounded');
            }
        });
    });
</script>
</body>

</html>