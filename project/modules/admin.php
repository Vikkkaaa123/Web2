<?php
require_once __DIR__ . '/../scripts/db.php';

$db = db_connect();
if (!isset($_SESSION['login']) || !admin_login_check($db, $_SESSION['login'])) {
    header('Location: login.php');
    exit;
}


function admin_get() {
    $db = db_connect();
    
    $stmt = $db->query("
        SELECT 
            a.id, 
            u.login, 
            a.full_name, 
            a.phone, 
            a.email, 
            a.birth_date, 
            a.gender, 
            a.biography, 
            a.agreement,
            GROUP_CONCAT(pl.name ORDER BY pl.name SEPARATOR ', ') AS languages
        FROM applications a
        JOIN users u ON a.id = u.id
        LEFT JOIN application_language al ON al.application_id = a.id
        LEFT JOIN programming_languages pl ON al.language_id = pl.id
        GROUP BY a.id
    ");
    $processedApplications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Получение статистики по языкам
    $stmt = $db->query("
        SELECT pl.name AS language, COUNT(al.language_id) AS count
        FROM programming_languages pl
        LEFT JOIN application_language al ON pl.id = al.language_id
        GROUP BY pl.name
    ");
    $stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Подключение шаблона
    include __DIR__ . '/../theme/admin.tpl.php';
}

// Запуск
admin_get();
