<?php
require_once 'auth.php';
checkAdminAuth();

$db = new PDO('mysql:host=localhost;dbname=u68606', 'u68606', '9347178');
$appId = $_GET['id'];

// Удаляем связи с языками
$db->exec("DELETE FROM application_languages WHERE application_id = $appId");

// Удаляем связь с пользователем
$db->exec("DELETE FROM user_applications WHERE application_id = $appId");

// Удаляем саму заявку
$db->exec("DELETE FROM applications WHERE id = $appId");

header('Location: admin.php');
?>
