<?php
function checkAdminAuth() {
    global $db;
    
    file_put_contents('auth_log.txt', date('Y-m-d H:i:s')." - Auth started\n", FILE_APPEND);
    
    if (empty($_SERVER['PHP_AUTH_USER']) || empty($_SERVER['PHP_AUTH_PW'])) {
        file_put_contents('auth_log.txt', "Empty credentials\n", FILE_APPEND);
        header('HTTP/1.1 401 Unauthorized');
        header('WWW-Authenticate: Basic realm="Admin Panel"');
        die('<h1>401 Требуется авторизация</h1>');
    }
    
    $login = $_SERVER['PHP_AUTH_USER'];
    $password = $_SERVER['PHP_AUTH_PW'];
    
    file_put_contents('auth_log.txt', "Attempt: $login\n", FILE_APPEND);
    
    try {
        $stmt = $db->prepare("SELECT password_hash FROM admins WHERE login = ?");
        $stmt->execute([$login]);
        $admin = $stmt->fetch();
        
        file_put_contents('auth_log.txt', "DB result: ".print_r($admin, true)."\n", FILE_APPEND);
        
        if (!$admin) {
            file_put_contents('auth_log.txt', "User not found\n", FILE_APPEND);
            header('HTTP/1.1 401 Unauthorized');
            header('WWW-Authenticate: Basic realm="Admin Panel"');
            die('<h1>401 Неверные учетные данные</h1>');
        }
        
        $hash = $admin['password_hash'];
        $verify = password_verify($password, $hash);
        
        file_put_contents('auth_log.txt', "Password verify: ".($verify ? 'true' : 'false')."\n", FILE_APPEND);
        file_put_contents('auth_log.txt', "Input password: $password\n", FILE_APPEND);
        file_put_contents('auth_log.txt', "Stored hash: $hash\n", FILE_APPEND);
        
        if (!$verify) {
            error_log("FAILED AUTH: login=$login, password=$password, hash=$hash");
            header('HTTP/1.1 401 Unauthorized');
            header('WWW-Authenticate: Basic realm="Admin Panel"');
            die('<h1>401 Неверные учетные данные</h1>');
        }
        
        file_put_contents('auth_log.txt', "Auth successful\n", FILE_APPEND);
        
    } catch (PDOException $e) {
        file_put_contents('auth_log.txt', "Error: ".$e->getMessage()."\n", FILE_APPEND);
        die('Ошибка авторизации: ' . $e->getMessage());
    }
}
?>
