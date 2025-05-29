<?php
require_once __DIR__ . '/../scripts/db.php';
session_start();

// Проверка авторизации
if (empty($_SESSION['login']) || !admin_login_check($_SESSION['login'])) {
    header('Location: login.php');
    exit;
}

function admin_get() {
    $db = db_connect();

    // Все заявки пользователей
    $apps = db_all("SELECT * FROM applications");

    $result = [];
    foreach ($apps as $app) {
        $app_id = $app['id'];

        // Получаем логин из таблицы users
        $loginRow = db_row("SELECT login FROM users WHERE app_id = ?", $app_id);
        $login = $loginRow ? $loginRow['login'] : '—';

        // Получаем языки
        $langsRows = db_all("SELECT pl.name FROM app_language al JOIN programming_languages pl ON al.language_id = pl.id WHERE al.app_id = ?", $app_id);
        $languages = array_column($langsRows, 'name');

        $result[] = [
            'id' => $app['id'],
            'login' => $login,
            'full_name' => $app['full_name'],
            'phone' => $app['phone'],
            'email' => $app['email'],
            'birth_date' => $app['birth_date'],
            'gender_short' => $app['gender'],
            'biography' => $app['biography'],
            'languages' => implode(', ', $languages),
        ];
    }

    // Статистика по языкам
    $langStats = db_all("SELECT pl.name, COUNT(*) as count FROM app_language al JOIN programming_languages pl ON al.language_id = pl.id GROUP BY al.language_id");

    return [$result, $langStats];
}

// Получаем данные
[$processedApplications, $stats] = admin_get();

// Показываем шаблон
require_once __DIR__ . '/../theme/admin.tpl.php';
