<?php
require 'db.php';
require 'auth.php';

checkAdminAuth();

$appId = (int)$_GET['id'];

// Получаем данные заявки
$stmt = $db->prepare("SELECT * FROM applications WHERE id = ?");
$stmt->execute([$appId]);
$application = $stmt->fetch();

if (!$application) {
    header('HTTP/1.1 404 Not Found');
    exit('Заявка не найдена');
}

// Получаем выбранные языки
$stmt = $db->prepare("SELECT language_id FROM application_languages WHERE application_id = ?");
$stmt->execute([$appId]);
$selectedLangs = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Получаем все доступные языки
$allLangs = $db->query("SELECT id, name FROM programming_languages")->fetchAll();

// Обработка формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Валидация данных
    $errors = [];
    
    $full_name = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $birth_date = $_POST['birth_date'] ?? '';
    $gender = $_POST['gender'] ?? '';
    $biography = trim($_POST['biography'] ?? '');
    $languages = $_POST['languages'] ?? [];
    $agreement = isset($_POST['agreement']) ? 1 : 0;

    // Простая валидация (можно расширить)
    if (empty($full_name)) $errors['full_name'] = 'Укажите ФИО';
    if (empty($phone)) $errors['phone'] = 'Укажите телефон';
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Некорректный email';
    if (empty($birth_date)) $errors['birth_date'] = 'Укажите дату рождения';
    if (!in_array($gender, ['male', 'female'])) $errors['gender'] = 'Выберите пол';
    if (empty($languages)) $errors['languages'] = 'Выберите хотя бы один язык';

    if (empty($errors)) {
        try {
            $db->beginTransaction();

            // Обновляем основную информацию
            $stmt = $db->prepare("
                UPDATE applications SET 
                full_name = :full_name,
                phone = :phone,
                email = :email,
                birth_date = :birth_date,
                gender = :gender,
                biography = :biography,
                agreement = :agreement
                WHERE id = :id
            ");
            
            $stmt->execute([
                'full_name' => $full_name,
                'phone' => $phone,
                'email' => $email,
                'birth_date' => $birth_date,
                'gender' => $gender,
                'biography' => $biography,
                'agreement' => $agreement,
                'id' => $appId
            ]);

            // Обновляем языки программирования
            $db->prepare("DELETE FROM application_languages WHERE application_id = ?")->execute([$appId]);
            
            $stmt = $db->prepare("INSERT INTO application_languages (application_id, language_id) VALUES (?, ?)");
            foreach ($languages as $langId) {
                $langId = (int)$langId;
                if ($langId > 0) {
                    $stmt->execute([$appId, $langId]);
                }
            }

            $db->commit();
            
            header('Location: admin.php');
            exit;
            
        } catch (PDOException $e) {
            $db->rollBack();
            $errors[] = 'Ошибка сохранения: ' . $e->getMessage();
        }
    }
}

// Разбиваем дату рождения для формы
$birthDate = explode('-', $application['birth_date']);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Редактирование заявки #<?= $appId ?></title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .edit-form { max-width: 700px; margin: 20px auto; }
        .form-group { margin-bottom: 15px; }
        .error { color: red; font-size: 14px; }
        .date-fields { display: flex; gap: 10px; }
        .date-fields input { flex: 1; }
        .actions { margin-top: 20px; display: flex; gap: 10px; }
        .btn { padding: 8px 15px; text-decoration: none; border-radius: 4px; }
        .btn-save { background: #4CAF50; color: white; border: none; cursor: pointer; }
        .btn-cancel { background: #f1f1f1; color: #333; }
    </style>
</head>
<body>
    <div class="edit-form">
        <h1>Редактирование заявки #<?= $appId ?></h1>
        
        <?php if (!empty($errors)): ?>
            <div class="error-message">
                <?php foreach ($errors as $error): ?>
                    <p><?= htmlspecialchars($error) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="full_name">ФИО:</label>
                <input type="text" id="full_name" name="full_name" 
                       value="<?= htmlspecialchars($application['full_name']) ?>" required>
            </div>
            
            <div class="form-group">
                <label for="phone">Телефон:</label>
                <input type="tel" id="phone" name="phone" 
                       value="<?= htmlspecialchars($application['phone']) ?>" required>
            </div>
            
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" 
                       value="<?= htmlspecialchars($application['email']) ?>" required>
            </div>
            
            <div class="form-group">
                <label>Дата рождения:</label>
                <input type="date" name="birth_date" 
                       value="<?= htmlspecialchars($application['birth_date']) ?>" required>
            </div>
            
            <div class="form-group">
                <label>Пол:</label>
                <div class="gender-options">
                    <label>
                        <input type="radio" name="gender" value="male" 
                               <?= $application['gender'] === 'male' ? 'checked' : '' ?> required> Мужской
                    </label>
                    <label>
                        <input type="radio" name="gender" value="female" 
                               <?= $application['gender'] === 'female' ? 'checked' : '' ?>> Женский
                    </label>
                </div>
            </div>
            
            <div class="form-group">
                <label>Языки программирования:</label>
                <div class="languages-list">
                    <?php foreach ($allLangs as $lang): ?>
                        <label style="display: block; margin: 5px 0;">
                            <input type="checkbox" name="languages[]" value="<?= $lang['id'] ?>"
                                   <?= in_array($lang['id'], $selectedLangs) ? 'checked' : '' ?>>
                            <?= htmlspecialchars($lang['name']) ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="form-group">
                <label for="biography">Биография:</label>
                <textarea id="biography" name="biography"><?= htmlspecialchars($application['biography']) ?></textarea>
            </div>
            
            <div class="form-group agreement-field">
                <label>
                    <input type="checkbox" name="agreement" 
                           <?= $application['agreement'] ? 'checked' : '' ?>>
                    Согласен(а) с обработкой персональных данных
                </label>
            </div>
            
            <div class="actions">
                <button type="submit" class="btn btn-save">Сохранить</button>
                <a href="admin.php" class="btn btn-cancel">Отмена</a>
            </div>
        </form>
    </div>
</body>
</html>
