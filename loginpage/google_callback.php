<?php
session_start();
require_once "../ld_db.php";
require_once "../vendor/autoload.php";

$client = new Google\Client();
$client->setClientId('837743881206-a4uglohivo68orpn5h88a9nhko7aga2g.apps.googleusercontent.com');
$client->setClientSecret('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');
$client->setRedirectUri('http://localhost/ld_project/loginpage/google_callback.php');

if (!isset($_GET['code'])) {
    header("Location: login.php");
    exit;
}

$token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
$client->setAccessToken($token['access_token']);

$oauth = new Google\Service\Oauth2($client);
$info  = $oauth->userinfo->get();

$google_id = $info->id;
$email     = $info->email;
$name      = $info->name;
$picture   = $info->picture;

/* ===== เช็คว่ามี user นี้แล้วหรือยัง ===== */
$stmt = $pdo->prepare("
    SELECT id, role
    FROM users
    WHERE google_id = ? OR email = ?
    LIMIT 1
");
$stmt->execute([$google_id, $email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {

    // 🔥 สมัครใหม่ → customer เท่านั้น
    $stmt = $pdo->prepare("
        INSERT INTO users
        (id, email, display_name, profile_image, google_id, role, login_provider)
        VALUES (UUID(), ?, ?, ?, ?, 'customer', 'google')
    ");
    $stmt->execute([
        $email,
        $name,
        $picture,
        $google_id
    ]);

    $user_id = $pdo->lastInsertId();

} else {

    // 🔁 อัปเดตรูป (เผื่อเปลี่ยนใน Google)
    $stmt = $pdo->prepare("
        UPDATE users
        SET profile_image = ?
        WHERE id = ?
    ");
    $stmt->execute([$picture, $user['id']]);

    $user_id = $user['id'];
}

/* ===== set session ===== */
$_SESSION['user_id'] = $user_id;
$_SESSION['role']    = 'customer';

/* ===== redirect ===== */
header("Location: ../userspage/index.php");
exit; 
