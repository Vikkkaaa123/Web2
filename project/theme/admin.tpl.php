<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./styles/table.css">
    <title>Админ-панель</title>
</head>
<body>
    <div class="admin-container">
        <h1>Админ-панель</h1>
        <a href="logout.php" class="button admin-logout">Выйти</a>

        <div class="stats">
            <h2>Статистика по языкам программирования</h2>
            <table>
                <tr><th>Язык</th><th>Количество выборов</th></tr>
                <?php foreach ($stats as $stat): ?>
                <tr>
                    <td><?= htmlspecialchars($stat['name']) ?></td>
                    <td><?= htmlspecialchars($stat['total'] ?? 0) ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>

        <h2>Все заявки пользователей (всего: <?= count($user_table) ?>)</h2>
        <table>
            <tr>
                <th>ID</th>
                <th>Пользователь</th>
                <th>ФИО</th>
                <th>Email</th>
                <th>Телефон</th>
                <th>Дата рождения</th>
                <th>Пол</th>
                <th>Языки</th>
                <th>Биография</th>
                <th>Согласие</th>
                <th>Действия</th>
            </tr>
            <?php foreach ($user_table as $app): ?>
            <tr>
                <td><?= htmlspecialchars($app['id']) ?></td>
                <td><?= htmlspecialchars($app['user_login'] ?? 'N/A') ?></td>
                <td><?= htmlspecialchars($app['full_name']) ?></td>
                <td><?= htmlspecialchars($app['email']) ?></td>
                <td><?= htmlspecialchars($app['phone']) ?></td>
                <td><?= htmlspecialchars($app['birth_date']) ?></td>
                <td><?= htmlspecialchars($app['gender_short']) ?></td>
                <td><?= htmlspecialchars($app['languages']) ?></td>
                <td><?= htmlspecialchars(mb_substr($app['biography'], 0, 50)) ?><?= mb_strlen($app['biography']) > 50 ? '...' : '' ?></td>
                <td><?= $app['agreement'] ? 'Да' : 'Нет' ?></td>
                <td>
                    <a href="edit.php?id=<?= $app['id'] ?>" class="button">Редактировать</a>
                    <a href="delete.php?id=<?= $app['id'] ?>" class="button" onclick="return confirm('Удалить эту заявку?')">Удалить</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
</body>
</html>
