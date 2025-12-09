<?php

  $servername_db = "localhost" ;
  $username_db = "root";
  $password_db = "";
  $ld_db = "ld_db";


  $conn = mysqli_connect("localhost", "root", "" , "ld_db");
  mysqli_query($conn, "SET NAMES 'UTF8'");
  date_default_timezone_set("asia/bangkok");
  
$pdo = new PDO(
    "mysql:host=localhost;dbname=ld_db;charset=utf8",
    "root",
    ""
);

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);


  $sid = session_id();
  $date = date("yy-m-d H:i:s");



?>