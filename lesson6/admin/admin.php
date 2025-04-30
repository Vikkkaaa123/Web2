<?php
require_once 'auth.php';
checkAdminAuth();

$db = new PDO('mysql:host=localhost;dbname=u68606', 'u68606', '9347178');

// Получение всех заявок (applications) с языками
$applications = $db->query("
    SELECT a.*, 
    GROUP_CONCAT(p.name SEPARATOR ', ') as languages,
    u.login as user_login
    FROM applications a
    LEFT JOIN application_languages al ON a.id = al.application_id
    LEFT JOIN programming_languages p ON al.language_id = p.id
    LEFT JOIN user_applications ua ON a.id = ua.application_id
    LEFT JOIN users u ON ua.user_id = u.id
    GROUP BY a.id
")->fetchAll();

// Статистика по языкам
$stats = $db->query("
    SELECT p.name, COUNT(al.application_id) as count
    FROM programming_languages p
    LEFT JOIN application_languages al ON p.id = al.language_id
    GROUP BY p.id
    ORDER BY count DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Админ-панель</title>
    <style>
        table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        tr:nth-child(even) { background-color: #f9f9f9; }
    </style>
</head>
<body>
    <h1>Админ-панель</h1>
    
    <h2>Статистика по языкам программирования</h2>
    <table>
        <tr><th>Язык</th><th>Количество выборов</th></tr>
        <?php foreach ($stats as $stat): ?>
        <tr>
            <td><?= htmlspecialchars($stat['name']) ?></td>
            <td><?= $stat['count'] ?></td>
        </tr>
        <?php endforeach; ?>
    </table>

    <h2>Все заявки пользователей</h2>
    <table>
        <tr>
            <th>ID</th>
            <th>Пользователь</th>
            <th>ФИО</th>
            <th>Email</th>
            <th>Языки</th>
            <th>Действия</th>
        </tr>
        <?php foreach ($applications as $app): ?>
        <tr>
            <td><?= $app['id'] ?></td>
            <td><?= htmlspecialchars($app['user_login']) ?></td>
            <td><?= htmlspecialchars($app['full_name']) ?></td>
            <td><?= htmlspecialchars($app['email']) ?></td>
            <td><?= htmlspecialchars($app['languages'] ?? 'Не указано') ?></td>
            <td>
                <a href="edit.php?id=<?= $app['id'] ?>">Редактировать</a> |
                <a href="delete.php?id=<?= $app['id'] ?>" onclick="return confirm('Удалить эту заявку?')">Удалить</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>
