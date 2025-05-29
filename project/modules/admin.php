<?php
// modules/admin.php

require_once __DIR__ . '/../scripts/db.php';

session_start();

if (!isset($_SESSION['login']) || !admin_login_check($_SESSION['login'])) {
    header('Location: login.php');
    exit;
}

// Получение данных пользователей и статистики
function admin_get() {
    $db = db_connect();

    // Получение всех пользователей
    $applications = db_all("SELECT * FROM applications");

    // Получение языков пользователей
    $app_ids = array_column($applications, 'id');
    $lang_data = [];
    if (!empty($app_ids)) {
        $in = str_repeat('?,', count($app_ids) - 1) . '?';
        $stmt = db_query("SELECT al.application_id, pl.name
                          FROM application_languages al
                          JOIN programming_languages pl ON al.language_id = pl.id
                          WHERE al.application_id IN ($in)", ...$app_ids);

        foreach ($stmt as $row) {
            $lang_data[$row['application_id']][] = $row['name'];
        }
    }

    $user_table = [];
    foreach ($applications as $app) {
        $id = $app['id'];
        $user_table[] = [
            'id' => $id,
            'full_name' => $app['full_name'],
            'email' => $app['email'],
            'phone' => $app['phone'],
            'birth_date' => $app['birth_date'],
            'gender' => $app['gender'] === 'male' ? 'Муж' : 'Жен',
            'biography' => $app['biography'],
            'languages' => isset($lang_data[$id]) ? implode(', ', $lang_data[$id]) : ''
        ];
    }

    // Получение статистики по языкам
    $stats = db_all("SELECT pl.name, COUNT(al.language_id) as total
                     FROM application_languages al
                     JOIN programming_languages pl ON al.language_id = pl.id
                     GROUP BY al.language_id");

    return [
        'user_table' => $user_table,
        'stats' => $stats
    ];
}

$data = admin_get();
$user_table = $data['user_table'];
$stats = $data['stats'];

require_once __DIR__ . '/../theme/admin.tpl.php';
