<?php
session_start();
session_destroy();

echo '
<script>
    alert("คุณได้ออกจากระบบเรียบร้อยแล้ว");
    window.location.href = "login.php";
</script>
';

exit;
?>
