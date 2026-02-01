<?php
// --- LOGIC: ตรวจสอบสิทธิ์และดึงข้อมูล ---
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['store_owner', 'staff'])) {
    die('ไม่มีสิทธิ์เข้าถึง');
}

$user_id = $_SESSION['user_id'];

// ดึงข้อมูลพร้อมยอดรวมสถานะเพื่อทำ Filter Badges
$stmt = $pdo->prepare("
    SELECT 
        o.id, o.order_number, o.status, o.notes, o.created_at,
        u.display_name AS customer_name,
        u.phone AS customer_phone
    FROM orders o
    LEFT JOIN users u ON u.id = o.customer_id
    JOIN store_staff ss ON ss.store_id = o.store_id
    WHERE ss.user_id = :user_id
    ORDER BY 
        CASE WHEN o.status != 'completed' THEN 0 ELSE 1 END, 
        o.created_at DESC
");
$stmt->execute([':user_id' => $user_id]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ฟังก์ชันแปลสถานะเป็นคำภาษาไทยและสี (UX: ภาษาไทยเข้าใจง่ายกว่าสำหรับพนักงาน)
function get_status_config($s)
{
    return match ($s) {
        'created'          => ['label' => 'ออเดอร์ใหม่', 'color' => '#6c757d', 'icon' => 'bi-plus-circle'],
        'picked_up'        => ['label' => 'รับผ้ามาแล้ว', 'color' => '#0dcaf0', 'icon' => 'bi-truck'],
        'in_process'       => ['label' => 'กำลังซัก/อบ', 'color' => '#ffc107', 'icon' => 'bi-gear-wide-connected'],
        'ready'            => ['label' => 'ซักเสร็จแล้ว', 'color' => '#0d6efd', 'icon' => 'bi-check2-all'],
        'out_for_delivery' => ['label' => 'กำลังไปส่ง', 'color' => '#212529', 'icon' => 'bi-bicycle'],
        'completed'        => ['label' => 'งานสำเร็จ', 'color' => '#198754', 'icon' => 'bi-hand-thumbs-up-fill'],
        default            => ['label' => 'ไม่ระบุ', 'color' => '#6c757d', 'icon' => 'bi-question-circle']
    };
}
?>

<style>
    .order-container {
        background: #f0f5fa;
        min-height: 100vh;
    }

    /* Search & Filter Bar */
    .filter-section {
        background: #fff;
        border-radius: 0 0 25px 25px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        position: sticky;
        top: 0;
        z-index: 100;
    }

    .search-input {
        background: #f1f4f9;
        border: none;
        border-radius: 12px;
        padding: 10px 15px 10px 40px;
    }

    .search-icon {
        position: absolute;
        left: 25px;
        top: 50%;
        transform: translateY(-50%);
        color: #adb5bd;
    }

    /* Custom Tabs */
    .nav-pills-custom .nav-link {
        color: #6c757d;
        font-weight: 500;
        border-radius: 10px;
        padding: 8px 16px;
        font-size: 0.9rem;
    }

    .nav-pills-custom .nav-link.active {
        background: #e7f1ff;
        color: #0d6efd;
    }

    /* Order Card Design */
    .order-item-card {
        border: none;
        border-radius: 20px;
        background: #fff;
        margin-bottom: 15px;
        transition: 0.25s;
        border-left: 5px solid transparent;
    }

    .order-item-card:active {
        transform: scale(0.98);
    }

    .status-indicator {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        display: inline-block;
        margin-right: 5px;
    }

    .btn-manage {
        background: #f8fbff;
        color: #0d6efd;
        border: 1px solid #e1e9f5;
        border-radius: 12px;
        font-weight: 600;
        width: 100%;
        padding: 10px;
    }
</style>

<div class="order-container">
    <div class="filter-section p-3 mb-4">
        <div class="position-relative mb-3">
            <i class="bi bi-search search-icon"></i>
            <input type="text" id="orderSearch" class="form-control search-input" placeholder="ค้นหาชื่อลูกค้า หรือเลขงาน...">
        </div>

        <ul class="nav nav-pills nav-pills-custom justify-content-center" id="statusFilter">
            <li class="nav-item">
                <a class="nav-link active" href="#" data-filter="all">ทั้งหมด</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#" data-filter="pending">รอดำเนินการ</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#" data-filter="completed">เสร็จสิ้น</a>
            </li>
        </ul>
    </div>

    <div class="container px-3">
        <div id="orderList">
            <?php foreach ($orders as $o):
                $config = get_status_config($o['status']);
                $is_completed = ($o['status'] === 'completed');
            ?>
                <div class="card order-item-card shadow-sm order-card-item"
                    data-status="<?= $is_completed ? 'completed' : 'pending' ?>"
                    data-search="<?= strtolower($o['order_number'] . ' ' . ($o['customer_name'] ?? '')) ?>">

                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <span class="badge mb-1" style="background: <?= $config['color'] ?>15; color: <?= $config['color'] ?>;">
                                    <i class="bi <?= $config['icon'] ?> me-1"></i> <?= $config['label'] ?>
                                </span>
                                <h6 class="fw-bold text-dark mb-0 mt-1">
                                    <?= htmlspecialchars($o['order_number']) ?>
                                </h6>
                            </div>
                            <div class="text-end">
                                <small class="text-muted d-block" style="font-size: 0.7rem;">
                                    <?= date('d M', strtotime($o['created_at'])) ?>
                                </small>
                                <small class="text-muted" style="font-size: 0.7rem;">
                                    <i class="bi bi-clock"></i> <?= date('H:i', strtotime($o['created_at'])) ?>
                                </small>
                            </div>
                        </div>

                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-light rounded-circle p-2 me-3">
                                <i class="bi bi-person text-primary"></i>
                            </div>
                            <div>
                                <div class="fw-semibold text-dark" style="font-size: 0.9rem;">
                                    <?= htmlspecialchars($o['customer_name'] ?? 'ลูกค้าทั่วไป') ?>
                                </div>
                                <?php if ($o['notes']): ?>
                                    <small class="text-warning"><i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($o['notes']) ?></small>
                                <?php endif; ?>
                            </div>
                        </div>

                        <a href="menu/orders/detail.php?id=<?= $o['id'] ?>" class="btn btn-manage">
                            จัดการงานและอัปเดต <i class="bi bi-chevron-right ms-1"></i>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div id="noResult" class="text-center py-5 d-none">
            <i class="bi bi-inbox text-muted display-1"></i>
            <p class="text-muted mt-2">ไม่พบรายการงานที่คุณค้นหา</p>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('orderSearch');
        const filterLinks = document.querySelectorAll('#statusFilter .nav-link');
        const orderCards = document.querySelectorAll('.order-card-item');
        const noResult = document.getElementById('noResult');

        function filterOrders() {
            const searchTerm = searchInput.value.toLowerCase();
            const activeFilter = document.querySelector('#statusFilter .nav-link.active').dataset.filter;
            let visibleCount = 0;

            orderCards.forEach(card => {
                const status = card.dataset.status;
                const searchData = card.dataset.search;

                const matchesSearch = searchData.includes(searchTerm);
                const matchesFilter = (activeFilter === 'all') || (activeFilter === status);

                if (matchesSearch && matchesFilter) {
                    card.classList.remove('d-none');
                    visibleCount++;
                } else {
                    card.classList.add('d-none');
                }
            });

            noResult.classList.toggle('d-none', visibleCount > 0);
        }

        // Search Event
        searchInput.addEventListener('input', filterOrders);

        // Filter Tabs Event
        filterLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                filterLinks.forEach(l => l.classList.remove('active'));
                this.classList.add('active');
                filterOrders();
            });
        });
    });
</script>