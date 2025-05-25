<?php
require_once __DIR__ . '/../scripts/db.php';

function admin_get($request, $db) {
    $user_log = $_SERVER['PHP_AUTH_USER'] ?? '';
    $user_pass = $_SERVER['PHP_AUTH_PW'] ?? '';
    
    if (empty($user_log) || empty($user_pass) || 
        !admin_login_check($user_log) || 
        !admin_password_check($user_log, $user_pass)) {
        
        header('HTTP/1.1 401 Unauthorized');
        header('WWW-Authenticate: Basic realm="My site"');
        return theme('401'); 
    }

    $language_table = language_stats($db);
    $user_table = users_table($db);

    return theme('admin', [
        'language_stats' => $language_table,
        'users' => $user_table
    ]);
}

function admin_post($request, $db) {
    if (!empty($request['del_by_uid']) && !empty($_SERVER['PHP_AUTH_USER'])) {
        del_by_uid($db, $request['del_by_uid']);
    }
    return redirect('admin');
}

$db = db_connect();
$response = ($_SERVER['REQUEST_METHOD'] === 'POST') 
    ? admin_post($_POST, $db) 
    : admin_get($_GET, $db);

echo $response;
