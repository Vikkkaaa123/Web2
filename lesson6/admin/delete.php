<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../db.php';
checkAdminAuth();

$db = connectDB();
$appId = $_GET['id'];

// Удаляем связи с языками
$db->exec("DELETE FROM application_languages WHERE application_id = $appId");

// Удаляем связь с пользователем
$db->exec("DELETE FROM user_applications WHERE application_id = $appId");

// Удаляем саму заявку
$db->exec("DELETE FROM applications WHERE id = $appId");

header('Location: admin.php');
?>
