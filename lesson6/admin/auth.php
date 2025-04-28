<?php

function verifyAdminCredentials($login, $password) {
    global $db;
    
    $stmt = $db->prepare("SELECT password_hash FROM admins WHERE login = ?");
    $stmt->execute([$login]);
    $admin = $stmt->fetch();
    
    error_log("Checking admin login: $login");
    error_log("Password hash from DB: " . ($admin['password_hash'] ?? 'NULL'));
    error_log("Password verify result: " . (password_verify($password, $admin['password_hash']) ? 'true' : 'false'));
    
    return $admin && password_verify($password, $admin['password_hash']);
}


function checkAdminAuth() {
    global $db;
    
    error_log("Admin auth started"); 
    
    if (empty($_SERVER['PHP_AUTH_USER']) || empty($_SERVER['PHP_AUTH_PW'])) {
        error_log("Empty credentials"); 
        header('HTTP/1.1 401 Unauthorized');
        header('WWW-Authenticate: Basic realm="Admin Panel"');
        die('<h1>401 Требуется авторизация</h1>');
    }
    
    $login = $_SERVER['PHP_AUTH_USER'];
    $password = $_SERVER['PHP_AUTH_PW'];
    
    error_log("Trying to auth: login=".$login); 
    
    try {
        $stmt = $db->prepare("SELECT password_hash FROM admins WHERE login = ?");
        $stmt->execute([$login]);
        $admin = $stmt->fetch();
        
        error_log("DB result: ".print_r($admin, true)); 
        
        if (!$admin || !password_verify($password, $admin['password_hash'])) {
            error_log("Auth failed for login: ".$login); 
            header('HTTP/1.1 401 Unauthorized');
            header('WWW-Authenticate: Basic realm="Admin Panel"');
            die('<h1>401 Неверные учетные данные</h1>');
        }
        
        error_log("Auth successful for login: ".$login); 
    } catch (PDOException $e) {
        error_log("Auth error: ".$e->getMessage()); 
        die('Ошибка авторизации: ' . $e->getMessage());
    }
}
?>
