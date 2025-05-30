<?php
// Подключаем стили
echo '<link rel="stylesheet" href="/styles/style.css">';
echo '<link rel="stylesheet" href="/styles/header-style.css">';
?>

<div class="form-messages"></div>
<div class="formstyle1">
  <form id="myform" class="application" method="POST" action="">
    <h2 class="white-text">Редактировать заявку</h2>

    <label>
      ФИО: <br/>
      <input type="text" name="fio"
             class="input-field <?= !empty($errors['fio']) ? 'error' : '' ?>"
             value="<?= htmlspecialchars($values['fio'] ?? '') ?>"/>
      <?php if (!empty($errors['fio'])): ?>
        <span class="error-text"><?= $errors['fio'] ?></span>
      <?php endif; ?>
    </label><br/>

    <label>
      Телефон: <br/>
      <input type="tel" name="phone"
             class="input-field <?= !empty($errors['phone']) ? 'error' : '' ?>"
             value="<?= htmlspecialchars($values['phone'] ?? '') ?>"/>
      <?php if (!empty($errors['phone'])): ?>
        <span class="error-text"><?= $errors['phone'] ?></span>
      <?php endif; ?>
    </label><br/>

    <label>
      Email: <br/>
      <input type="email" name="email"
             class="input-field <?= !empty($errors['email']) ? 'error' : '' ?>"
             value="<?= htmlspecialchars($values['email'] ?? '') ?>"/>
      <?php if (!empty($errors['email'])): ?>
        <span class="error-text"><?= $errors['email'] ?></span>
      <?php endif; ?>
    </label><br/>

    <label>
      Дата рождения: <br/>
      <div class="date-fields">
        <input type="number" name="birth_day"
               class="input-field date-input <?= !empty($errors['birth_day']) ? 'error' : '' ?>"
               placeholder="День" value="<?= htmlspecialchars($values['birth_day'] ?? '') ?>"/>
        <input type="number" name="birth_month"
               class="input-field date-input <?= !empty($errors['birth_month']) ? 'error' : '' ?>"
               placeholder="Месяц" value="<?= htmlspecialchars($values['birth_month'] ?? '') ?>"/>
        <input type="number" name="birth_year"
               class="input-field date-input <?= !empty($errors['birth_year']) ? 'error' : '' ?>"
               placeholder="Год" value="<?= htmlspecialchars($values['birth_year'] ?? '') ?>"/>
      </div>
      <?php if (!empty($errors['birth_date'])): ?>
        <span class="error-text"><?= $errors['birth_date'] ?></span>
      <?php endif; ?>
    </label><br/>

    <label class="white-text">
      Пол: <br/>
      <div class="gender-options">
        <label>
          <input type="radio" name="gender" value="male"
                 <?= ($values['gender'] ?? '') === 'male' ? 'checked' : '' ?>>
          Мужской
        </label>
        <label>
          <input type="radio" name="gender" value="female"
                 <?= ($values['gender'] ?? '') === 'female' ? 'checked' : '' ?>>
          Женский
        </label>
      </div>
      <?php if (!empty($errors['gender'])): ?>
        <span class="error-text"><?= $errors['gender'] ?></span>
      <?php endif; ?>
    </label><br/>

    <label>
      Любимые языки программирования: <br/>
      <select name="languages[]" multiple="multiple"
              class="input-field <?= !empty($errors['lang']) ? 'error' : '' ?>"
              style="height: auto; min-height: 100px;">
        <?php
        $user_languages = isset($values['lang']) ? explode(",", $values['lang']) : [];
        foreach ($allowed_lang as $lang): ?>
          <option value="<?= $lang['id'] ?>"
            <?= in_array($lang['id'], $user_languages) ? 'selected="selected"' : '' ?>>
            <?= htmlspecialchars($lang['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
      <?php if (!empty($errors['lang'])): ?>
        <span class="error-text"><?= $errors['lang'] ?></span>
      <?php endif; ?>
    </label><br/>

    <label>
      Биография: <br/>
      <textarea name="biography"
                class="input-field <?= !empty($errors['biography']) ? 'error' : '' ?>"><?= htmlspecialchars($values['biography'] ?? '') ?></textarea>
      <?php if (!empty($errors['biography'])): ?>
        <span class="error-text"><?= $errors['biography'] ?></span>
      <?php endif; ?>
    </label><br/>

    <div class="checkbox-block">
      <label class="form-checkbox">
        <input type="checkbox" name="agreement"
               class="custom-checkbox <?= !empty($errors['agreement']) ? 'error' : '' ?>"
               <?= !empty($values['agreement']) ? 'checked' : '' ?>>
        <span class="checkmark"></span>
        Согласен(а) с <a href="#">обработкой персональных данных</a>
      </label>
      <?php if (!empty($errors['agreement'])): ?>
        <span class="error-text"><?= $errors['agreement'] ?></span>
      <?php endif; ?>
    </div><br/>

    <input class="submit-btn" type="submit" value="Сохранить изменения"/>
  </form>
</div>
