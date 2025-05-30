 <!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <title>Редактировать заявку</title>
  <link rel="stylesheet" href="/styles/style.css">
</head>
<body>
  <div class="form-container">
    <h2>Редактировать заявку</h2>
    <form action="/modules/save_edit.php" method="POST" class="main-form">
      <input type="hidden" name="id" value="<?= htmlspecialchars($app['id']) ?>">

      <label for="name">Имя:</label>
      <input type="text" id="name" name="name" value="<?= htmlspecialchars($app['name']) ?>" class="<?= isset($errors['name']) ? 'error' : '' ?>">
      <?php if (!empty($errors['name'])): ?><div class="error-message"><?= $errors['name'] ?></div><?php endif; ?>

      <label for="email">E-mail:</label>
      <input type="email" id="email" name="email" value="<?= htmlspecialchars($app['email']) ?>" class="<?= isset($errors['email']) ? 'error' : '' ?>">
      <?php if (!empty($errors['email'])): ?><div class="error-message"><?= $errors['email'] ?></div><?php endif; ?>

      <label for="birthdate">Дата рождения:</label>
      <input type="date" id="birthdate" name="birthdate" value="<?= htmlspecialchars($app['birthdate']) ?>" class="<?= isset($errors['birthdate']) ? 'error' : '' ?>">
      <?php if (!empty($errors['birthdate'])): ?><div class="error-message"><?= $errors['birthdate'] ?></div><?php endif; ?>

      <label>Пол:</label><br>
      <label><input type="radio" name="gender" value="male" <?= $app['gender'] === 'male' ? 'checked' : '' ?>> Мужской</label>
      <label><input type="radio" name="gender" value="female" <?= $app['gender'] === 'female' ? 'checked' : '' ?>> Женский</label>
      <?php if (!empty($errors['gender'])): ?><div class="error-message"><?= $errors['gender'] ?></div><?php endif; ?>

      <label>Любимые языки программирования:</label>
      <?php foreach ($languages as $lang): ?>
        <label>
          <input type="checkbox" name="languages[]" value="<?= $lang['id'] ?>"
            <?= in_array($lang['id'], $app['languages']) ? 'checked' : '' ?>>
          <?= htmlspecialchars($lang['name']) ?>
        </label>
      <?php endforeach; ?>
      <?php if (!empty($errors['languages'])): ?><div class="error-message"><?= $errors['languages'] ?></div><?php endif; ?>

      <label for="bio">Биография:</label>
      <textarea id="bio" name="bio" class="<?= isset($errors['bio']) ? 'error' : '' ?>"><?= htmlspecialchars($app['bio']) ?></textarea>
      <?php if (!empty($errors['bio'])): ?><div class="error-message"><?= $errors['bio'] ?></div><?php endif; ?>

      <label><input type="checkbox" name="consent" value="1" <?= $app['consent'] ? 'checked' : '' ?>> Согласен с условиями</label>
      <?php if (!empty($errors['consent'])): ?><div class="error-message"><?= $errors['consent'] ?></div><?php endif; ?>

      <br><br>
      <button type="submit">Сохранить изменения</button>
    </form>
  </div>
</body>
</html>
