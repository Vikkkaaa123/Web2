<?php

$db = null;

function db_connect() {
    global $db;
    if ($db !== null) return $db;

    $user = 'u68606';
    $pass = '9347178';
    $dsn = 'mysql:host=localhost;dbname=u68606;charset=utf8mb4';

    try {
        $db = new PDO($dsn, $user, $pass, [
            PDO::ATTR_PERSISTENT => true,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
        ]);
        return $db;
    } catch (PDOException $e) {
        error_log("DB Connection Error: " . $e->getMessage());
        throw new Exception("Ошибка подключения к базе данных.");
    }
}

function db_query($query, ...$params) {
    $db = db_connect();
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    return $stmt;
}

function db_row($query, ...$params) {
    return db_query($query, ...$params)->fetch();
}

function db_result($query, ...$params) {
    $row = db_row($query, ...$params);
    return $row ? reset($row) : null;
}

function db_all($query, ...$params) {
    return db_query($query, ...$params)->fetchAll();
}

function db_command($query, ...$params) {
    return db_query($query, ...$params)->rowCount();
}

function db_insert_id() {
    return db_connect()->lastInsertId();
}

function admin_login_check($login) {
    return db_row("SELECT id FROM admins WHERE login = ?", $login) !== false;
}


function getLangs() {
    $db = db_connect();
    $stmt = $db->query("SELECT id, name FROM programming_languages");

    $langs = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $langs[] = [
            'id' => $row['id'],
            'name' => $row['name']
        ];
    }

    return $langs;
}




function admin_password_check($login, $password) {
    $row = db_row("SELECT password_hash FROM admins WHERE login = ?", $login);
    return $row && password_verify($password, $row['password_hash']);
}

//  Получение всех заявок
function get_all_applications() {
    return db_all("
        SELECT a.*, u.login as user_login, g.short as gender_short,
               GROUP_CONCAT(l.name SEPARATOR ', ') as languages
        FROM applications a
        LEFT JOIN users u ON u.id = a.user_id
        LEFT JOIN genders g ON g.id = a.gender
        LEFT JOIN application_language al ON al.app_id = a.id
        LEFT JOIN programming_languages l ON l.id = al.lang_id
        GROUP BY a.id
        ORDER BY a.id DESC
    ");
}
