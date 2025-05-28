<?php

global $db;

// Подключение к базе данных
$user = 'u68606';
$pass = '9347178'; 
$db = new PDO('mysql:host=localhost;dbname=u68606', $user, $pass, [
    PDO::ATTR_PERSISTENT => true,
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'
]);

/**
 * Получение списка языков программирования
 */
function getLangs() {
    global $db;
    try {
        $stmt = $db->query("SELECT id, name FROM programming_languages");
        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    } catch(PDOException $e) {
        error_log('DB Error (getLangs): ' . $e->getMessage());
        return [];
    }
}

/**
 * Проверка пароля пользователя
 */
function password_check($login, $password) {
    global $db;
    try {
        $stmt = $db->prepare("SELECT password_hash FROM users WHERE login = ?");
        $stmt->execute([$login]);
        $hash = $stmt->fetchColumn();
        return $hash && password_verify($password, $hash);
    } catch(PDOException $e) {
        error_log('DB Error (password_check): ' . $e->getMessage());
        return false;
    }
}

/**
 * Проверка пароля администратора
 */
function admin_password_check($login, $password) {
    global $db;
    try {
        $stmt = $db->prepare("SELECT password_hash FROM admins WHERE login = ?");
        $stmt->execute([$login]);
        $hash = $stmt->fetchColumn();
        return $hash && password_verify($password, $hash);
    } catch(PDOException $e) {
        error_log('DB Error (admin_password_check): ' . $e->getMessage());
        return false;
    }
}

/**
 * Сохранение новой заявки
 */
function saveApplication($data) {
    global $db;
    
    $db->beginTransaction();
    try {
        // Сохранение основной информации
        $stmt = $db->prepare("
            INSERT INTO applications 
            (full_name, phone, email, birth_date, gender, biography, agreement) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['fio'],
            $data['phone'],
            $data['email'],
            $data['birth_date'],
            $data['gender'],
            $data['biography'],
            $data['agreement']
        ]);
        $appId = $db->lastInsertId();

        // Сохранение языков программирования
        $stmt = $db->prepare("
            INSERT INTO application_languages 
            (application_id, language_id) 
            VALUES (?, ?)
        ");
        foreach ($data['languages'] as $langId) {
            $stmt->execute([$appId, $langId]);
        }

        // Создание пользователя
        $stmt = $db->prepare("
            INSERT INTO users 
            (login, password_hash) 
            VALUES (?, ?)
        ");
        $stmt->execute([$data['login'], $data['password_hash']]);
        $userId = $db->lastInsertId();

        // Связь пользователя с заявкой
        $stmt = $db->prepare("
            INSERT INTO user_applications 
            (user_id, application_id) 
            VALUES (?, ?)
        ");
        $stmt->execute([$userId, $appId]);

        $db->commit();
        return [
            'application_id' => $appId,
            'user_id' => $userId,
            'login' => $data['login'],
            'password' => $data['password']
        ];
    } catch(PDOException $e) {
        $db->rollBack();
        error_log('DB Error (saveApplication): ' . $e->getMessage());
        return false;
    }
}

/**
 * Получение данных заявки по логину пользователя
 */
function getApplicationByLogin($login) {
    global $db;
    try {
        $stmt = $db->prepare("
            SELECT a.* FROM applications a
            JOIN user_applications ua ON a.id = ua.application_id
            JOIN users u ON ua.user_id = u.id
            WHERE u.login = ?
        ");
        $stmt->execute([$login]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log('DB Error (getApplicationByLogin): ' . $e->getMessage());
        return false;
    }
}

/**
 * Обновление данных заявки
 */
function updateApplication($appId, $data) {
    global $db;
    
    $db->beginTransaction();
    try {
        // Обновление основной информации
        $stmt = $db->prepare("
            UPDATE applications SET
            full_name = ?,
            phone = ?,
            email = ?,
            birth_date = ?,
            gender = ?,
            biography = ?,
            agreement = ?
            WHERE id = ?
        ");
        $stmt->execute([
            $data['fio'],
            $data['phone'],
            $data['email'],
            $data['birth_date'],
            $data['gender'],
            $data['biography'],
            $data['agreement'],
            $appId
        ]);

        // Удаление старых языков
        $stmt = $db->prepare("
            DELETE FROM application_languages 
            WHERE application_id = ?
        ");
        $stmt->execute([$appId]);

        // Добавление новых языков
        $stmt = $db->prepare("
            INSERT INTO application_languages 
            (application_id, language_id) 
            VALUES (?, ?)
        ");
        foreach ($data['languages'] as $langId) {
            $stmt->execute([$appId, $langId]);
        }

        $db->commit();
        return true;
    } catch(PDOException $e) {
        $db->rollBack();
        error_log('DB Error (updateApplication): ' . $e->getMessage());
        return false;
    }
}

/**
 * Получение статистики по языкам
 */
function getLanguagesStats() {
    global $db;
    try {
        $stmt = $db->query("
            SELECT pl.name, COUNT(al.application_id) as count
            FROM programming_languages pl
            LEFT JOIN application_languages al ON pl.id = al.language_id
            GROUP BY pl.id
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log('DB Error (getLanguagesStats): ' . $e->getMessage());
        return [];
    }
}

/**
 * Получение списка всех заявок (для админки)
 */
function getAllApplications() {
    global $db;
    try {
        $stmt = $db->query("
            SELECT 
                a.id, 
                a.full_name, 
                a.phone, 
                a.email, 
                a.gender,
                a.birth_date,
                GROUP_CONCAT(pl.name SEPARATOR ', ') as languages
            FROM applications a
            LEFT JOIN application_languages al ON a.id = al.application_id
            LEFT JOIN programming_languages pl ON al.language_id = pl.id
            GROUP BY a.id
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log('DB Error (getAllApplications): ' . $e->getMessage());
        return [];
    }
}

/**
 * Удаление заявки
 */
function deleteApplication($appId) {
    global $db;
    try {
        $db->beginTransaction();
        
        // Удаление связей с языками
        $stmt = $db->prepare("DELETE FROM application_languages WHERE application_id = ?");
        $stmt->execute([$appId]);
        
        // Удаление связи пользователя с заявкой
        $stmt = $db->prepare("DELETE FROM user_applications WHERE application_id = ?");
        $stmt->execute([$appId]);
        
        // Удаление пользователя
        $stmt = $db->prepare("
            DELETE u FROM users u
            JOIN user_applications ua ON u.id = ua.user_id
            WHERE ua.application_id = ?
        ");
        $stmt->execute([$appId]);
        
        // Удаление самой заявки
        $stmt = $db->prepare("DELETE FROM applications WHERE id = ?");
        $stmt->execute([$appId]);
        
        $db->commit();
        return true;
    } catch(PDOException $e) {
        $db->rollBack();
        error_log('DB Error (deleteApplication): ' . $e->getMessage());
        return false;
    }
}

// Вспомогательные функции для работы с БД
function db_query($query, ...$params) {
    global $db;
    try {
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log('DB Error (db_query): ' . $e->getMessage());
        return false;
    }
}

function db_execute($query, ...$params) {
    global $db;
    try {
        $stmt = $db->prepare($query);
        return $stmt->execute($params);
    } catch(PDOException $e) {
        error_log('DB Error (db_execute): ' . $e->getMessage());
        return false;
    }
}

function db_last_insert_id() {
    global $db;
    return $db->lastInsertId();
}
