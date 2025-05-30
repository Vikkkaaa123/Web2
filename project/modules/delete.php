<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../scripts/db.php';

checkAdminAuth();
$db = connectDB();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id > 0) {
    // Удаляем связанные языки
    $stmt = $db->prepare("DELETE FROM application_languages WHERE application_id = ?");
    $stmt->execute([$id]);

    // Удаляем связь с пользователем
    $stmt = $db->prepare("DELETE FROM user_applications WHERE application_id = ?");
    $stmt->execute([$id]);

    // Удаляем саму заявку
    $stmt = $db->prepare("DELETE FROM applications WHERE id = ?");
    $stmt->execute([$id]);
}

header("Location: admin.php");
exit();
