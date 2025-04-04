<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title>Задание 5</title>
</head>
<body>
    <!-- Кнопки авторизации в правом верхнем углу -->
    <div class="auth-buttons">
        <?php if (!empty($_SESSION['login'])): ?>
            <input type="button" value="Выйти" onclick="location.href='logout.php'" class="auth-btn">
        <?php else: ?>
            <input type="button" value="Войти" onclick="location.href='login.php'" class="auth-btn">
        <?php endif; ?>
    </div>

    <!-- Блок сгенерированных учетных данных (показывается один раз после регистрации) -->
    <?php if (!empty($_SESSION['generated_login']) && !empty($_SESSION['generated_password']) && empty($_SESSION['login'])): ?>
        <div class="credentials">
            <h3>Ваши учетные данные:</h3>
            <p><strong>Логин:</strong> <?php echo htmlspecialchars($_SESSION['generated_login']); ?></p>
            <p><strong>Пароль:</strong> <?php echo htmlspecialchars($_SESSION['generated_password']); ?></p>
            <p>Используйте их для входа в следующий раз.</p>
        </div>
        <?php 
            unset($_SESSION['generated_login']);
            unset($_SESSION['generated_password']);
        ?>
    <?php endif; ?>

    <!-- Основная форма -->
    <form action="index.php" method="POST">
        <h1>Форма регистрации</h1>

        <!-- Поле ФИО -->
        <div class="form-group">
            <label for="full_name">ФИО:</label>
            <input type="text" id="full_name" name="full_name" 
                   placeholder="Введите ваше полное имя" 
                   required maxlength="128"
                   value="<?php echo htmlspecialchars($values['full_name'] ?? ''); ?>"
                   <?php if (!empty($errors['full_name'])) echo 'class="error"'; ?>>
            <?php if (!empty($messages['full_name'])): ?>
                <div class="error-message"><?php echo $messages['full_name']; ?></div>
            <?php endif; ?>
        </div>

        <!-- Поле Телефон -->
        <div class="form-group">
            <label for="phone">Телефон:</label>
            <input type="tel" id="phone" name="phone" 
                   placeholder="+7XXXXXXXXXX" 
                   required
                   value="<?php echo htmlspecialchars($values['phone'] ?? ''); ?>"
                   <?php if (!empty($errors['phone'])) echo 'class="error"'; ?>>
            <?php if (!empty($messages['phone'])): ?>
                <div class="error-message"><?php echo $messages['phone']; ?></div>
            <?php endif; ?>
        </div>

        <!-- Поле Email -->
        <div class="form-group">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" 
                   placeholder="example@domain.com" 
                   required
                   value="<?php echo htmlspecialchars($values['email'] ?? ''); ?>"
                   <?php if (!empty($errors['email'])) echo 'class="error"'; ?>>
            <?php if (!empty($messages['email'])): ?>
                <div class="error-message"><?php echo $messages['email']; ?></div>
            <?php endif; ?>
        </div>

        <!-- Поле Дата рождения -->
        <div class="form-group">
            <label>Дата рождения:</label>
            <div class="date-fields">
                <input type="number" id="birth_day" name="birth_day" 
                       placeholder="День" min="1" max="31" 
                       required
                       value="<?php echo htmlspecialchars($values['birth_day'] ?? ''); ?>"
                       <?php if (!empty($errors['birth_day'])) echo 'class="error"'; ?>>
                <input type="number" id="birth_month" name="birth_month" 
                       placeholder="Месяц" min="1" max="12" 
                       required
                       value="<?php echo htmlspecialchars($values['birth_month'] ?? ''); ?>"
                       <?php if (!empty($errors['birth_month'])) echo 'class="error"'; ?>>
                <input type="number" id="birth_year" name="birth_year" 
                       placeholder="Год" min="1900" max="<?php echo date('Y'); ?>" 
                       required
                       value="<?php echo htmlspecialchars($values['birth_year'] ?? ''); ?>"
                       <?php if (!empty($errors['birth_year'])) echo 'class="error"'; ?>>
            </div>
            <?php if (!empty($messages['birth_date'])): ?>
                <div class="error-message"><?php echo $messages['birth_date']; ?></div>
            <?php endif; ?>
        </div>

        <!-- Поле Пол -->
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
                <div class="error-message"><?php echo $messages['gender']; ?></div>
            <?php endif; ?>
        </div>

        <!-- Поле Языки программирования -->
        <div class="form-group">
            <label for="languages">Любимые языки программирования:</label>
            <select id="languages" name="languages[]" multiple 
                    <?php if (!empty($errors['languages'])) echo 'class="error"'; ?>>
                <?php foreach ($allowed_lang as $id => $name): ?>
                    <option value="<?php echo $id; ?>"
                        <?php if (in_array($id, explode(',', $values['languages'] ?? ''))) echo 'selected'; ?>>
                        <?php echo $name; ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (!empty($messages['languages'])): ?>
                <div class="error-message"><?php echo $messages['languages']; ?></div>
            <?php endif; ?>
        </div>

        <!-- Поле Биография -->
        <div class="form-group">
            <label for="biography">Биография:</label>
            <textarea id="biography" name="biography" 
                      placeholder="Расскажите о себе..."
                      <?php if (!empty($errors['biography'])) echo 'class="error"'; ?>><?php 
                echo htmlspecialchars($values['biography'] ?? ''); 
            ?></textarea>
            <?php if (!empty($messages['biography'])): ?>
                <div class="error-message"><?php echo $messages['biography']; ?></div>
            <?php endif; ?>
        </div>

        <!-- Поле Согласие -->
        <div class="form-group agreement-field">
            <label>
                <input type="checkbox" name="agreement" 
                       <?php if (!empty($values['agreement'])) echo 'checked'; ?>
                       <?php if (!empty($errors['agreement'])) echo 'class="error"'; ?>>
                Согласен(а) с обработкой персональных данных
            </label>
            <?php if (!empty($messages['agreement'])): ?>
                <div class="error-message"><?php echo $messages['agreement']; ?></div>
            <?php endif; ?>
        </div>

        <!-- Кнопка отправки формы -->
        <div class="form-actions">
            <input type="submit" value="<?php 
                echo !empty($_SESSION['login']) ? 'Обновить данные' : 'Зарегистрироваться'; 
            ?>">
        </div>
    </form>
</body>
</html>
