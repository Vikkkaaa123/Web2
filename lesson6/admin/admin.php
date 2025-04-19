<?php
require_once 'auth.php'; // Подключение HTTP-авторизации

// Подключение к БД
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

// Обработка удаления записи
if (isset($_GET['delete_id'])) {
    $id = (int)$_GET['delete_id'];
    $db->beginTransaction();
    try {
        $db->exec("DELETE FROM application_languages WHERE application_id = $id");
        $db->exec("DELETE FROM applications WHERE id = $id");
        $db->commit();
        header('Location: admin.php?success=1');
    } catch (PDOException $e) {
        $db->rollBack();
        die('Ошибка удаления: ' . $e->getMessage());
    }
}

// Получение всех заявок
$applications = $db->query("
    SELECT a.*, GROUP_CONCAT(l.name SEPARATOR ', ') as langs 
    FROM applications a
    LEFT JOIN application_languages al ON a.id = al.application_id
    LEFT JOIN programming_languages l ON al.language_id = l.id
    GROUP BY a.id
")->fetchAll(PDO::FETCH_ASSOC);

// Статистика по языкам
$stats = $db->query("
    SELECT l.name, COUNT(*) as count 
    FROM application_languages al
    JOIN programming_languages l ON al.language_id = l.id
    GROUP BY l.name
    ORDER BY count DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Админ-панель</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h1>Админ-панель</h1>
    
    <!-- Статистика -->
    <div class="stats">
        <h2>Статистика по языкам</h2>
        <ul>
            <?php foreach ($stats as $item): ?>
                <li><?= htmlspecialchars($item['name']) ?>: <?= $item['count'] ?></li>
            <?php endforeach; ?>
        </ul>
    </div>

    <!-- Таблица заявок -->
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
                <a href="edit.php?id=<?= $app['id'] ?>">✏️</a>
                <a href="?delete_id=<?= $app['id'] ?>" onclick="return confirm('Удалить?')">❌</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>
