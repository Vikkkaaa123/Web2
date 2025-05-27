<?php
require_once __DIR__ . '/../scripts/db.php';



function front_get($request) {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    $db = db_connect();
    
    $data = [
        'errors' => [],
        'values' => [
            'fio' => $_COOKIE['fio_value'] ?? '',
            'email' => $_COOKIE['email_value'] ?? '',
            'phone' => $_COOKIE['phone_value'] ?? '',
            'birth_date' => $_COOKIE['birth_date_value'] ?? '',
            'gender' => $_COOKIE['gender_value'] ?? '',
            'biography' => $_COOKIE['biography_value'] ?? '',
            'languages' => isset($_COOKIE['languages_value']) ? explode(',', $_COOKIE['languages_value']) : [],
            'agreement' => isset($_COOKIE['agreement_value'])
        ],
        'language_options' => $db->query("SELECT id, name FROM programming_languages")->fetchAll(),
        'messages' => []
    ];

    $fields = [
        'fio' => [
            '1' => 'ФИО обязательно для заполнения',
            '2' => 'ФИО должно быть короче 150 символов',
            '3' => 'ФИО содержит недопустимые символы'
        ],
        'email' => [
            '1' => 'Email обязателен для заполнения',
            '2' => 'Некорректный формат email'
        ],
        'phone' => [
            '1' => 'Телефон обязателен для заполнения',
            '2' => 'Формат: +7XXXXXXXXXX'
        ],
        'birth_date' => [
            '1' => 'Дата рождения обязательна',
            '2' => 'Некорректная дата (требуется формат YYYY-MM-DD)'
        ],
        'gender' => [
            '1' => 'Укажите пол',
            '2' => 'Недопустимое значение пола'
        ],
        'biography' => [
            '1' => 'Биография слишком длинная (макс. 500 символов)',
            '2' => 'Биография содержит запрещенные символы'
        ],
        'languages' => [
            '1' => 'Выберите хотя бы один язык',
            '2' => 'Выбран недопустимый язык'
        ],
        'agreement' => [
            '1' => 'Необходимо принять соглашение'
        ]
    ];

    foreach ($fields as $field => $messages) {
        if (!empty($_COOKIE["{$field}_error"])) {
            $error_code = $_COOKIE["{$field}_error"];
            $data['errors'][$field] = $messages[$error_code] ?? 'Ошибка';
            setcookie("{$field}_error", '', time() - 3600);
        }
    }

    if (!empty($_COOKIE['save_success'])) {
        $data['messages'][] = 'Данные успешно сохранены!';
        setcookie('save_success', '', time() - 3600);
    }

    return theme('form', $data);
}




function front_post($request) {
    $db = db_connect();
    $errors = [];

    if (empty($request['fio'])) {
        $errors['fio'] = '1';
    } elseif (strlen($request['fio']) > 150) {
        $errors['fio'] = '2';
    } elseif (!preg_match('/^[a-zA-Zа-яА-ЯёЁ\s\-]+$/u', $request['fio'])) {
        $errors['fio'] = '3';
    }
    setcookie('fio_value', $request['fio'] ?? '', time() + 3600);

    if (empty($request['email'])) {
        $errors['email'] = '1';
    } elseif (!filter_var($request['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = '2';
    }
    setcookie('email_value', $request['email'] ?? '', time() + 3600);

    if (empty($request['phone'])) {
        $errors['phone'] = '1';
    } elseif (!preg_match('/^\+7\d{10}$/', $request['phone'])) {
        $errors['phone'] = '2';
    }
    setcookie('phone_value', $request['phone'] ?? '', time() + 3600);

    if (empty($request['birth_date'])) {
        $errors['birth_date'] = '1';
    } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $request['birth_date'])) {
        $errors['birth_date'] = '2';
    }
    setcookie('birth_date_value', $request['birth_date'] ?? '', time() + 3600);

    $allowed_genders = ['male', 'female'];
    if (empty($request['gender'])) {
        $errors['gender'] = '1';
    } elseif (!in_array($request['gender'], $allowed_genders)) {
        $errors['gender'] = '2';
    }
    setcookie('gender_value', $request['gender'] ?? '', time() + 3600);

    if (!empty($request['biography'])) {
        if (strlen($request['biography']) > 500) {
            $errors['biography'] = '1';
        } elseif (preg_match('/[<>{}]/', $request['biography'])) {
            $errors['biography'] = '2';
        }
    }
    setcookie('biography_value', $request['biography'] ?? '', time() + 3600);

    $allowed_languages = $db->query("SELECT id FROM programming_languages")->fetchAll(PDO::FETCH_COLUMN);
    if (empty($request['languages'])) {
        $errors['languages'] = '1';
    } else {
        foreach ($request['languages'] as $lang_id) {
            if (!in_array($lang_id, $allowed_languages)) {
                $errors['languages'] = '2';
                break;
            }
        }
    }
    setcookie('languages_value', implode(',', $request['languages'] ?? []), time() + 3600);

    if (empty($request['agreement'])) {
        $errors['agreement'] = '1';
    }
    setcookie('agreement_value', $request['agreement'] ?? '', time() + 3600);

    foreach ($errors as $field => $error_code) {
        setcookie("{$field}_error", $error_code, time() + 3600);
    }

    if (!empty($errors)) {
        return redirect('form');
    }

    try {
        $stmt = $db->prepare("
            INSERT INTO applications 
            (full_name, email, phone, birth_date, gender, biography, agreement)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $request['fio'],
            $request['email'],
            $request['phone'],
            $request['birth_date'],
            $request['gender'],
            $request['biography'],
            !empty($request['agreement']) ? 1 : 0
        ]);

        $app_id = $db->lastInsertId();
        foreach ($request['languages'] as $lang_id) {
            $db->prepare("
                INSERT INTO application_languages (application_id, language_id)
                VALUES (?, ?)
            ")->execute([$app_id, $lang_id]);
        }

        $fields = ['fio', 'email', 'phone', 'birth_date', 'gender', 'biography', 'languages', 'agreement'];
        foreach ($fields as $field) {
            setcookie("{$field}_error", '', time() - 3600);
            setcookie("{$field}_value", '', time() - 3600);
        }

        setcookie('save_success', '1', time() + 3600);
        return redirect('form');

    } catch (PDOException $e) {
        error_log("Ошибка сохранения: " . $e->getMessage());
        setcookie('save_error', '1', time() + 3600);
        return redirect('form');
    }
}
