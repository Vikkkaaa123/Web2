<?php
// admin/admin.php
session_start();
header('Content-Type: text/html; charset=UTF-8');

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

// Проверка HTTP-авторизации
if (!isset($_SERVER['PHP_AUTH_USER']) {
    header('WWW-Authenticate: Basic realm="Admin Panel"');
    header('HTTP/1.0 401 Unauthorized');
    echo 'Требуется авторизация';
    exit;
} else {
    // Проверка логина и пароля в таблице admins
    $stmt = $db->prepare("SELECT password_hash FROM admins WHERE login = ?");
    $stmt->execute([$_SERVER['PHP_AUTH_USER']]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$admin || !password_verify($_SERVER['PHP_AUTH_PW'], $admin['password_hash'])) {
        header('WWW-Authenticate: Basic realm="Admin Panel"');
        header('HTTP/1.0 401 Unauthorized');
        echo 'Неверные учетные данные';
        exit;
    }
}

// Получение всех заявок
$stmt = $db->query("
    SELECT a.id, a.full_name, a.phone, a.email, a.birth_date, 
           a.gender, a.biography, a.agreement, 
           GROUP_CONCAT(pl.name SEPARATOR ', ') as languages
    FROM applications a
    LEFT JOIN application_languages al ON a.id = al.application_id
    LEFT JOIN programming_languages pl ON al.language_id = pl.id
    GROUP BY a.id
");
$applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Получение статистики по языкам
$stmt = $db->query("
    SELECT pl.name, COUNT(al.application_id) as count
    FROM programming_languages pl
    LEFT JOIN application_languages al ON pl.id = al.language_id
    GROUP BY pl.id
    ORDER BY count DESC
");
$stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Обработка удаления заявки
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    try {
        $db->beginTransaction();
        
        // Удаляем связанные языки
        $stmt = $db->prepare("DELETE FROM application_languages WHERE application_id = ?");
        $stmt->execute([$id]);
        
        // Удаляем связь с пользователем
        $stmt = $db->prepare("DELETE FROM user_applications WHERE application_id = ?");
        $stmt->execute([$id]);
        
        // Удаляем саму заявку
        $stmt = $db->prepare("DELETE FROM applications WHERE id = ?");
        $stmt->execute([$id]);
        
        $db->commit();
        header("Location: admin.php?deleted=1");
        exit;
    } catch (PDOException $e) {
        $db->rollBack();
        die('Ошибка при удалении: ' . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админ-панель</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .stats { margin-top: 30px; }
        .action-btn { margin: 2px; padding: 4px 8px; text-decoration: none; }
        .edit-btn { background-color: #4CAF50; color: white; }
        .delete-btn { background-color: #f44336; color: white; }
        .success { color: green; margin-bottom: 15px; }
    </style>
</head>
<body>
    <h1>Админ-панель</h1>
    
    <?php if (isset($_GET['deleted'])): ?>
        <div class="success">Заявка успешно удалена!</div>
    <?php endif; ?>
    
    <h2>Все заявки</h2>
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
                <th>Биография</th>
                <th>Согласие</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($applications as $app): ?>
                <tr>
                    <td><?= htmlspecialchars($app['id']) ?></td>
                    <td><?= htmlspecialchars($app['full_name']) ?></td>
                    <td><?= htmlspecialchars($app['phone']) ?></td>
                    <td><?= htmlspecialchars($app['email']) ?></td>
                    <td><?= htmlspecialchars($app['birth_date']) ?></td>
                    <td><?= $app['gender'] == 'male' ? 'Мужской' : 'Женский' ?></td>
                    <td><?= htmlspecialchars($app['languages']) ?></td>
                    <td><?= htmlspecialchars($app['biography']) ?></td>
                    <td><?= $app['agreement'] ? 'Да' : 'Нет' ?></td>
                    <td>
                        <a href="edit.php?id=<?= $app['id'] ?>" class="action-btn edit-btn">Редактировать</a>
                        <a href="admin.php?delete=<?= $app['id'] ?>" class="action-btn delete-btn" onclick="return confirm('Вы уверены?')">Удалить</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <div class="stats">
        <h2>Статистика по языкам программирования</h2>
        <table>
            <thead>
                <tr>
                    <th>Язык программирования</th>
                    <th>Количество пользователей</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($stats as $stat): ?>
                    <tr>
                        <td><?= htmlspecialchars($stat['name']) ?></td>
                        <td><?= $stat['count'] ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
