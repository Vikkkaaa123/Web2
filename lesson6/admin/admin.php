<?php
// 1. Подключение к БД
$db = new PDO('mysql:host=localhost;dbname=u68606', 'u68606', '9347178', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);

// 2. HTTP-авторизация
function checkAdminCredentials($login, $password, $db) {
    $stmt = $db->prepare("SELECT password_hash FROM admins WHERE login = ?");
    $stmt->execute([$login]);
    $admin = $stmt->fetch();
    return $admin && password_verify($password, $admin['password_hash']);
}

if (empty($_SERVER['PHP_AUTH_USER']) || 
    empty($_SERVER['PHP_AUTH_PW']) ||
    !checkAdminCredentials($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'], $db)) {
    
    header('HTTP/1.1 401 Unauthorized');
    header('WWW-Authenticate: Basic realm="Admin Panel"');
    die('<h1>401 Требуется авторизация</h1>');
}

// 3. Обработка удаления
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_id'])) {
    $db->beginTransaction();
    try {
        $db->exec("DELETE FROM application_languages WHERE application_id = ".(int)$_POST['delete_id']);
        $db->exec("DELETE FROM applications WHERE id = ".(int)$_POST['delete_id']);
        $db->commit();
        header('Location: admin.php?success=1');
        exit;
    } catch (PDOException $e) {
        $db->rollBack();
        die('Ошибка удаления: '.$e->getMessage());
    }
}

// 4. Получение данных
$applications = $db->query("
    SELECT a.*, GROUP_CONCAT(l.name SEPARATOR ', ') as langs 
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
<html>
<head>
    <title>Админ-панель</title>
    <style>
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <h1>Админ-панель</h1>
    
    <?php if (isset($_GET['success'])): ?>
        <div style="color: green;">Запись успешно удалена!</div>
    <?php endif; ?>

    <h2>Статистика по языкам</h2>
    <ul>
        <?php foreach ($stats as $stat): ?>
            <li><?= htmlspecialchars($stat['name']) ?>: <?= $stat['count'] ?></li>
        <?php endforeach; ?>
    </ul>

    <h2>Все заявки</h2>
    <table>
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
        <?php foreach ($applications as $app): ?>
        <tr>
            <td><?= $app['id'] ?></td>
            <td><?= htmlspecialchars($app['full_name']) ?></td>
            <td><?= htmlspecialchars($app['phone']) ?></td>
            <td><?= htmlspecialchars($app['email']) ?></td>
            <td><?= $app['birth_date'] ?></td>
            <td><?= $app['gender'] === 'male' ? 'Мужской' : 'Женский' ?></td>
            <td><?= htmlspecialchars($app['langs']) ?></td>
            <td>
                <form method="POST">
                    <input type="hidden" name="delete_id" value="<?= $app['id'] ?>">
                    <button type="submit" onclick="return confirm('Удалить?')">Удалить</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>
