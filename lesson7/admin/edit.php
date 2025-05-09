<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../db.php';

checkAdminAuth();

// Генерация CSRF-токена
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$db = connectDB();
$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

$appId = (int)$_GET['id'];

// Получаем данные
$allLangs = $db->query("SELECT * FROM programming_languages")->fetchAll();

$stmt = $db->prepare("SELECT * FROM applications WHERE id = ?");
$stmt->execute([$appId]);
$app = $stmt->fetch();

$stmt = $db->prepare("SELECT language_id FROM application_languages WHERE application_id = ?");
$stmt->execute([$appId]);
$selectedLangs = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Обработка формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('Неверный CSRF-токен');
    }

    try {
        // Обновление данных
        $stmt = $db->prepare("UPDATE applications SET full_name=?, email=?, phone=?, gender=?, biography=? WHERE id=?");
        $stmt->execute([
            $_POST['full_name'],
            $_POST['email'],
            $_POST['phone'],
            $_POST['gender'],
            $_POST['biography'],
            $appId
        ]);
        
        // Обновление языков
        $db->beginTransaction();
        $stmt = $db->prepare("DELETE FROM application_languages WHERE application_id = ?");
        $stmt->execute([$appId]);
        
        if (!empty($_POST['languages'])) {
            $stmt = $db->prepare("INSERT INTO application_languages (application_id, language_id) VALUES (?, ?)");
            foreach ($_POST['languages'] as $langId) {
                $stmt->execute([$appId, (int)$langId]);
            }
        }
        $db->commit();
        
        header('Location: admin.php');
        exit();
    } catch (PDOException $e) {
        $db->rollBack();
        error_log("Update error: " . $e->getMessage());
        die('Ошибка обновления данных');
    }
}
?>
    
<!DOCTYPE html>
<html>
<head>
    <title>Редактирование заявки</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>
    <div class="admin-container">
        <h1>Редактирование заявки #<?= $appId ?></h1>
        <form method="POST" class="edit-form">
            <div class="form-group">
                <label>ФИО:</label>
                <input type="text" name="full_name" value="<?= htmlspecialchars($app['full_name']) ?>" required>
            </div>
            
            <div class="form-group">
                <label>Email:</label>
                <input type="email" name="email" value="<?= htmlspecialchars($app['email']) ?>" required>
            </div>
            
            <div class="form-group">
                <label>Телефон:</label>
                <input type="text" name="phone" value="<?= htmlspecialchars($app['phone']) ?>">
            </div>
            
            <div class="form-group">
                <label>Пол:</label>
                <select name="gender">
                    <option value="male" <?= $app['gender'] == 'male' ? 'selected' : '' ?>>Мужской</option>
                    <option value="female" <?= $app['gender'] == 'female' ? 'selected' : '' ?>>Женский</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Биография:</label>
                <textarea name="biography"><?= htmlspecialchars($app['biography']) ?></textarea>
            </div>
            
            <div class="form-group">
                <label>Языки программирования:</label>
                <select name="languages[]" multiple class="multiselect">
                    <?php foreach ($allLangs as $lang): ?>
                    <option value="<?= $lang['id'] ?>" 
                        <?= in_array($lang['id'], $selectedLangs) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($lang['name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="action-buttons">
                <button type="submit" class="button">Сохранить</button>
                <a href="admin.php" class="button">Отмена</a>
            </div>
        </form>
    </div>
</body>
</html>
