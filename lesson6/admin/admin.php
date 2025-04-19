<?php
// Включение отображения ошибок для диагностики
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 1. Подключение к БД
try {
    $db = new PDO('mysql:host=localhost;dbname=u68606', 'u68606', '9347178', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    die("Ошибка подключения к БД: " . $e->getMessage());
}

// 2. Проверка HTTP-авторизации
if (!isset($_SERVER['PHP_AUTH_USER'])) {
    header('WWW-Authenticate: Basic realm="Admin Panel"');
    header('HTTP/1.1 401 Unauthorized');
    die('<h1 style="text-align: center">Требуется авторизация</h1>');
}

// 3. Проверка учетных данных
$stmt = $db->prepare("SELECT password_hash FROM admins WHERE login = ?");
$stmt->execute([$_SERVER['PHP_AUTH_USER']]);
$admin = $stmt->fetch();

if (!$admin || !password_verify($_SERVER['PHP_AUTH_PW'], $admin['password_hash'])) {
    header('HTTP/1.1 403 Forbidden');
    die('<h1 style="text-align: center">Неверный логин или пароль</h1>');
}

// 4. Обработка удаления записи
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $id = (int)$_POST['delete_id'];
    $db->beginTransaction();
    try {
        $db->exec("DELETE FROM application_languages WHERE application_id = $id");
        $db->exec("DELETE FROM applications WHERE id = $id");
        $db->commit();
        header('Location: admin.php?deleted=1');
        exit;
    } catch (PDOException $e) {
        $db->rollBack();
        die("Ошибка удаления: " . $e->getMessage());
    }
}

// 5. Получение данных
$applications = $db->query("
    SELECT a.*, GROUP_CONCAT(l.name SEPARATOR ', ') as languages
    FROM applications a
    LEFT JOIN application_languages al ON a.id = al.application_id
    LEFT JOIN programming_languages l ON al.language_id = l.id
    GROUP BY a.id
")->fetchAll();

$stats = $db->query("
    SELECT l.name, COUNT(*) as count
    FROM application_languages al
    JOIN programming_languages l ON al.language_id = l.id
    GROUP BY l.name
    ORDER BY count DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Админ-панель</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f2f2f2;
        }
        tr:hover {
            background-color: #f9f9f9;
        }
        .alert {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .alert-success {
            background-color: #dff0d8;
            color: #3c763d;
        }
        button {
            background-color: #d9534f;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 3px;
            cursor: pointer;
        }
        button:hover {
            background-color: #c9302c;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Админ-панель</h1>
        <p>Вы вошли как: <strong><?= htmlspecialchars($_SERVER['PHP_AUTH_USER']) ?></strong></p>

        <?php if (isset($_GET['deleted'])): ?>
            <div class="alert alert-success">
                Запись успешно удалена!
            </div>
        <?php endif; ?>

        <h2>Статистика по языкам программирования</h2>
        <ul>
            <?php foreach ($stats as $stat): ?>
                <li><?= htmlspecialchars($stat['name']) ?>: <?= $stat['count'] ?></li>
            <?php endforeach; ?>
        </ul>

        <h2>Список заявок</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>ФИО</th>
                    <th>Телефон</th>
                    <th>Email</th>
                    <th>Дата рождения</th>
                    <th>Пол</th>
                    <th>Языки</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($applications as $app): ?>
                <tr>
                    <td><?= $app['id'] ?></td>
                    <td><?= htmlspecialchars($app['full_name']) ?></td>
                    <td><?= htmlspecialchars($app['phone']) ?></td>
                    <td><?= htmlspecialchars($app['email']) ?></td>
                    <td><?= $app['birth_date'] ?></td>
                    <td><?= $app['gender'] === 'male' ? 'Мужской' : 'Женский' ?></td>
                    <td><?= htmlspecialchars($app['languages']) ?></td>
                    <td>
                        <form method="POST" onsubmit="return confirm('Удалить эту запись?')">
                            <input type="hidden" name="delete_id" value="<?= $app['id'] ?>">
                            <button type="submit">Удалить</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
