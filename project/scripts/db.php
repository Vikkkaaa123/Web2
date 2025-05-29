<?php
/**
 * Подключение к базе и функции работы с БД
 */

$db = null;

function db_connect() {
    global $db;

    if ($db === null) {
        $user = 'u68606';
        $pass = '9347178';
        $dsn = 'mysql:host=localhost;dbname=u68606;charset=utf8mb4';

        try {
            $db = new PDO($dsn, $user, $pass, [
                PDO::ATTR_PERSISTENT => true,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (PDOException $e) {
            error_log("Ошибка подключения к БД: " . $e->getMessage());
            die("Ошибка подключения к БД");
        }
    }

    return $db;
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

function db_all($query, ...$params) {
    return db_query($query, ...$params)->fetchAll();
}

function admin_login_check($login) {
    $row = db_row("SELECT id FROM admins WHERE login = ?", $login);
    return $row ? true : false;
}

function admin_password_check($login, $password) {
    $row = db_row("SELECT password_hash FROM admins WHERE login = ?", $login);
    return $row && password_verify($password, $row['password_hash']);
}

function getLangs() {
    $rows = db_all("SELECT id, name FROM programming_languages");
    $langs = [];
    foreach ($rows as $row) {
        $langs[$row['id']] = $row['name'];
    }
    return $langs;
}
