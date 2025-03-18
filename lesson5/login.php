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

// Если запрос был методом POST, проверяем логин и пароль
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $login = $_POST['login'];
    $pass = $_POST['pass'];

    // Ищем пользователя в таблице `users`
    $stmt = $db->prepare("SELECT * FROM users WHERE login = ?");
    $stmt->execute([$login]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Проверяем пароль
    if ($user && password_verify($pass, $user['password_hash'])) {
        // Начинаем сессию и сохраняем данные
        $_SESSION['login'] = $login;
        header('Location: index.php');
        exit();
    } else {
        echo 'Неверный логин или пароль.';
    }
}

// Если запрос был методом GET, выводим форму входа
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
?>
<form action="" method="post">
  <input name="login" placeholder="Логин" required />
  <input name="pass" type="password" placeholder="Пароль" required />
  <input type="submit" value="Войти" />
</form>
<?php
}