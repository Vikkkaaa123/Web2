<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title>Форма</title>
</head>
<body>
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

    <form action="index.php" method="POST">
        <h1>Форма</h1>

        <div class="form-group">
            <label for="full_name">ФИО:</label>
            <input type="text" id="full_name" name="full_name" placeholder="Введите Ваше фамилию, имя, отчество" required maxlength="128" value="<?php echo htmlspecialchars($values['full_name']); ?>" <?php if ($errors['full_name']) echo 'class="error"'; ?>>
            <?php if (!empty($messages['full_name'])) echo '<div class="error-message">' . $messages['full_name'] . '</div>'; ?>
        </div>

        <div class="form-group">
            <label for="phone">Телефон:</label>
            <input type="tel" id="phone" name="phone" placeholder="+7XXXXXXXXXX" required value="<?php echo htmlspecialchars($values['phone']); ?>" <?php if ($errors['phone']) echo 'class="error"'; ?>>
            <?php if (!empty($messages['phone'])) echo '<div class="error-message">' . $messages['phone'] . '</div>'; ?>
        </div>

        <div class="form-group">
            <label for="email">E-mail:</label>
            <input type="email" id="email" name="email" placeholder="Введите Вашу почту" required value="<?php echo htmlspecialchars($values['email']); ?>" <?php if ($errors['email']) echo 'class="error"'; ?>>
            <?php if (!empty($messages['email'])) echo '<div class="error-message">' . $messages['email'] . '</div>'; ?>
        </div>

        <div class="form-group">
            <label>Дата рождения:</label>
            <div class="date-fields">
                <input type="number" id="birth_day" name="birth_day" placeholder="День" min="1" max="31" required value="<?php echo htmlspecialchars($values['birth_day']); ?>" <?php if ($errors['birth_day']) echo 'class="error"'; ?>>
                <input type="number" id="birth_month" name="birth_month" placeholder="Месяц" min="1" max="12" required value="<?php echo htmlspecialchars($values['birth_month']); ?>" <?php if ($errors['birth_month']) echo 'class="error"'; ?>>
                <input type="number" id="birth_year" name="birth_year" placeholder="Год" min="1900" max="<?php echo date('Y'); ?>" required value="<?php echo htmlspecialchars($values['birth_year']); ?>" <?php if ($errors['birth_year']) echo 'class="error"'; ?>>
            </div>
            <?php if (!empty($messages['birth_date'])) echo '<div class="error-message">' . $messages['birth_date'] . '</div>'; ?>
        </div>

        <div class="form-group">
            <label>Пол:</label>
            <div class="gender-options">
                <label>
                    <input type="radio" name="gender" value="male" required <?php if ($values['gender'] === 'male') echo 'checked'; ?> <?php if ($errors['gender']) echo 'class="error"'; ?>> Мужской
                </label>
                <label>
                    <input type="radio" name="gender" value="female" required <?php if ($values['gender'] === 'female') echo 'checked'; ?> <?php if ($errors['gender']) echo 'class="error"'; ?>> Женский
                </label>
            </div>
            <?php if (!empty($messages['gender'])) echo '<div class="error-message">' . $messages['gender'] . '</div>'; ?>
        </div>

        <div class="form-group">
            <label for="languages">Любимый язык программирования:</label>
            <select id="languages" name="languages[]" multiple required <?php if ($errors['languages']) echo 'class="error"'; ?>>
                <?php foreach ($allowed_lang as $id => $name): ?>
                    <option value="<?php echo $id; ?>" <?php if (in_array($id, explode(',', $values['languages']))) echo 'selected'; ?>><?php echo $name; ?></option>
                <?php endforeach; ?>
            </select>
            <?php if (!empty($messages['languages'])) echo '<div class="error-message">' . $messages['languages'] . '</div>'; ?>
        </div>

        <div class="form-group">
            <label for="biography">Биография:</label>
            <textarea id="biography" name="biography" placeholder="Расскажите о себе" required <?php if ($errors['biography']) echo 'class="error"'; ?>><?php echo htmlspecialchars($values['biography']); ?></textarea>
            <?php if (!empty($messages['biography'])) echo '<div class="error-message">' . $messages['biography'] . '</div>'; ?>
        </div>

        <div class="form-group agreement-field">
            <label>
                <input type="checkbox" name="agreement" required <?php if ($values['agreement']) echo 'checked'; ?> <?php if ($errors['agreement']) echo 'class="error"'; ?>> С контрактом ознакомлен(а)
            </label>
            <?php if (!empty($messages['agreement'])) echo '<div class="error-message">' . $messages['agreement'] . '</div>'; ?>
        </div>

        <div class="form-actions">
            <input type="submit" value="Сохранить">
            <input type="button" value="Войти" onclick="location.href='login.php'">
            <?php if (!empty($_SESSION['login'])): ?>
                <input type="button" value="Выйти" onclick="location.href='logout.php'">
            <?php endif; ?>
        </div>
    </form>
</body>
</html>
