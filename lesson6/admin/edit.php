<?php
require_once 'db.php';
require_once 'auth.php';

checkAdminAuth();

$appId = (int)($_GET['id'] ?? 0);
if (!$appId) die('Не указан ID заявки');

// Получение данных заявки
$stmt = $db->prepare("SELECT * FROM applications WHERE id = ?");
$stmt->execute([$appId]);
$application = $stmt->fetch();

if (!$application) die('Заявка не найдена');

// Получение выбранных языков
$stmt = $db->prepare("SELECT language_id FROM application_languages WHERE application_id = ?");
$stmt->execute([$appId]);
$selectedLangs = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Получение списка языков
$allowed_lang = $db->query("SELECT id, name FROM programming_languages")->fetchAll(PDO::FETCH_KEY_PAIR);

// Обработка формы
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Валидация данных
    $errors = [];
    
    $full_name = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $day = trim($_POST['birth_day'] ?? '');
    $month = trim($_POST['birth_month'] ?? '');
    $year = trim($_POST['birth_year'] ?? '');
    $gender = $_POST['gender'] ?? '';
    $biography = trim($_POST['biography'] ?? '');
    $languages = $_POST['languages'] ?? [];
    $agreement = isset($_POST['agreement']) ? 1 : 0;
    
    if (empty($full_name)) $errors['full_name'] = 'Имя не указано';
    if (empty($phone)) $errors['phone'] = 'Телефон не указан';
    if (empty($email)) $errors['email'] = 'Email не указан';
    if (!checkdate($month, $day, $year)) $errors['birth_date'] = 'Некорректная дата';
    if (!in_array($gender, ['male', 'female'])) $errors['gender'] = 'Некорректный пол';
    
    if (empty($errors)) {
        $birth_date = sprintf("%04d-%02d-%02d", $year, $month, $day);
        
        try {
            $db->beginTransaction();
            
            // Обновление данных заявки
            $stmt = $db->prepare("
                UPDATE applications SET 
                full_name = ?, 
                phone = ?, 
                email = ?, 
                birth_date = ?, 
                gender = ?, 
                biography = ?, 
                agreement = ? 
                WHERE id = ?
            ");
            $stmt->execute([
                $full_name, 
                $phone, 
                $email, 
                $birth_date, 
                $gender, 
                $biography, 
                $agreement,
                $appId
            ]);
            
            // Обновление языков
            $db->prepare("DELETE FROM application_languages WHERE application_id = ?")->execute([$appId]);
            
            if (!empty($languages)) {
                $stmt = $db->prepare("INSERT INTO application_languages (application_id, language_id) VALUES (?, ?)");
                foreach ($languages as $langId) {
                    if (isset($allowed_lang[$langId])) {
                        $stmt->execute([$appId, $langId]);
                    }
                }
            }
            
            $db->commit();
            header('Location: admin.php');
            exit;
        } catch (PDOException $e) {
            $db->rollBack();
            die('Ошибка обновления: ' . $e->getMessage());
        }
    }
}

// Подготовка данных для формы
$birthDate = explode('-', $application['birth_date']);
$values = [
    'full_name' => $application['full_name'],
    'phone' => $application['phone'],
    'email' => $application['email'],
    'birth_day' => $birthDate[2],
    'birth_month' => $birthDate[1],
    'birth_year' => $birthDate[0],
    'gender' => $application['gender'],
    'biography' => $application['biography'],
    'languages' => $selectedLangs,
    'agreement' => $application['agreement'],
    'errors' => $errors ?? []
];
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
        
        <form method="POST">
            <div class="form-group">
                <label for="full_name">ФИО:</label>
                <input type="text" id="full_name" name="full_name" 
                       value="<?= htmlspecialchars($values['full_name']) ?>" required>
                <?php if (!empty($values['errors']['full_name'])): ?>
                    <div class="error-message"><?= $values['errors']['full_name'] ?></div>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <label for="phone">Телефон:</label>
                <input type="tel" id="phone" name="phone" 
                       value="<?= htmlspecialchars($values['phone']) ?>" required>
                <?php if (!empty($values['errors']['phone'])): ?>
                    <div class="error-message"><?= $values['errors']['phone'] ?></div>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" 
                       value="<?= htmlspecialchars($values['email']) ?>" required>
                <?php if (!empty($values['errors']['email'])): ?>
                    <div class="error-message"><?= $values['errors']['email'] ?></div>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <label>Дата рождения:</label>
                <div class="date-fields">
                    <input type="number" name="birth_day" placeholder="День" min="1" max="31" 
                           value="<?= htmlspecialchars($values['birth_day']) ?>" required>
                    <input type="number" name="birth_month" placeholder="Месяц" min="1" max="12" 
                           value="<?= htmlspecialchars($values['birth_month']) ?>" required>
                    <input type="number" name="birth_year" placeholder="Год" min="1900" max="<?= date('Y') ?>" 
                           value="<?= htmlspecialchars($values['birth_year']) ?>" required>
                </div>
                <?php if (!empty($values['errors']['birth_date'])): ?>
                    <div class="error-message"><?= $values['errors']['birth_date'] ?></div>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <label>Пол:</label>
                <div class="gender-options">
                    <label>
                        <input type="radio" name="gender" value="male" 
                               <?= $values['gender'] == 'male' ? 'checked' : '' ?> required> Мужской
                    </label>
                    <label>
                        <input type="radio" name="gender" value="female" 
                               <?= $values['gender'] == 'female' ? 'checked' : '' ?>> Женский
                    </label>
                </div>
                <?php if (!empty($values['errors']['gender'])): ?>
                    <div class="error-message"><?= $values['errors']['gender'] ?></div>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <label for="languages">Любимые языки программирования:</label>
                <select id="languages" name="languages[]" multiple size="<?= min(5, count($allowed_lang)) ?>">
                    <?php foreach ($allowed_lang as $id => $name): ?>
                        <option value="<?= $id ?>" <?= in_array($id, $values['languages']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($name) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="biography">Биография:</label>
                <textarea id="biography" name="biography"><?= htmlspecialchars($values['biography']) ?></textarea>
            </div>
            
            <div class="form-group agreement-field">
                <label>
                    <input type="checkbox" name="agreement" <?= $values['agreement'] ? 'checked' : '' ?>>
                    Согласен(а) с обработкой персональных данных
                </label>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="button edit">Сохранить</button>
                <a href="admin.php" class="button">Отмена</a>
            </div>
        </form>
    </div>
</body>
</html>
