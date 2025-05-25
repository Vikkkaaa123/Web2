<?php

$db = null;

function db_connect() {
    global $db;
    
    if ($db === null) {
        $user = 'u68606';
        $pass = '9347178';
        
        try {
            $db = new PDO('mysql:host=localhost;dbname=u68606', $user, $pass, 
                [
                    PDO::ATTR_PERSISTENT => true,
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]
            );
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            die("Ошибка подключения к БД. Попробуйте позже.");
        }
    }
    return $db;
}

function db_row($stmt) {
    return $stmt->fetch();
}

function db_query($query) {
    global $db;
    $args = func_get_args();
    $query = array_shift($args);
    $stmt = $db->prepare($query);
    $stmt->execute($args);
    $result = [];
    while ($row = $stmt->fetch()) {
        if (isset($row['id']) {
            $result[$row['id']] = $row;
        } else {
            $result[] = $row;
        }
    }
    return $result;
}

function db_command($query) {
    global $db;
    $args = func_get_args();
    $query = array_shift($args);
    $stmt = $db->prepare($query);
    return $stmt->execute($args);
}

function db_insert_id() {
    global $db;
    return $db->lastInsertId();
}

function db_result($query) {
    $result = db_query($query);
    return $result ? reset($result[0]) : false;
}

// Автоматическое подключение при первом вызове
db_connect();
