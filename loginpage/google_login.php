<?php
require_once '../vendor/autoload.php';

$client = new Google\Client();
// $client->setClientId('837743881206-a4uglohivo68orpn5h88a9nhko7aga2g.apps.googleusercontent.com');
// $client->setClientSecret('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');
$client->setRedirectUri('http://localhost/ld_project/loginpage/google_callback.php');
$client->addScope('email');
$client->addScope('profile');

header('Location: ' . $client->createAuthUrl());
exit;
