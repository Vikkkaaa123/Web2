<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <title>Редактирование заявки</title>
  <link rel="stylesheet" href="/styles/style.css">
</head>
<body>
  <div class="form-wrapper">
    <h1 class="form-title">Редактирование заявки</h1>

    <?php if (!empty($message)): ?>
      <p class="success-message"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <form action="" method="POST" class="form">
      <input type="hidden" name="id" value="<?= htmlspecialchars($app['id']) ?>">

      <label class="form-label">
        Имя:<br>
        <input name="name" value="<?= htmlspecialchars($app['name']) ?>" class="form-input" required>
      </label><br>

      <label class="form-label">
        Email:<br>
        <input name="email" type="email" value="<?= htmlspecialchars($app['email']) ?>" class="form-input" required>
      </label><br>

      <label class="form-label">
        Дата рождения:<br>
        <input name="birthday" type="date" value="<?= htmlspecialchars($app['birthday']) ?>" class="form-input" required>
      </label><br>

      <label class="form-label">
        Пол:<br>
        <label><input type="radio" name="gender" value="male" <?= $app['gender'] === 'male' ? 'checked' : '' ?>> Мужской</label>
        <label><input type="radio" name="gender" value="female" <?= $app['gender'] === 'female' ? 'checked' : '' ?>> Женский</label>
      </label><br>

      <label class="form-label">
        Любимый язык программирования:<br>
        <select name="languages[]" multiple class="form-input">
          <?php foreach ($languages as $language): ?>
            <option value="<?= $language['id'] ?>"
              <?= in_array($language['id'], $app['languages']) ? 'selected' : '' ?>>
              <?= htmlspecialchars($language['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </label><br>

      <label class="form-label">
        Биография:<br>
        <textarea name="biography" class="form-input" required><?= htmlspecialchars($app['biography']) ?></textarea>
      </label><br>

      <label class="form-label checkbox-label">
        <input type="checkbox" name="consent" value="1" <?= $app['consent'] ? 'checked' : '' ?>>
        Согласие с условиями
      </label><br><br>

      <button type="submit" class="form-button">Сохранить</button>
    </form>

    <p><a href="/admin.php" class="form-button">Назад в админку</a></p>
  </div>
</body>
</html>
