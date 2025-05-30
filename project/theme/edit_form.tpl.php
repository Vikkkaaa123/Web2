<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <title>Редактирование заявки</title>
  <link rel="stylesheet" href="/styles/style.css">
</head>
<body>

<div class="form-container">
  <h1>Редактировать заявку</h1>

  <form id="editForm" method="POST" action="" class="form">
    <input type="hidden" name="id" value="<?= htmlspecialchars($app['id']) ?>">

    <div class="form-group">
      <label for="name">Имя:</label>
      <input type="text" name="name" id="name" class="form-control<?= isset($errors['name']) ? ' error' : '' ?>" value="<?= htmlspecialchars($app['name']) ?>">
      <?php if (isset($errors['name'])): ?>
        <div class="error-text"><?= $errors['name'] ?></div>
      <?php endif; ?>
    </div>

    <div class="form-group">
      <label for="email">E-mail:</label>
      <input type="email" name="email" id="email" class="form-control<?= isset($errors['email']) ? ' error' : '' ?>" value="<?= htmlspecialchars($app['email']) ?>">
      <?php if (isset($errors['email'])): ?>
        <div class="error-text"><?= $errors['email'] ?></div>
      <?php endif; ?>
    </div>

    <div class="form-group">
      <label for="birthday">Дата рождения:</label>
      <input type="date" name="birthday" id="birthday" class="form-control<?= isset($errors['birthday']) ? ' error' : '' ?>" value="<?= htmlspecialchars($app['birthday']) ?>">
      <?php if (isset($errors['birthday'])): ?>
        <div class="error-text"><?= $errors['birthday'] ?></div>
      <?php endif; ?>
    </div>

    <div class="form-group">
      <label>Пол:</label><br>
      <label><input type="radio" name="gender" value="male" <?= $app['gender'] === 'male' ? 'checked' : '' ?>> Мужской</label>
      <label><input type="radio" name="gender" value="female" <?= $app['gender'] === 'female' ? 'checked' : '' ?>> Женский</label>
      <?php if (isset($errors['gender'])): ?>
        <div class="error-text"><?= $errors['gender'] ?></div>
      <?php endif; ?>
    </div>

    <div class="form-group">
      <label for="languages">Любимые языки программирования:</label>
      <select name="languages[]" id="languages" multiple class="form-control<?= isset($errors['languages']) ? ' error' : '' ?>">
        <?php foreach ($languages as $lang): ?>
          <option value="<?= $lang['id'] ?>" <?= in_array($lang['id'], $app['languages']) ? 'selected' : '' ?>>
            <?= htmlspecialchars($lang['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
      <?php if (isset($errors['languages'])): ?>
        <div class="error-text"><?= $errors['languages'] ?></div>
      <?php endif; ?>
    </div>

    <div class="form-group">
      <label for="biography">Биография:</label>
      <textarea name="biography" id="biography" class="form-control<?= isset($errors['biography']) ? ' error' : '' ?>"><?= htmlspecialchars($app['biography']) ?></textarea>
      <?php if (isset($errors['biography'])): ?>
        <div class="error-text"><?= $errors['biography'] ?></div>
      <?php endif; ?>
    </div>

    <div class="form-group">
      <label>
        <input type="checkbox" name="consent" value="1" <?= !empty($app['consent']) ? 'checked' : '' ?>>
        Согласен с условиями
      </label>
      <?php if (isset($errors['consent'])): ?>
        <div class="error-text"><?= $errors['consent'] ?></div>
      <?php endif; ?>
    </div>

    <button type="submit" class="submit-btn">Сохранить</button>
    <a href="/admin.php" class="submit-btn">Назад</a>
  </form>
</div>

</body>
</html>
