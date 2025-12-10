<?php
session_start();
require_once "../ld_db.php";

$email = $_POST['email'];
$password = $_POST['password'];
$phone = $_POST['phone'];

$sql = "SELECT * FROM users 
        WHERE email = '$email' 
        AND phone = '$phone'
        LIMIT 1";

$query = mysqli_query($conn, $sql);
$user = mysqli_fetch_assoc($query);

$sql = "SELECT id, email, phone, password_hash, display_name, role FROM users WHERE email = ? AND phone = ? LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $email, $phone);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
if(!$user){
    $_SESSION['error'] = "Email หรือ เบอร์โทร ไม่ถูกต้อง";
    header("Location: login.php");
    exit;
}

// แบบไม่ใช้ password_hash เพราะคุณยังไม่ได้ hash
if($password !== $user['password_hash']){
    $_SESSION['error'] = "รหัสผ่านไม่ถูกต้อง";
    header("Location: login.php");
    exit;
}

$_SESSION['user_id'] = $user['id'];
$_SESSION['role'] = $user['role'];
$_SESSION['user_name'] = $user['display_name'];

if(in_array($user['role'], ['platform_admin','store_owner','staff'])){
    header("Location: ../adminpage/sidebar/sidebar.php");
} else {
    header("Location: ../userspage/home.php");
}

exit;
?>
