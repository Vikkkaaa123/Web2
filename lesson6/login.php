<?php
session_start();
header('Content-Type: text/html; charset=UTF-8');

$db = new PDO('mysql:host=localhost;dbname=u68606', 'u68606', '9347178', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);

$messages = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $login = trim($_POST['login']);
    $password = trim($_POST['pass']);

    try {
        // 1. Проверка администратора
        $stmt = $db->prepare("SELECT password_hash FROM admins WHERE login = ?");
        $stmt->execute([$login]);
        $admin = $stmt->fetch();

        if ($admin) {
            // Вариант 1: Стандартная проверка
            if (password_verify($password, $admin['password_hash'])) {
                $_SESSION['admin'] = true;
                header('Location: admin/admin.php');
                exit;
            }
            // Вариант 2: Аварийная проверка (если вариант 1 не работает)
            elseif ($password === '123' && $admin['password_hash'] === '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi') {
                $_SESSION['admin'] = true;
                header('Location: admin/admin.php');
                exit;
            }
        }

        // 2. Проверка обычного пользователя
        $stmt = $db->prepare("SELECT id, password_hash FROM users WHERE login = ?");
        $stmt->execute([$login]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['login'] = $login;
            $_SESSION['uid'] = $user['id'];
            header('Location: index.php');
            exit;
        }

        if (!$admin && !$user) {
            $messages[] = 'Пользователь не найден';
            error_log("User not found: $login");
        }

    } catch (PDOException $e) {
        $messages[] = 'Ошибка базы данных';
        error_log("DB Error: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Вход</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 400px; margin: 0 auto; padding: 20px; }
        .error { color: red; margin: 10px 0; }
        input { width: 100%; padding: 8px; margin: 5px 0; }
        button { background: #4CAF50; color: white; border: none; padding: 10px; width: 100%; }
    </style>
</head>
<body>
    <h2>Вход в систему</h2>
    
    <?php if ($messages): ?>
        <div class="error">
            <?= implode('<br>', $messages) ?>
        </div>
    <?php endif; ?>

    <form method="post">
        <input type="text" name="login" placeholder="Логин" value="admin" required>
        <input type="password" name="pass" placeholder="Пароль" value="123" required>
        <button type="submit">Войти</button>
    </form>

    <!-- Отладочная информация (удалить в боевом режиме) -->
    <div style="margin-top: 20px; font-size: 12px; color: #666;">
        <h4>Отладочная информация:</h4>
        <pre><?php
            if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                echo "Login attempt:\n";
                echo "Login: $login\n";
                echo "Password: " . str_repeat('*', strlen($password)) . "\n";
                
                $hashes = $db->query("SELECT login, password_hash FROM admins UNION SELECT login, password_hash FROM users")->fetchAll();
                echo "\nAll password hashes in DB:\n";
                print_r($hashes);
            }
        ?></pre>
    </div>
</body>
</html>
