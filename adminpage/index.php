
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>Admin Panel</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Bootstrap OFFLINE -->
    <link href="../bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.css" rel="stylesheet">


    <style>
        body {
            margin-left: 260px;
            background: #f7f7f7;
        }

        .sidebar {
            width: 260px;
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            background: #0d0d1a;
            padding: 20px;
            color: white;
            overflow-y: auto;
            z-index: 1000;
        }

        .menu-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 0;
            color: #e0e0e0;
            text-decoration: none;
        }

        .menu-item:hover {
            color: #ffffff;
            padding-left: 5px;
            transition: 0.2s;
        }
    </style>
</head>

<body>

    <!-- ตัวอย่าง Sidebar -->
    <div class="sidebar">
        <a href="#" class="menu-item">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>
        <a href="#" class="menu-item">
            <i class="bi bi-shop"></i> ร้านทั้งหมด
        </a>
    </div>

    <script src="../bootstrap/js/bootstrap.bundle.min.js"></script>
</body>

</html>