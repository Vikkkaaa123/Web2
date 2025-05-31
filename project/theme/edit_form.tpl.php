<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Редактирование заявки #<?= $appId ?></title>
    <link rel="stylesheet" href="../styles/style.css">
    <link rel="stylesheet" href="../styles/table.css">
</head>
<body>
<div class="container">
    <h1>Редактирование заявки #<?= $appId ?></h1>
    <form method="post" action="edit.php?id=<?= $appId ?>" class="form">

        <div class="form-group">
            <label for="full_name">ФИО:</label>
            <input type="text" id="full_name" name="full_name" value="<?= htmlspecialchars($app['full_name']) ?>" required>
        </div>

        <div class="form-group">
            <label for="email">E-mail:</label>
            <input type="email" id="email" name="email" value="<?= htmlspecialchars($app['email']) ?>" required>
        </div>

        <div class="form-group">
            <label for="phone">Телефон:</label>
            <input type="text" id="phone" name="phone" value="<?= htmlspecialchars($app['phone']) ?>">
        </div>

        <div class="form-group">
            <label for="gender">Пол:</label>
            <select id="gender" name="gender">
                <option value="male" <?= $app['gender'] == 'male' ? 'selected' : '' ?>>Мужской</option>
                <option value="female" <?= $app['gender'] == 'female' ? 'selected' : '' ?>>Женский</option>
            </select>
        </div>

        <div class="form-group">
            <label for="biography">Биография:</label>
            <textarea id="biography" name="biography"><?= htmlspecialchars($app['biography']) ?></textarea>
        </div>

        <div class="form-group">
            <label for="languages">Языки программирования:</label>
            <select id="languages" name="languages[]" multiple>
                <?php foreach ($allLangs as $lang): ?>
                    <option value="<?= $lang['id'] ?>" <?= in_array($lang['id'], $selectedLangs) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($lang['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group action-buttons">
            <button type="submit" class="button">💾 Сохранить</button>
            <a href="admin.php" class="button">↩ Выйти без сохранения</a>
        </div>
    </form>
</div>
</body>
</html>
