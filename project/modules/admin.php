<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../scripts/db.php';

checkAdminAuth();

function get_admin_data() {
    $db = db_connect();

    // Статистика по языкам
    $stats = $db->query("
        SELECT p.name, COUNT(DISTINCT al.application_id) as count
        FROM programming_languages p
        LEFT JOIN application_languages al ON p.id = al.language_id
        GROUP BY p.id
        ORDER BY count DESC
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Все заявки
    $applications = $db->query("
        SELECT * FROM applications ORDER BY id ASC
    ")->fetchAll(PDO::FETCH_ASSOC);

    $processedApplications = [];

    foreach ($applications as $app) {
        // Логин пользователя
        $stmt = $db->prepare("
            SELECT u.login FROM users u
            JOIN user_applications ua ON u.id = ua.user_id
            WHERE ua.application_id = ?
        ");
        $stmt->execute([$app['id']]);
        $app['user_login'] = $stmt->fetchColumn() ?: '—';

        // Языки программирования
        $stmt = $db->prepare("
            SELECT GROUP_CONCAT(p.name SEPARATOR ', ')
            FROM programming_languages p
            JOIN application_languages al ON p.id = al.language_id
            WHERE al.application_id = ?
        ");
        $stmt->execute([$app['id']]);
        $app['languages'] = $stmt->fetchColumn() ?: 'Не указано';

        // Пол
        $app['gender_short'] = $app['gender'] === 'male' ? 'м' : ($app['gender'] === 'female' ? 'ж' : '—');

        // Согласие
        $app['agreement_text'] = (int)$app['agreement'] === 1 ? 'Да' : 'Нет';

        $processedApplications[] = $app;
    }

    return [
        'stats' => $stats,
        'processedApplications' => $processedApplications
    ];
}


$data = admin_get($db);
$stats = $data['stats'];
$processedApplications = $data['users'];

require_once __DIR__ . '/../theme/admin.tpl.php';
