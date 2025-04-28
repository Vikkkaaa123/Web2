<?php
require 'auth.php';
checkAdminAuth();

$id = (int)$_GET['id'];

$db->beginTransaction();
try {
    // Удаляем связанные языки
    $db->prepare("DELETE FROM application_languages WHERE application_id = ?")->execute([$id]);
    
    // Удаляем заявку
    $db->prepare("DELETE FROM applications WHERE id = ?")->execute([$id]);
    
    $db->commit();
} catch (Exception $e) {
    $db->rollBack();
    die("Ошибка удаления: " . $e->getMessage());
}

header('Location: admin.php');
