<?php
require_once __DIR__ . '/../scripts/db.php';

if (!isset($_SESSION['login']) || !admin_login_check($_SESSION['login'])) {
    header('Location: login.php');
    exit;
}

function admin_get() {
    global $db;

    // 1. Получаем статистику по языкам
    $stmt = $db->prepare("SELECT l.name, COUNT(al.language_id) as count
                          FROM languages l
                          LEFT JOIN application_languages al ON l.id = al.language_id
                          GROUP BY l.id");
    $stmt->execute();
    $language_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 2. Получаем все заявки + логины пользователей
    $stmt = $db->prepare("
        SELECT 
            a.*,
            u.login
        FROM applications a
        LEFT JOIN user_applications ua ON a.id = ua.application_id
        LEFT JOIN users u ON ua.user_id = u.id
        ORDER BY a.id DESC
    ");
    $stmt->execute();
    $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. Получаем языки для всех заявок
    $stmt = $db->prepare("
        SELECT al.application_id, l.name
        FROM application_languages al
        JOIN languages l ON al.language_id = l.id
    ");
    $stmt->execute();
    $language_rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Группируем языки по заявке
    $lang_data = [];
    foreach ($language_rows as $row) {
        $lang_data[$row['application_id']][] = $row['name'];
    }

    // Формируем таблицу пользователей
    $user_table = [];
    foreach ($applications as $app) {
        $id = $app['id'];
        $user_table[] = [
            'id' => $id,
            'login' => $app['login'] ?? '—',
            'full_name' => $app['full_name'],
            'email' => $app['email'],
            'phone' => $app['phone'],
            'birth_date' => $app['birth_date'],
            'gender' => $app['gender'] === 'male' ? 'Муж' : ($app['gender'] === 'female' ? 'Жен' : '—'),
            'biography' => $app['biography'],
            'agreement' => (int)$app['agreement'] === 1 ? 'Да' : 'Нет',
            'languages' => isset($lang_data[$id]) ? implode(', ', $lang_data[$id]) : ''
        ];
    }

    return [
        'stats' => $language_stats,
        'users' => $user_table,
    ];
}


$data = admin_get();
$user_table = $data['user_table'];
$stats = $data['stats'];
$processedApplications = $data['user_table']; 
require_once __DIR__ . '/../theme/admin.tpl.php';
