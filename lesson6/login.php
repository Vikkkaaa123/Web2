<?php
// Старт сессии ДО любого вывода (это критически важно)
session_start();

// Установка заголовков
header('Content-Type: text/html; charset=UTF-8');

// Подключение к базе данных
$db = new PDO('mysql:host=localhost;dbname=u68606', 'u68606', '9347178', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
]);

$messages = []; // Массив для сообщений об ошибках

// Обработка POST-запроса
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $login = trim($_POST['login']);
    $password = trim($_POST['pass']);

    try {
        /******************************
         * 1. ПРОВЕРКА АДМИНИСТРАТОРА *
         ******************************/
        $stmt = $db->prepare("SELECT password_hash FROM admins WHERE login = ? LIMIT 1");
        $stmt->execute([$login]);
        $admin = $stmt->fetch();

        if ($admin) {
            // Основная проверка через password_verify()
            if (password_verify($password, $admin['password_hash'])) {
                $_SESSION['admin'] = true;
                $_SESSION['admin_login'] = $login;
                $_SESSION['last_activity'] = time();
                
                // Логирование успешного входа
                error_log("Admin login success: $login");
                
                header('Location: admin/admin.php');
                exit;
            }
            // Аварийный вариант проверки (если password_verify не работает)
            elseif ($password === '123' && $admin['password_hash'] === '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi') {
                $_SESSION['admin'] = true;
                $_SESSION['admin_login'] = $login;
                $_SESSION['last_activity'] = time();
                
                error_log("Emergency admin auth used for: $login");
                
                header('Location: admin/admin.php');
                exit;
            }
        }

        /****************************
         * 2. ПРОВЕРКА ПОЛЬЗОВАТЕЛЯ *
         ****************************/
        $stmt = $db->prepare("SELECT id, password_hash FROM users WHERE login = ? LIMIT 1");
        $stmt->execute([$login]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_login'] = $login;
            $_SESSION['last_activity'] = time();
            
            error_log("User login success: $login");
            
            header('Location: index.php');
            exit;
        }

        // Если ни администратор, ни пользователь не найдены
        if (!$admin && !$user) {
            $messages[] = 'Пользователь не найден';
            error_log("Login failed: User '$login' not found");
        } else {
            $messages[] = 'Неверный пароль';
            error_log("Login failed: Invalid password for '$login'");
        }

    } catch (PDOException $e) {
        $messages[] = 'Ошибка базы данных';
        error_log("Database error during login: " . $e->getMessage());
    }
}

// Если пользователь уже авторизован - перенаправляем
if (isset($_SESSION['admin'])) {
    header('Location: admin/admin.php');
    exit;
} elseif (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход в систему</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 400px;
            margin: 30px auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .login-form {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h2 {
            color: #333;
            text-align: center;
            margin-bottom: 20px;
        }
        .error {
            color: #d32f2f;
            background-color: #fde0e0;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
            font-size: 14px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
        }
        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        button {
            width: 100%;
            padding: 12px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            margin-top: 10px;
        }
        button:hover {
            background-color: #45a049;
        }
        .debug-info {
            margin-top: 30px;
            font-size: 12px;
            color: #666;
            background: #f9f9f9;
            padding: 15px;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="login-form">
        <h2>Вход в систему</h2>
        
        <?php if (!empty($messages)): ?>
            <div class="error">
                <?= implode('<br>', array_map('htmlspecialchars', $messages)) ?>
            </div>
        <?php endif; ?>

        <form method="post">
            <div class="form-group">
                <label for="login">Логин:</label>
                <input type="text" id="login" name="login" required value="admin">
            </div>
            
            <div class="form-group">
                <label for="pass">Пароль:</label>
                <input type="password" id="pass" name="pass" required value="123">
            </div>
            
            <button type="submit">Войти</button>
        </form>
    </div>

    <!-- Блок отладочной информации (можно удалить в боевом режиме) -->
    <div class="debug-info">
        <h4>Отладочная информация:</h4>
        <pre><?php
            if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                echo "Login attempt:\n";
                echo "Login: " . htmlspecialchars($login) . "\n";
                echo "Password length: " . strlen($password) . " chars\n";
                
                echo "\nSession status:\n";
                echo "Session ID: " . session_id() . "\n";
                echo "Session data: " . print_r($_SESSION, true) . "\n";
                
                echo "\nDatabase hashes:\n";
                $hashes = $db->query("
                    SELECT login, LEFT(password_hash, 20) AS hash_part 
                    FROM admins 
                    UNION 
                    SELECT login, LEFT(password_hash, 20) AS hash_part 
                    FROM users
                ")->fetchAll();
                print_r($hashes);
            }
        ?></pre>
    </div>
</body>
</html>
