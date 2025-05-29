<?php
require_once __DIR__ . '/../scripts/db.php';

// просто вызываем функцию, не объявляя её заново
admin_login_check();

function admin_get() {
    $db = connectDB();

    // Получаем статистику по языкам
    $stmt = $db->query("
        SELECT l.language, COUNT(al.language_id) AS count
        FROM application_languages al
        JOIN languages l ON al.language_id = l.id
        GROUP BY al.language_id
    ");
    $stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Получаем все заявки пользователей и их языки
    $stmt = $db->query("
        SELECT 
            a.id,
            a.full_name,
            a.phone,
            a.email,
            a.birth_date,
            a.gender,
            a.biography,
            a.agreement,
            GROUP_CONCAT(l.language SEPARATOR ', ') as languages
        FROM applications a
        LEFT JOIN application_languages al ON a.id = al.application_id
        LEFT JOIN languages l ON al.language_id = l.id
        GROUP BY a.id
    ");
    $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Передаём в шаблон
    include_once __DIR__ . '/../theme/admin.tpl.php';
}

admin_get();
