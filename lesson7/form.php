<?php
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Security-Policy" content="default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data:">
    <title>Форма заявки</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="auth-buttons">
        <?php if (!empty($_SESSION['login'])): ?>
            <input type="button" value="Выйти" onclick="location.href='logout.php'" class="auth-btn">
        <?php else: ?>
            <input type="button" value="Войти" onclick="location.href='login.php'" class="auth-btn">
        <?php endif; ?>
    </div>

    <?php if (!empty($_SESSION['generated_login']) && !empty($_SESSION['generated_password']) && empty($_SESSION['login'])): ?>
        <div class="credentials">
            <h3>Ваши учетные данные:</h3>
            <p><strong>Логин:</strong> <?php echo htmlspecialchars($_SESSION['generated_login'], ENT_QUOTES, 'UTF-8'); ?></p>
            <p><strong>Пароль:</strong> <?php echo htmlspecialchars($_SESSION['generated_password'], ENT_QUOTES, 'UTF-8'); ?></p>
            <p>Используйте их для входа в следующий раз.</p>
        </div>
        <?php 
            unset($_SESSION['generated_login']);
            unset($_SESSION['generated_password']);
        ?>
    <?php endif; ?>

    <form action="index.php" method="POST" id="applicationForm">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
        <h1>Форма заявки</h1>

        <div class="form-group">
            <label for="full_name">ФИО:</label>
            <input type="text" id="full_name" name="full_name" 
                   placeholder="Введите ваше полное имя" 
                   required maxlength="128"
                   value="<?php echo htmlspecialchars($values['full_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                   <?php if (!empty($errors['full_name'])) echo 'class="error"'; ?>>
            <?php if (!empty($messages['full_name'])): ?>
                <div class="error-message"><?php echo htmlspecialchars($messages['full_name'], ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="phone">Телефон:</label>
            <input type="tel" id="phone" name="phone" 
                   placeholder="+7XXXXXXXXXX" 
                   required pattern="\+7\d{10}"
                   value="<?php echo htmlspecialchars($values['phone'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                   <?php if (!empty($errors['phone'])) echo 'class="error"'; ?>>
            <?php if (!empty($messages['phone'])): ?>
                <div class="error-message"><?php echo htmlspecialchars($messages['phone'], ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" 
                   placeholder="example@mail.com" 
                   required
                   value="<?php echo htmlspecialchars($values['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                   <?php if (!empty($errors['email'])) echo 'class="error"'; ?>>
            <?php if (!empty($messages['email'])): ?>
                <div class="error-message"><?php echo htmlspecialchars($messages['email'], ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label>Дата рождения:</label>
            <div class="date-fields">
                <input type="number" id="birth_day" name="birth_day" 
                       placeholder="День" min="1" max="31" 
                       required
                       value="<?php echo htmlspecialchars($values['birth_day'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                       <?php if (!empty($errors['birth_day'])) echo 'class="error"'; ?>>
                <input type="number" id="birth_month" name="birth_month" 
                       placeholder="Месяц" min="1" max="12" 
                       required
                       value="<?php echo htmlspecialchars($values['birth_month'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                       <?php if (!empty($errors['birth_month'])) echo 'class="error"'; ?>>
                <input type="number" id="birth_year" name="birth_year" 
                       placeholder="Год" min="1900" max="<?php echo date('Y'); ?>" 
                       required
                       value="<?php echo htmlspecialchars($values['birth_year'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                       <?php if (!empty($errors['birth_year'])) echo 'class="error"'; ?>>
            </div>
            <?php if (!empty($messages['birth_date'])): ?>
                <div class="error-message"><?php echo htmlspecialchars($messages['birth_date'], ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label>Пол:</label>
            <div class="gender-options">
                <label>
                    <input type="radio" name="gender" value="male" 
                           <?php if (($values['gender'] ?? '') === 'male') echo 'checked'; ?>
                           <?php if (!empty($errors['gender'])) echo 'class="error"'; ?>>
                    Мужской
                </label>
                <label>
                    <input type="radio" name="gender" value="female" 
                           <?php if (($values['gender'] ?? '') === 'female') echo 'checked'; ?>
                           <?php if (!empty($errors['gender'])) echo 'class="error"'; ?>>
                    Женский
                </label>
            </div>
            <?php if (!empty($messages['gender'])): ?>
                <div class="error-message"><?php echo htmlspecialchars($messages['gender'], ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="languages">Любимые языки программирования:</label>
            <select id="languages" name="languages[]" multiple 
                    <?php if (!empty($errors['languages'])) echo 'class="error"'; ?>>
                <?php foreach ($allowed_lang as $id => $name): ?>
                    <option value="<?php echo (int)$id; ?>"
                        <?php if (in_array($id, explode(',', $values['languages'] ?? ''))) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (!empty($messages['languages'])): ?>
                <div class="error-message"><?php echo htmlspecialchars($messages['languages'], ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="biography">Биография:</label>
            <textarea id="biography" name="biography" 
                      placeholder="Расскажите о себе..." maxlength="512"
                      <?php if (!empty($errors['biography'])) echo 'class="error"'; ?>><?php 
                echo htmlspecialchars($values['biography'] ?? '', ENT_QUOTES, 'UTF-8'); 
            ?></textarea>
            <?php if (!empty($messages['biography'])): ?>
                <div class="error-message"><?php echo htmlspecialchars($messages['biography'], ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>
        </div>

        <div class="form-group agreement-field">
            <label>
                <input type="checkbox" name="agreement" 
                       <?php if (!empty($values['agreement'])) echo 'checked'; ?>
                       <?php if (!empty($errors['agreement'])) echo 'class="error"'; ?>>
                Согласен(а) с обработкой персональных данных
            </label>
            <?php if (!empty($messages['agreement'])): ?>
                <div class="error-message"><?php echo htmlspecialchars($messages['agreement'], ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>
        </div>

        <div class="form-actions">
            <input type="submit" value="<?php 
                echo !empty($_SESSION['login']) ? 'Обновить данные' : 'Сохранить'; 
            ?>">
        </div>
    </form>

    <script>
        // CSRF защита для AJAX запросов
        document.getElementById('applicationForm').addEventListener('submit', function(e) {
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'index.php', true);
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.setRequestHeader('X-CSRF-Token', '<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>');
            
            xhr.onload = function() {
                if (xhr.status === 200) {
                    window.location.reload();
                }
            };
            
            xhr.send(new FormData(this));
            e.preventDefault();
        });
    </script>
</body>
</html>
