<?php
session_start();
require 'auth.php';

checkAdminAuth();

// Проверка авторизации
if (empty($_SESSION['admin'])) {
    header('Location: ../login.php');
    exit();
}

// Получение данных из БД
$applications = $db->query("
    SELECT a.*, GROUP_CONCAT(l.name SEPARATOR ', ') as languages 
    FROM applications a
    LEFT JOIN application_languages al ON a.id = al.application_id
    LEFT JOIN programming_languages l ON al.language_id = l.id
    GROUP BY a.id
")->fetchAll();

// Статистика по языкам
$stats = $db->query("
    SELECT l.name, COUNT(al.application_id) as count
    FROM programming_languages l
    LEFT JOIN application_languages al ON l.id = al.language_id
    GROUP BY l.id
    ORDER BY count DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Админ-панель</title>
    <style>
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 8px; }
        th { background-color: #f2f2f2; }
        .action-btns { display: flex; gap: 5px; }
        .btn { padding: 5px 10px; text-decoration: none; color: white; border-radius: 3px; }
        .edit { background: #4CAF50; }
        .delete { background: #f44336; }
    </style>
</head>
<body>
    <h1>Админ-панель</h1>
    
    <h2>Статистика по языкам</h2>
    <table>
        <tr><th>Язык</th><th>Количество</th></tr>
        <?php foreach ($stats as $stat): ?>
        <tr>
            <td><?= htmlspecialchars($stat['name']) ?></td>
            <td><?= $stat['count'] ?></td>
        </tr>
        <?php endforeach; ?>
    </table>

    <h2>Все заявки</h2>
    <table>
        <tr>
            <th>ID</th>
            <th>ФИО</th>
            <th>Телефон</th>
            <th>Email</th>
            <th>Дата рождения</th>
            <th>Языки</th>
            <th>Действия</th>
        </tr>
        <?php foreach ($applications as $app): ?>
        <tr>
            <td><?= $app['id'] ?></td>
            <td><?= htmlspecialchars($app['full_name']) ?></td>
            <td><?= htmlspecialchars($app['phone']) ?></td>
            <td><?= htmlspecialchars($app['email']) ?></td>
            <td><?= date('d.m.Y', strtotime($app['birth_date'])) ?></td>
            <td><?= htmlspecialchars($app['languages']) ?></td>
            <td class="action-btns">
                <a href="edit.php?id=<?= $app['id'] ?>" class="btn edit">Редактировать</a>
                <a href="delete.php?id=<?= $app['id'] ?>" class="btn delete" onclick="return confirm('Удалить?')">Удалить</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>
