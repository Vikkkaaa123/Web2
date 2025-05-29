<?php
function front_get($request) {
    $messages = [];
    $errors = [];
    $values = [];
    $allowed_lang = getLangs();

    // Все поля формы
    $all_fields = [
        'fio', 'phone', 'email', 'birth_day', 'birth_month', 'birth_year',
        'gender', 'biography', 'languages', 'agreement'
    ];

    // Загружаем значения и ошибки для каждого поля
    foreach ($all_fields as $field) {
        $errors[$field] = !empty($_COOKIE["{$field}_error"]) 
            ? getErrorMessage($field, $_COOKIE["{$field}_error"]) 
            : '';

        $values[$field] = $_COOKIE["{$field}_value"] ?? '';

        // Чистим куки после использования
        setcookie("{$field}_error", '', time() - 3600, '/');
        setcookie("{$field}_value", '', time() - 3600, '/');
    }

    // Обработка успешного сохранения
    if (!empty($_COOKIE['save'])) {
        $messages[] = 'Спасибо, результаты сохранены.';
        setcookie('save', '', time() - 3600, '/');
    }

    return theme('form', [
        'messages' => $messages,
        'errors' => $errors,
        'values' => $values,
        'allowed_lang' => $allowed_lang
    ]);
}


function front_post($request) {
    $db = db_connect();
    if (!$db) {
        return ['success' => false, 'errors' => ['db' => 'Ошибка подключения к БД']];
    }

    $post_data = $request['post'] ?? $_POST;
    $errors = [];
    $values = [];

    // Все поля, которые должны проверяться
    $fields = [
        'fio', 'phone', 'email', 'birth_day', 'birth_month', 'birth_year',
        'gender', 'biography', 'languages', 'agreement'
    ];

    // Проверяем каждое поле
    foreach ($fields as $field) {
        // Получаем значение (для чекбоксов и массивов - особый случай)
        if ($field === 'agreement') {
            $values[$field] = isset($post_data[$field]) ? 1 : 0;
        } elseif ($field === 'languages') {
            $values[$field] = $post_data[$field] ?? [];
        } else {
            $values[$field] = trim($post_data[$field] ?? '');
        }

        // Проверка на заполненность
        if (empty($values[$field])) {
            $errors[$field] = 1; // Код ошибки "поле обязательно"
        }
    }

    // Дополнительная проверка даты
    if (!isset($errors['birth_day']) && !isset($errors['birth_month']) && !isset($errors['birth_year'])) {
        if (!checkdate((int)$values['birth_month'], (int)$values['birth_day'], (int)$values['birth_year'])) {
            $errors['birth_day'] = 2; // Код ошибки "некорректная дата"
            $errors['birth_month'] = 2;
            $errors['birth_year'] = 2;
        }
    }

    // Сохраняем ВСЕ значения и ошибки в куки
    foreach ($values as $field => $value) {
        setcookie("{$field}_value", is_array($value) ? implode(',', $value) : $value, [
            'expires' => time() + 3600,
            'path' => '/',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    }

    foreach ($errors as $field => $code) {
        setcookie("{$field}_error", $code, [
            'expires' => time() + 3600,
            'path' => '/',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    }

    // Если есть ошибки - возвращаем
    if (!empty($errors)) {
        return ['success' => false];
    }

    // Сохраняем в БД
    try {
        $db->beginTransaction();

        $birth_date = sprintf('%04d-%02d-%02d', $values['birth_year'], $values['birth_month'], $values['birth_day']);

        $stmt = $db->prepare("INSERT INTO applications 
            (full_name, phone, email, birth_date, gender, biography, agreement) 
            VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $values['fio'],
            $values['phone'],
            $values['email'],
            $birth_date,
            $values['gender'],
            $values['biography'],
            $values['agreement']
        ]);
        $app_id = $db->lastInsertId();

        $stmt = $db->prepare("INSERT INTO application_languages (application_id, language_id) VALUES (?, ?)");
        foreach ($values['lang'] as $lang_id) {
            $stmt->execute([$app_id, $lang_id]);
        }

        $login = 'user_' . bin2hex(random_bytes(3));
        $password = bin2hex(random_bytes(4));
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $db->prepare("INSERT INTO users (login, password_hash) VALUES (?, ?)");
        $stmt->execute([$login, $hash]);

        $user_id = $db->lastInsertId();
        $stmt = $db->prepare("INSERT INTO user_applications (user_id, application_id) VALUES (?, ?)");
        $stmt->execute([$user_id, $app_id]);

        $db->commit();

        if (!$is_ajax) {
            setcookie('save', 1, time() + 3600, '/');
            setcookie('login', $login, time() + 3600, '/');
            setcookie('password', $password, time() + 3600, '/');
        }

        return ['success' => true, 'login' => $login, 'password' => $password];
    } catch (PDOException $e) {
        $db->rollBack();
        error_log('DB Error: ' . $e->getMessage());
        return ['success' => false, 'errors' => ['db' => 'Ошибка при сохранении в БД']];
    }
}

function getErrorMessage($field, $code) {
    $messages = [
        'fio' => 'Укажите ФИО',
        'phone' => 'Укажите телефон',
        'email' => 'Укажите email',
        'birth_day' => ($code == 1) ? 'Укажите день' : 'Некорректный день',
        'birth_month' => ($code == 1) ? 'Укажите месяц' : 'Некорректный месяц',
        'birth_year' => ($code == 1) ? 'Укажите год' : 'Некорректный год',
        'gender' => 'Укажите пол',
        'biography' => 'Напишите биографию',
        'languages' => 'Выберите хотя бы один язык',
        'agreement' => 'Необходимо согласие'
    ];

    return $messages[$field] ?? 'Ошибка в поле';
}
