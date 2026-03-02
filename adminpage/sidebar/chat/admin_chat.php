<?php
// admin_chat.php


// ดึง ID ของ Admin จาก Session (อ้างอิงจากตาราง users ของคุณ)
$admin_id = $_SESSION['user_id'] ?? '3d25e2d5-74fe-40c2-8a59-1530a0734da7'; 

/* 1. ดึงรายการห้องแชท โดยเชื่อมกับตาราง users */
$sql_rooms = "
    SELECT r.*, u.display_name as store_name, u.profile_image as store_image,
    (SELECT message FROM report_messages WHERE report_id = r.id ORDER BY created_at DESC LIMIT 1) as last_msg,
    (SELECT created_at FROM report_messages WHERE report_id = r.id ORDER BY created_at DESC LIMIT 1) as last_time
    FROM reports r
    LEFT JOIN users u ON r.store_id = u.id
    ORDER BY last_time DESC, r.created_at DESC
";
$rooms = $pdo->query($sql_rooms)->fetchAll(PDO::FETCH_ASSOC);
$current_room = $_GET['room_id'] ?? null;
$messages = [];
$info = null; // สร้างตัวแปรไว้รอรับข้อมูลร้าน

if ($current_room) {
    // ดึงข้อมูลร้านค้าและหัวข้อ Report เฉพาะของห้องนี้ออกมาอีกครั้งเพื่อความแม่นยำ
    $stmt_info = $pdo->prepare("
        SELECT r.title, u.display_name as store_name, u.profile_image as store_image 
        FROM reports r 
        LEFT JOIN users u ON r.store_id = u.id 
        WHERE r.id = ?
    ");
    $stmt_info->execute([$current_room]);
    $info = $stmt_info->fetch(PDO::FETCH_ASSOC);

/* 2. ดึงข้อความในห้องที่เลือก */
$current_room = $_GET['room_id'] ?? null;
$messages = [];
if ($current_room) {
    // ปรับปรุง: เมื่อคลิกเข้าห้องแชท ให้ตั้งค่าข้อความที่ยังไม่ได้อ่านเป็น 'อ่านแล้ว'
    $pdo->prepare("UPDATE report_messages SET is_read = 1 WHERE report_id = ? AND sender_role != 'admin'")->execute([$current_room]);

    $stmt = $pdo->prepare("SELECT * FROM report_messages WHERE report_id = ? ORDER BY created_at ASC");
    $stmt->execute([$current_room]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
}}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>Admin Support Chat</title>
    <link rel="stylesheet" href="../../../bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #0084ff;
            --bg-chat: #f0f2f5;
        }

        body {
            font-family: 'Kanit', sans-serif;
            background: #f8fafc;
            height: 100vh;
            overflow: hidden;
        }

        .chat-wrapper {
            display: flex;
            height: calc(100vh - 20px);
            margin: 10px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        /* Sidebar */
        .chat-sidebar {
            width: 350px;
            border-right: 1px solid #eee;
            display: flex;
            flex-direction: column;
        }

        .sidebar-header {
            padding: 20px;
            border-bottom: 1px solid #f5f5f5;
        }

        .room-list {
            flex: 1;
            overflow-y: auto;
        }

        .room-item {
            padding: 15px 20px;
            cursor: pointer;
            transition: 0.2s;
            border-bottom: 1px solid #f9f9f9;
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: inherit;
        }

        .room-item:hover {
            background: #f0f7ff;
        }

        .room-item.active {
            background: #e7f3ff;
            border-left: 4px solid var(--primary-color);
        }

        .avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: #ddd;
            object-fit: cover;
        }

        /* Chat Main */
        .chat-main {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: var(--bg-chat);
        }

        .chat-header {
            background: white;
            padding: 15px 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }

        .chat-body {
            flex: 1;
            overflow-y: auto;
            padding: 25px;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        /* Bubble */
        .msg-bubble {
            max-width: 70%;
            padding: 10px 16px;
            border-radius: 18px;
            position: relative;
            font-size: 0.95rem;
            line-height: 1.4;
        }

        .msg-admin {
            align-self: flex-end;
            background: var(--primary-color);
            color: white;
            border-bottom-right-radius: 4px;
        }

        .msg-store {
            align-self: flex-start;
            background: white;
            color: #333;
            border-bottom-left-radius: 4px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .msg-time {
            font-size: 0.7rem;
            opacity: 0.7;
            margin-top: 5px;
            display: block;
            text-align: right;
        }

        /* Input Area */
        .chat-footer {
            background: white;
            padding: 20px;
        }

        .input-group-custom {
            display: flex;
            gap: 10px;
            background: #f0f2f5;
            padding: 10px 15px;
            border-radius: 25px;
        }

        .input-group-custom input {
            border: none;
            background: transparent;
            flex: 1;
            outline: none;
        }

        .btn-send {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary-color);
            color: white;
            border: none;
            transition: 0.3s;
        }

        .btn-send:hover {
            transform: scale(1.1) rotate(-20deg);
        }

        /* Badge Status */
        .status-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
        }

        .status-new {
            background: #ff9800;
            animation: pulse 1.5s infinite;
        }

        @keyframes pulse {
            0% {
                opacity: 1;
            }

            50% {
                opacity: 0.5;
            }

            100% {
                opacity: 1;
            }
        }
        .chat-wrapper { 
        display: flex; 
        height: calc(100vh - 80px); /* หักลบ padding ของ main-content */
        background: white; 
        border-radius: 15px; 
        box-shadow: 0 5px 20px rgba(0,0,0,0.05); 
        overflow: hidden; 
    }
    
    /* ซ่อน scrollbar ของหน้าหลักเพื่อความสวยงาม */
    body { overflow: hidden; } 
    
    /* แก้ไขส่วน Sidebar ของแชทให้กว้างพอดี */
    .chat-sidebar { 
        width: 300px; 
        min-width: 300px;
        border-right: 1px solid #eee; 
    }
    </style>
</head>

<body>

    <div class="chat-wrapper">
        <div class="chat-sidebar">
            <div class="sidebar-header">
                <h4 class="fw-bold mb-0">ข้อความสนับสนุน</h4>
                <small class="text-muted">จัดการรายงานปัญหาจากร้านค้า</small>
            </div>
            <div class="room-list">
             <?php foreach ($rooms as $room): ?>
    <div class="room-item <?= $current_room == $room['id'] ? 'active' : '' ?>" 
         onclick="window.location.href='?link=reports&room_id=<?= $room['id'] ?>'" 
         style="cursor: pointer;">
        
        <a href="?link=allstore&store_id=<?= $room['store_id'] ?>" 
           onclick="event.stopPropagation();">
            <img src="../../<?= $room['store_image'] ?: 'path/to/default.jpg' ?>" class="avatar">
        </a>

        <div class="flex-grow-1 overflow-hidden">
            <div class="d-flex justify-content-between">
                <a href="?link=allstore&store_id=<?= $room['store_id'] ?>" 
                   onclick="event.stopPropagation();" 
                   class="fw-bold text-truncate text-decoration-none text-dark">
                    <?= htmlspecialchars($room['store_name']) ?>
                </a>
                <small class="text-muted" style="font-size: 0.7rem;">
                    <?= $room['last_time'] ? date('H:i', strtotime($room['last_time'])) : '' ?>
                </small>
            </div>
            <div class="small text-muted text-truncate">
                <?= htmlspecialchars($room['last_msg'] ?: $room['title']) ?>
            </div>
        </div>
        
        <?php if($room['status'] == 'new'): ?>
            <span class="status-dot status-new"></span>
        <?php endif; ?>
    </div>
<?php endforeach; ?>
            </div>
        </div>

        <div class="chat-main">
            <?php if ($current_room): ?>
                <?php
                $room_info = array_filter($rooms, fn($r) => $r['id'] == $current_room);
                $info = reset($room_info);
                ?>
                <div class="chat-header">
    <div class="d-flex align-items-center gap-3">
        <?php 
            // 1. กำหนด Path ให้ถอยออกไปหาโฟลเดอร์หลัก (ปรับจำนวน ../ ตามความจริง)
            // สมมติไฟล์นี้อยู่ใน adminpage/sidebar/chat/ ให้ถอย 3 ชั้นเพื่อไปหน้าโปรเจกต์
            $base_path = "../../../"; 
            
            // 2. ตรวจสอบว่ามีรูปใน DB มั้ย และไฟล์มีอยู่จริงมั้ย
            $profile_img = $info['store_image'];
            $final_img_url = "https://ui-avatars.com/api/?name=" . urlencode($info['store_name']) . "&background=random";

            if (!empty($profile_img)) {
                // ลองเช็ค Path ถอยหลังไปหาโฟลเดอร์ uploads
                if (file_exists($base_path . $profile_img)) {
                    $final_img_url = $base_path . $profile_img;
                }
            }
        ?>
          <img src="../../<?= $room['store_image'] ?: 'path/to/default.jpg' ?>" class="avatar">
        
        <div>
            <h6 class="mb-0 fw-bold"><?= htmlspecialchars($info['store_name']) ?></h6>
            <small class="text-success">หัวข้อ: <?= htmlspecialchars($info['title']) ?></small>
        </div>
    </div>
    
    <div class="dropdown">
        <button class="btn btn-light rounded-pill" data-bs-toggle="dropdown">
            <i class="bi bi-three-dots-vertical"></i>
        </button>
        <ul class="dropdown-menu">
            <li><a class="dropdown-item text-success" href="chat/update_status.php?id=<?= $current_room ?>&status=resolved">
                <i class="bi bi-check-circle"></i> ปิดเคสนี้ (Resolved)</a>
            </li>
            <li><a class="dropdown-item text-danger" href="chat/delete_chat.php?id=<?= $current_room ?>" onclick="return confirm('ยืนยันการลบ?')">
                <i class="bi bi-trash"></i> ลบการสนทนา</a>
            </li>
        </ul>
    </div>
</div>

                <div class="chat-body" id="chatWindow">
    <?php foreach ($messages as $msg): ?>
        <div class="msg-bubble <?= $msg['sender_role'] == 'admin' ? 'msg-admin' : 'msg-store' ?>">
            
            <?php if (!empty($msg['attachment'])): ?>
                <?php 
                    // ปรับ Path ให้ถอยออกจาก adminpage/sidebar/chat/ ไปหาหน้าหลัก
                    $img_path = "../../" . $msg['attachment']; 
                ?>
                <div class="mb-2">
                    <a href="<?= $img_path ?>" target="_blank">
                        <img src="<?= $img_path ?>" class="img-fluid rounded" style="max-width: 250px; cursor: pointer; border: 1px solid rgba(0,0,0,0.1);">
                    </a>
                </div>
            <?php endif; ?>

            <?php if (!empty($msg['message'])): ?>
                <div><?= nl2br(htmlspecialchars($msg['message'])) ?></div>
            <?php endif; ?>

            <span class="msg-time"><?= date('H:i', strtotime($msg['created_at'])) ?></span>
        </div>
    <?php endforeach; ?>
</div>

               <div class="chat-footer">
    <div id="adminPreviewContainer" style="display: none; position: relative; margin-bottom: 10px; width: fit-content;">
        <img id="adminImagePreview" src="" style="max-width: 100px; border-radius: 10px; border: 2px solid var(--primary-color);">
        <button type="button" class="btn-close" onclick="clearAdminPreview()" 
            style="position: absolute; top: -5px; right: -5px; background: white; border-radius: 50%; padding: 4px; font-size: 10px; shadow: 0 2px 5px rgba(0,0,0,0.2);"></button>
    </div>

    <form id="chatForm" class="input-group-custom" enctype="multipart/form-data">
        <input type="hidden" name="report_id" value="<?= $current_room ?>">
        
        <label for="adminChatFile" class="btn text-muted p-0 ms-2" style="cursor: pointer;">
            <i class="bi bi-image" style="font-size: 1.2rem;"></i>
            <input type="file" id="adminChatFile" name="attachment" accept="image/*" style="display: none;" onchange="previewAdminImage(this)">
        </label>

        <input type="text" name="message" id="msgInput" placeholder="พิมพ์ข้อความตอบกลับ..." autocomplete="off">
        <button type="submit" class="btn-send"><i class="bi bi-send-fill"></i></button>
    </form>
</div>
            <?php else: ?>
                <div class="h-100 d-flex flex-column justify-content-center align-items-center text-muted">
                    <i class="bi bi-chat-dots" style="font-size: 5rem; opacity: 0.2;"></i>
                    <h5>เลือกการสนทนาเพื่อเริ่มตอบกลับ</h5>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <audio id="sendSound" src="https://assets.mixkit.co/active_storage/sfx/2354/2354-preview.mp3"></audio>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
   <script>
    const chatWindow = document.getElementById('chatWindow');
    if (chatWindow) chatWindow.scrollTop = chatWindow.scrollHeight;

    let isSubmitting = false; // 1. เพิ่มตัวแปรเช็คสถานะป้องกันการส่งซ้ำ

    // ระบบส่งข้อความแบบ AJAX (รวมเหลืออันเดียว)
    document.getElementById('chatForm')?.addEventListener('submit', function(e) {
        e.preventDefault();

        if (isSubmitting) return; // 2. ถ้ากำลังส่งอยู่ ให้หยุดทำงานทันที
        
        const formData = new FormData(this);
        const input = document.getElementById('msgInput');
        const submitBtn = this.querySelector('button[type="submit"]');

        isSubmitting = true; // 3. ล็อคสถานะ
        submitBtn.disabled = true; // 4. ปิดปุ่มกดชั่วคราว

        fetch('chat/save_message.php', { 
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if(data.status === 'success') {
                document.getElementById('sendSound').play();
                input.value = '';
                clearAdminPreview();
                
                // เลื่อนลงล่างสุดหรือ Reload
                location.reload(); 
            } else {
                alert(data.message);
                isSubmitting = false; // ปลดล็อคถ้าเกิด Error
                submitBtn.disabled = false;
            }
        })
        .catch(err => {
            console.error(err);
            isSubmitting = false;
            submitBtn.disabled = false;
        });
    });

    // ฟังก์ชันพรีวิวรูปภาพ (เหมือนเดิม)
    function previewAdminImage(input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('adminImagePreview').src = e.target.result;
                document.getElementById('adminPreviewContainer').style.display = 'block';
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    // ล้างรูปพรีวิว (เหมือนเดิม)
    function clearAdminPreview() {
        const fileInput = document.getElementById('adminChatFile');
        if(fileInput) fileInput.value = "";
        document.getElementById('adminPreviewContainer').style.display = 'none';
    }
</script>
</body>

</html>