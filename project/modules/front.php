<?php
function front_get($request) {
    $db = db_connect();
    $messages = [];
    $errors = [];
    $values = [];
    $allowed_lang = getLangs();

    // Все поля, которые могут быть в форме
    $all_fields = [
        'fio', 'phone', 'email', 'birth_day', 'birth_month', 'birth_year', 
        'gender', 'biography', 'languages', 'agreement'
    ];

    // Загружаем значения и ошибки для ВСЕХ полей
    foreach ($all_fields as $field) {
        $errors[$field] = !empty($_COOKIE[$field.'_error']) ? getErrorMessage($field, $_COOKIE[$field.'_error']) : '';
        $values[$field] = isset($_COOKIE[$field.'_value']) ? $_COOKIE[$field.'_value'] : '';
        setcookie($field.'_error', '', time() - 3600, '/');
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

    // Проверка ВСЕХ полей в одном месте
    $fields_to_check = [
        'fio' => ['required' => true],
        'phone' => ['required' => true],
        'email' => ['required' => true],
        'birth_day' => ['required' => true, 'date_part' => true],
        'birth_month' => ['required' => true, 'date_part' => true],
        'birth_year' => ['required' => true, 'date_part' => true],
        'gender' => ['required' => true],
        'biography' => ['required' => true],
        'languages' => ['required' => true, 'is_array' => true],
        'agreement' => ['required' => true, 'is_checkbox' => true]
    ];

    // Проверка каждого поля
    foreach ($fields_to_check as $field => $rules) {
        $value = $rules['is_checkbox'] ? (isset($post_data[$field]) ? 1 : 0) : ($post_data[$field] ?? '');
        
        if ($rules['is_array'] ?? false) {
            $value = $post_data[$field] ?? [];
        }

        $values[$field] = is_array($value) ? $value : trim($value);

        // Проверка на обязательность
        if ($rules['required']) {
            if ((is_array($value) && empty($value)) || (!is_array($value) && $value === '')) {
                $errors[$field] = 1; // Код ошибки "обязательное поле"
            }
        }
    }

    // Дополнительная проверка даты
    $date_valid = true;
    if (!empty($values['birth_day']) && !empty($values['birth_month']) && !empty($values['birth_year'])) {
        $date_valid = checkdate(
            (int)$values['birth_month'], 
            (int)$values['birth_day'], 
            (int)$values['birth_year']
        );
    }
    
    if (!$date_valid) {
        $errors['birth_day'] = 2; // Код ошибки "некорректная дата"
        $errors['birth_month'] = 2;
        $errors['birth_year'] = 2;
    }

    // Сохраняем ВСЕ значения в куки
    foreach ($values as $key => $val) {
        setcookie($key . '_value', is_array($val) ? implode(',', $val) : $val, time() + 3600, '/');
    }

    // Сохраняем ВСЕ ошибки в куки
    foreach ($fields_to_check as $field => $rules) {
        if (isset($errors[$field])) {
            setcookie($field . '_error', $errors[$field], time() + 3600, '/');
        } else {
            setcookie($field . '_error', '', time() - 3600, '/');
        }
    }

    // Если есть ошибки - возвращаем
    if (!empty($errors)) {
        return ['success' => false];
    }
    
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
        foreach ((array)$values['lang'] as $lang_id) {
            $stmt->execute([$app_id, $lang_id]);
        }

        $login = 'user_' . bin2hex(random_bytes(3));
        $password = bin2hex(random_bytes(4));
        $pass_hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $db->prepare("INSERT INTO users (login, password_hash) VALUES (?, ?)");
        $stmt->execute([$login, $pass_hash]);
        $user_id = $db->lastInsertId();

        $stmt = $db->prepare("INSERT INTO user_applications (user_id, application_id) VALUES (?, ?)");
        $stmt->execute([$user_id, $app_id]);

        $db->commit();

        if (!$is_ajax) {
            setcookie('save', '1', time() + 3600, '/');
            setcookie('login', $login, time() + 3600, '/');
            setcookie('password', $password, time() + 3600, '/');
        }

        return ['success' => true, 'login' => $login, 'password' => $password];

    } catch (PDOException $e) {
        $db->rollBack();
        error_log('DB Error: ' . $e->getMessage());
        return ['success' => false, 'errors' => ['db' => 'Ошибка при сохранении данных']];
    }
}

function getErrorMessage($field, $code) {
    $messages = [
        'fio' => 'Поле ФИО обязательно для заполнения',
        'phone' => 'Укажите номер телефона',
        'email' => 'Укажите email адрес',
        'birth_day' => $code == 1 ? 'Укажите день рождения' : 'Некорректная дата',
        'birth_month' => $code == 1 ? 'Укажите месяц рождения' : 'Некорректная дата',
        'birth_year' => $code == 1 ? 'Укажите год рождения' : 'Некорректная дата',
        'gender' => 'Укажите ваш пол',
        'biography' => 'Расскажите о себе',
        'languages' => 'Выберите хотя бы один язык программирования',
        'agreement' => 'Необходимо ваше согласие'
    ];
    
    return $messages[$field] ?? 'Неверное значение';
}
