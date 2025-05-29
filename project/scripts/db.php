<?php
/**
 * Database connection and operations
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
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ]);

            // Проверка существования необходимых таблиц
            $requiredTables = ['programming_languages', 'applications', 'users', 'admins'];
            foreach ($requiredTables as $table) {
                $db->query("SELECT 1 FROM `{$table}` LIMIT 1");
            }
            
            return $db;
            
        } catch (PDOException $e) {
            error_log("DB Connection Error [{$dsn}]: " . $e->getMessage());
            throw new Exception("Ошибка подключения к БД. Попробуйте позже.");
        }
    }
    
    return $db;
}

/**
 * Execute query and return single row
 */
function db_row($query, ...$params) {
    try {
        $stmt = db_query($query, ...$params);
        return $stmt ? $stmt->fetch() : false;
    } catch (PDOException $e) {
        error_log("DB Row Error: {$query} - " . $e->getMessage());
        return false;
    }
}

/**
 * Execute query and return statement handler
 */
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

/**
 * Execute command (INSERT/UPDATE/DELETE) and return affected rows count
 */
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

/**
 * Get last inserted ID
 */
function db_insert_id() {
    $db = db_connect();
    return $db ? $db->lastInsertId() : false;
}

/**
 * Execute query and return single value
 */
function db_result($query, ...$params) {
    $row = db_row($query, ...$params);
    return $row ? reset($row) : false;
}

/**
 * Check if admin exists
 */
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

/**
 * Verify admin password
 */
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

/**
 * Get list of programming languages
 */
function getLangs() {
    $db = db_connect();
    if (!$db) return [];
    
    try {
        $stmt = $db->query("SELECT id, name FROM programming_languages");
        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    } catch (PDOException $e) {
        error_log('DB Error (getLangs): ' . $e->getMessage());
        return [];
    }
}

// Устанавливаем соединение при первом включении файла
try {
    db_connect();
} catch (Exception $e) {
    // Ошибка уже записана в лог
}
