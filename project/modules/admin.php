<?php
require_once __DIR__ . '/../scripts/db.php';

function admin_get($request, $db) {
    $user_log = $_SERVER['PHP_AUTH_USER'] ?? '';
    $user_pass = $_SERVER['PHP_AUTH_PW'] ?? '';

    if (empty($user_log) || empty($user_pass) || 
        !admin_login_check($db, $user_log) || 
        !admin_password_check($db, $user_log, $user_pass)) {

        header('HTTP/1.1 401 Unauthorized');
        header('WWW-Authenticate: Basic realm="Admin Panel"');
        return theme('401');
    }

    // Статистика по языкам
    $language_table = $db->query("
        SELECT p.name, COUNT(al.application_id) as count 
        FROM programming_languages p
        LEFT JOIN application_languages al ON p.id = al.language_id
        GROUP BY p.id
        ORDER BY count DESC
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Все заявки с пользователями
    $user_table = $db->query("
        SELECT
            a.id,
            u.login AS user_login,
            a.full_name,
            a.email,
            a.phone,
            a.birth_date,
            CASE a.gender
                WHEN 'm' THEN 'М'
                WHEN 'f' THEN 'Ж'
                ELSE 'Другое'
            END AS gender_short,
            a.biography,
            a.agreement,
            GROUP_CONCAT(p.name SEPARATOR ', ') AS languages
        FROM applications a
        LEFT JOIN users u ON a.user_id = u.id
        LEFT JOIN application_languages al ON a.id = al.application_id
        LEFT JOIN programming_languages p ON al.language_id = p.id
        GROUP BY a.id
        ORDER BY a.id DESC
    ")->fetchAll(PDO::FETCH_ASSOC);

    return theme('admin', [
        'language_stats' => $language_table,
        'processedApplications' => $user_table
    ]);
}

function admin_post($request, $db) {
    if (!empty($request['del_by_uid'])) {
        $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$request['del_by_uid']]);
    }

    return redirect('admin');
}

$db = db_connect();
$response = ($_SERVER['REQUEST_METHOD'] === 'POST') 
    ? admin_post($_POST, $db) 
    : admin_get($_GET, $db);

echo $response;
