<?php
require_once 'auth.php';
checkAdminAuth();

$db = new PDO('mysql:host=localhost;dbname=u68606', 'u68606', '9347178');

// Получение всех данных пользователей
$users = $db->query("
    SELECT u.*, 
    GROUP_CONCAT(l.name SEPARATOR ', ') as languages
    FROM users u
    LEFT JOIN user_languages ul ON u.id = ul.user_id
    LEFT JOIN languages l ON ul.language_id = l.id
    GROUP BY u.id
")->fetchAll();

// Статистика по языкам
$stats = $db->query("
    SELECT l.name, COUNT(ul.user_id) as count
    FROM languages l
    LEFT JOIN user_languages ul ON l.id = ul.language_id
    GROUP BY l.id
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
        tr:nth-child(even) { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <h1>Админ-панель</h1>
    
    <h2>Статистика по языкам</h2>
    <table>
        <tr><th>Язык</th><th>Количество пользователей</th></tr>
        <?php foreach ($stats as $stat): ?>
        <tr>
            <td><?= htmlspecialchars($stat['name']) ?></td>
            <td><?= $stat['count'] ?></td>
        </tr>
        <?php endforeach; ?>
    </table>

    <h2>Все пользователи</h2>
    <table>
        <tr>
            <th>ID</th><th>Логин</th><th>Email</th><th>Языки</th><th>Действия</th>
        </tr>
        <?php foreach ($users as $user): ?>
        <tr>
            <td><?= $user['id'] ?></td>
            <td><?= htmlspecialchars($user['login']) ?></td>
            <td><?= htmlspecialchars($user['email']) ?></td>
            <td><?= htmlspecialchars($user['languages']) ?></td>
            <td>
                <a href="edit.php?id=<?= $user['id'] ?>">Редактировать</a> |
                <a href="delete.php?id=<?= $user['id'] ?>" onclick="return confirm('Удалить?')">Удалить</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>
