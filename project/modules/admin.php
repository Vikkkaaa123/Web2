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

    $language_table = $db->query("
        SELECT p.name, COUNT(al.application_id) as count 
        FROM programming_languages p
        LEFT JOIN application_languages al ON p.id = al.language_id
        GROUP BY p.id
        ORDER BY count DESC
    ")->fetchAll();

    $user_table = $db->query("
        SELECT u.id, u.login, COUNT(ua.application_id) as apps_count
        FROM users u
        LEFT JOIN user_applications ua ON u.id = ua.user_id
        GROUP BY u.id
    ")->fetchAll();

    return theme('admin', [
        'language_stats' => $language_table,
        'users' => $user_table
    ]);
}

function admin_post($request, $db) {
    $user_log = $_SERVER['PHP_AUTH_USER'] ?? '';
    
    if (!empty($request['del_by_uid']) && !empty($user_log)) {
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
