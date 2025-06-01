<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Редактирование заявки</title>
    <link rel="stylesheet" href="../styles/style.css">
</head>
<body>
    <div class="bottom-wrapper">
        <div class="b-form formstyle1">
            <h1 class="white-text">Редактирование заявки #<?= htmlspecialchars($appId) ?></h1>
            <form method="POST" class="form">
                <div class="form-group">
                    <label class="b-form label">ФИО:</label>
                    <input type="text" name="full_name" class="input-field" value="<?= htmlspecialchars($app['full_name']) ?>" required>
                </div>

                <div class="form-group">
                    <label class="b-form label">Email:</label>
                    <input type="email" name="email" class="input-field" value="<?= htmlspecialchars($app['email']) ?>" required>
                </div>

                <div class="form-group">
                    <label class="b-form label">Телефон:</label>
                    <input type="text" name="phone" class="input-field" value="<?= htmlspecialchars($app['phone']) ?>">
                </div>

                <div class="form-group">
                    <label class="b-form label">Пол:</label>
                    <div class="gender-options">
                        <label>
                            <input type="radio" name="gender" value="male" <?= $app['gender'] === 'male' ? 'checked' : '' ?>>
                            Мужской
                        </label>
                        <label>
                            <input type="radio" name="gender" value="female" <?= $app['gender'] === 'female' ? 'checked' : '' ?>>
                            Женский
                        </label>
                    </div>
                </div>

                <div class="form-group">
                    <label class="b-form label">Языки программирования:</label>
                    <select name="languages[]" multiple class="input-field">
                        <?php foreach ($allLangs as $lang): ?>
                            <option value="<?= $lang['id'] ?>" <?= in_array($lang['id'], $selectedLangs) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($lang['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="b-form label">Биография:</label>
                    <textarea name="biography" class="input-field"><?= htmlspecialchars($app['biography']) ?></textarea>
                </div>

                <div class="form-group checkbox-block">
                    <label class="form-checkbox">
                        <input type="checkbox" name="agreement" <?= $app['agreement'] ? 'checked' : '' ?>>
                        <span class="checkmark"></span>
                        Согласен с условиями
                    </label>
                </div>

                <div class="action-buttons">
                    <button type="submit" class="submit-btn">Сохранить</button>
                    <a href="../modules/admin.php" class="submit-btn" style="background-color: rgba(255, 255, 255, 0.1); border: 1px solid rgba(255, 255, 255, 0.3); margin-left: 10px;">Отмена</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
