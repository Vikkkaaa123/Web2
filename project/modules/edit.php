<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../scripts/db.php';

checkAdminAuth();
$db = connectDB();

// Получаем ID заявки из запроса
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Если форма отправлена
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $db->prepare("UPDATE applications SET full_name = ?, email = ?, phone = ?, birth_date = ?, gender = ?, biography = ?, agreement = ? WHERE id = ?");
    $stmt->execute([
        $_POST['full_name'],
        $_POST['email'],
        $_POST['phone'],
        $_POST['birth_date'],
        $_POST['gender'],
        $_POST['biography'],
        isset($_POST['agreement']) ? 1 : 0,
        $id
    ]);

    // Обновим языки
    $db->prepare("DELETE FROM application_languages WHERE application_id = ?")->execute([$id]);
    if (!empty($_POST['languages']) && is_array($_POST['languages'])) {
        $stmt = $db->prepare("INSERT INTO application_languages (application_id, language_id) VALUES (?, ?)");
        foreach ($_POST['languages'] as $lang_id) {
            $stmt->execute([$id, $lang_id]);
        }
    }

    header("Location: admin.php");
    exit();
}

// Получение данных заявки
$stmt = $db->prepare("SELECT * FROM applications WHERE id = ?");
$stmt->execute([$id]);
$app = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$app) {
    echo "Заявка не найдена.";
    exit();
}

// Получение всех языков
$languages = $db->query("SELECT * FROM programming_languages")->fetchAll(PDO::FETCH_ASSOC);

// Получение выбранных языков
$stmt = $db->prepare("SELECT language_id FROM application_languages WHERE application_id = ?");
$stmt->execute([$id]);
$selectedLanguages = $stmt->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Редактирование заявки</title>
    <link rel="stylesheet" href="./styles/table.css">
</head>
<body>
    <div class="admin-container">
        <h1>Редактировать заявку #<?= $id ?></h1>
        <form method="POST">
            <label>ФИО: <input type="text" name="full_name" value="<?= htmlspecialchars($app['full_name']) ?>" required></label><br>
            <label>Email: <input type="email" name="email" value="<?= htmlspecialchars($app['email']) ?>" required></label><br>
            <label>Телефон: <input type="text" name="phone" value="<?= htmlspecialchars($app['phone']) ?>" required></label><br>
            <label>Дата рождения: <input type="date" name="birth_date" value="<?= htmlspecialchars($app['birth_date']) ?>" required></label><br>
            <label>Пол:
                <select name="gender" required>
                    <option value="male" <?= $app['gender'] === 'male' ? 'selected' : '' ?>>Мужской</option>
                    <option value="female" <?= $app['gender'] === 'female' ? 'selected' : '' ?>>Женский</option>
                </select>
            </label><br>
            <label>Биография:<br>
                <textarea name="biography" rows="4"><?= htmlspecialchars($app['biography']) ?></textarea>
            </label><br>
            <label>Языки программирования:<br>
                <?php foreach ($languages as $lang): ?>
                    <label>
                        <input type="checkbox" name="languages[]" value="<?= $lang['id'] ?>"
                            <?= in_array($lang['id'], $selectedLanguages) ? 'checked' : '' ?>>
                        <?= htmlspecialchars($lang['name']) ?>
                    </label><br>
                <?php endforeach; ?>
            </label>
            <label>
                <input type="checkbox" name="agreement" value="1" <?= $app['agreement'] ? 'checked' : '' ?>>
                Согласие на обработку данных
            </label><br><br>
            <button type="submit">Сохранить</button>
            <a href="admin.php">Отмена</a>
        </form>
    </div>
</body>
</html>
