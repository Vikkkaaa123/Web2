<?php
require_once 'auth.php';
checkAdminAuth();

$db = new PDO('mysql:host=localhost;dbname=u68606', 'u68606', '9347178');
$userId = $_GET['id'];

$db->exec("DELETE FROM user_languages WHERE user_id = $userId");
$db->exec("DELETE FROM users WHERE id = $userId");

header('Location: admin.php');
?>
