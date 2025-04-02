<?php
session_start();
header('Content-Type: text/html; charset=UTF-8');

// Подключение к базе данных
$user = 'u68606'; 
$pass = '9347178'; 

try {
    $db = new PDO('mysql:host=localhost;dbname=u68606', $user, $pass, [
        PDO::ATTR_PERSISTENT => true,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    die('Ошибка подключения: ' . $e->getMessage());
}

// Если пользователь уже авторизован, перенаправляем на главную страницу
if (!empty($_SESSION['login'])) {
    header('Location: index.php');
    exit();
}

$messages = array();

// Если запрос был методом POST, проверяем логин и пароль
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $login = trim($_POST['login'] ?? '');
    $pass = trim($_POST['pass'] ?? '');

    if (empty($login) || empty($pass)) {
        $messages[] = '<div class="error">Заполните все поля</div>';
    } else {
        try {
            $stmt = $db->prepare("SELECT id, login, password_hash FROM users WHERE login = ?");
            $stmt->execute([$login]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($pass, $user['password_hash'])) {
                $_SESSION['login'] = $user['login'];
                $_SESSION['uid'] = $user['id'];
                header('Location: index.php');
                exit();
            } else {
                $messages[] = '<div class="error">Неверный логин или пароль</div>';
            }
        } catch (PDOException $e) {
            $messages[] = '<div class="error">Ошибка при проверке данных</div>';
        }
    }
}

// Получаем сгенерированные данные из кук
$generated_login = $_COOKIE['login'] ?? '';
$generated_password = $_COOKIE['password'] ?? '';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Вход в систему</title>
    <style>
        .error { color: red; }
        .success { color: green; }
        .info { color: blue; }
        .form-group { margin-bottom: 15px; }
        input { padding: 5px; width: 200px; }
    </style>
</head>
<body>
    <h1>Вход в систему</h1>
    
    <?php foreach ($messages as $message): ?>
        <?php echo $message; ?>
    <?php endforeach; ?>
    
    <form action="" method="post">
        <div class="form-group">
            <label>Логин:</label><br>
            <input name="login" required value="<?php echo htmlspecialchars($generated_login); ?>">
        </div>
        
        <div class="form-group">
            <label>Пароль:</label><br>
            <input name="pass" type="password" required value="<?php echo htmlspecialchars($generated_password); ?>">
        </div>
        
        <div class="form-group">
            <input type="submit" value="Войти">
        </div>
    </form>
    
    <p>Нет аккаунта? <a href="index.php">Зарегистрируйтесь</a></p>
</body>
</html>
