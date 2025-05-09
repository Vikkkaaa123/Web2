<?php
// Защитные headers
header('Content-Type: text/html; charset=UTF-8');
header("X-Frame-Options: DENY");

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../db.php';

checkAdminAuth();

$db = connectDB();

// Защита от SQL-инъекций
$appId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($appId <= 0) {
    header('Location: admin.php');
    exit();
}

try {
    // Удаление
    $stmt = $db->prepare("DELETE FROM application_languages WHERE application_id = ?");
    $stmt->execute([$appId]);
    
    $stmt = $db->prepare("DELETE FROM user_applications WHERE application_id = ?");
    $stmt->execute([$appId]);
    
    $stmt = $db->prepare("DELETE FROM applications WHERE id = ?");
    $stmt->execute([$appId]);
    
} catch (PDOException $e) {
    error_log("Delete error: " . $e->getMessage());
}

header('Location: admin.php');
exit();
?>
