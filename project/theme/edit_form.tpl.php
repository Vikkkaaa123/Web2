<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Редактирование заявки</title>
    <link rel="stylesheet" href="/styles/style.css">
</head>
<body>
    <h2>Редактировать заявку №<?= htmlspecialchars($application['id']) ?></h2>
    <form method="POST" action="/modules/edit.php">
        <input type="hidden" name="id" value="<?= htmlspecialchars($application['id']) ?>">

        <label>ФИО:<br>
            <input name="full_name" value="<?= htmlspecialchars($application['full_name']) ?>" required>
        </label><br><br>

        <label>Email:<br>
            <input name="email" type="email" value="<?= htmlspecialchars($application['email']) ?>" required>
        </label><br><br>

        <label>Телефон:<br>
            <input name="phone" value="<?= htmlspecialchars($application['phone']) ?>" required>
        </label><br><br>

        <label>Дата рождения:<br>
            <input type="date" name="birth_date" value="<?= htmlspecialchars($application['birth_date']) ?>" required>
        </label><br><br>

        <label>Пол:<br>
            <label><input type="radio" name="gender" value="male" <?= $application['gender'] === 'male' ? 'checked' : '' ?>> Мужской</label>
            <label><input type="radio" name="gender" value="female" <?= $application['gender'] === 'female' ? 'checked' : '' ?>> Женский</label>
        </label><br><br>

        <label>Биография:<br>
            <textarea name="biography" rows="5" cols="50"><?= htmlspecialchars($application['biography']) ?></textarea>
        </label><br><br>

        <label>Языки программирования:<br>
            <?php foreach ($languages as $lang): ?>
                <label>
                    <input type="checkbox" name="languages[]" value="<?= $lang['id'] ?>"
                        <?= in_array($lang['id'], $lang_ids) ? 'checked' : '' ?>>
                    <?= htmlspecialchars($lang['name']) ?>
                </label><br>
            <?php endforeach; ?>
        </label><br>

        <label>
            <input type="checkbox" name="agreement" value="1" <?= $application['agreement'] ? 'checked' : '' ?>>
            Согласен с условиями
        </label><br><br>

        <button type="submit">Сохранить</button>
        <a href="/admin"><button type="button">Назад в админку</button></a>
    </form>
</body>
</html>
