<?php

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
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ]);
            
            // Проверяем доступность необходимых таблиц
            $requiredTables = ['programming_languages', 'applications', 'users'];
            foreach ($requiredTables as $table) {
                $db->query("SELECT 1 FROM `{$table}` LIMIT 1");
            }
            
            return $db;
            
        } catch (PDOException $e) {
            error_log("DB Connection Error [{$dsn}]: " . $e->getMessage());
            return false;
        }
    }
    
    return $db;
}

function db_row($query, ...$params) {
    try {
        $stmt = db_query($query, ...$params);
        return $stmt ? $stmt->fetch() : false;
    } catch (PDOException $e) {
        error_log("DB Row Error: {$query} - " . $e->getMessage());
        return false;
    }
}

function db_query($query, ...$params) {
    $db = db_connect();
    if (!$db) return false;
    
    try {
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        error_log("DB Query Error: {$query} - " . $e->getMessage());
        return false;
    }
}


function db_command($query, ...$params) {
    $db = db_connect();
    if (!$db) return false;
    
    try {
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        return $stmt->rowCount();
    } catch (PDOException $e) {
        error_log("DB Command Error: {$query} - " . $e->getMessage());
        return false;
    }
}


function db_insert_id() {
    $db = db_connect();
    return $db ? $db->lastInsertId() : false;
}


function db_result($query, ...$params) {
    $row = db_row($query, ...$params);
    return $row ? reset($row) : false;
}


function admin_login_check($login) {
    $db = db_connect();
    if (!$db) return false;
    
    try {
        $stmt = $db->prepare("SELECT COUNT(*) FROM admins WHERE login = ?");
        $stmt->execute([$login]);
        return $stmt->fetchColumn() > 0;
    } catch (PDOException $e) {
        error_log("Admin login check failed: " . $e->getMessage());
        return false;
    }
}


function admin_password_check($login, $password) {
    $db = db_connect();
    if (!$db) return false;
    
    try {
        $stmt = $db->prepare("SELECT password FROM admins WHERE login = ?");
        $stmt->execute([$login]);
        $hash = $stmt->fetchColumn();
        return $hash && password_verify($password, $hash);
    } catch (PDOException $e) {
        error_log("Admin password check failed: " . $e->getMessage());
        return false;
    }
}

db_connect();
