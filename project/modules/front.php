<?php
require_once __DIR__ . '/../scripts/db.php';

function front_get($request) {
    $db = db_connect();
    
    $data = [
        'messages' => [],
        'errors' => [],
        'values' => [
            'uid' => '',
            'fio' => '',
            'email' => '',
            'phone' => '',
            'birth_date' => '',
            'gender' => '',
            'biography' => '',
            'languages' => [],
            'agreement' => false
        ],
        'language_options' => get_languages($db)
    ];

    check_form_errors($data);

    if (is_admin() && !empty($request['uid'])) {
        load_user_data($db, $request['uid'], $data);
    }

    if (has_user_session()) {
        load_session_user_data($db, $data);
    }

    return theme('form', $data);
}

function front_post($request) {
    $db = db_connect();
    
    $errors = validate_form_data($request);
    
    if (!empty($errors)) {
        save_errors_to_cookies($errors, $request);
        return redirect('form');
    }

    try {
        $db->beginTransaction();
        
        if (is_admin() && !empty($request['uid'])) {
            update_application($db, $request);
        } elseif (has_user_session()) {
            update_user_application($db, $request);
        } else {
            create_new_application($db, $request);
        }
        
        $db->commit();
        set_success_cookies();
        return redirect('form?success=1');
        
    } catch (PDOException $e) {
        $db->rollBack();
        error_log("Database error: " . $e->getMessage());
        setcookie('save_error', '1', time() + 3600);
        return redirect('form');
    }
}

/*** Вспомогательные функции ***/

// Получение списка языков из БД
function get_languages($db) {
    return $db->query("SELECT id, name FROM programming_languages")->fetchAll();
}

// Проверка ошибок в куках
function check_form_errors(&$data) {
    $fields = ['fio', 'email', 'phone', 'birth_date', 'gender', 'biography', 'languages', 'agreement'];
    
    foreach ($fields as $field) {
        if (!empty($_COOKIE["{$field}_error"])) {
            $data['errors'][$field] = true;
            setcookie("{$field}_error", '', time() - 3600);
        }
        if (!empty($_COOKIE["{$field}_value"])) {
            $data['values'][$field] = $_COOKIE["{$field}_value"];
            setcookie("{$field}_value", '', time() - 3600);
        }
    }
}

function validate_form_data($request) {
    $errors = [];
    
    if (empty($request['fio'])) {
        $errors['fio'] = 'Поле обязательно для заполнения';
    } elseif (strlen($request['fio']) > 150) {
        $errors['fio'] = 'Максимум 150 символов';
    } elseif (!preg_match('/^[a-zA-Zа-яА-ЯёЁ\s\-]+$/u', $request['fio'])) {
        $errors['fio'] = 'Допустимы только буквы, пробелы и дефисы';
    }
    
    if (empty($request['email'])) {
        $errors['email'] = 'Поле обязательно для заполнения';
    } elseif (!filter_var($request['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Некорректный email';
    } elseif (strlen($request['email']) > 100) {
        $errors['email'] = 'Максимум 100 символов';
    }
    
    if (empty($request['phone'])) {
        $errors['phone'] = 'Поле обязательно для заполнения';
    } elseif (!preg_match('/^\+7\d{10}$/', $request['phone'])) {
        $errors['phone'] = 'Формат: +7XXXXXXXXXX';
    }
    
    if (empty($request['birth_date'])) {
        $errors['birth_date'] = 'Поле обязательно для заполнения';
    } else {
        $date = DateTime::createFromFormat('Y-m-d', $request['birth_date']);
        if (!$date || $date->format('Y-m-d') !== $request['birth_date']) {
            $errors['birth_date'] = 'Некорректная дата';
        }
    }
    
    $allowed_genders = ['male', 'female'];
    if (empty($request['gender'])) {
        $errors['gender'] = 'Укажите пол';
    } elseif (!in_array($request['gender'], $allowed_genders)) {
        $errors['gender'] = 'Недопустимое значение';
    }
    
    if (!empty($request['biography'])) {
        if (strlen($request['biography']) > 500) {
            $errors['biography'] = 'Максимум 500 символов';
        } elseif (preg_match('/[<>{}]/', $request['biography'])) {
            $errors['biography'] = 'Запрещенные символы: < > { }';
        }
    }
    
    $allowed_languages = [1, 2, 3]; // ID допустимых языков из БД
    if (empty($request['languages'])) {
        $errors['languages'] = 'Выберите хотя бы один язык';
    } else {
        foreach ($request['languages'] as $lang) {
            if (!in_array($lang, $allowed_languages)) {
                $errors['languages'] = 'Выбран недопустимый язык';
                break;
            }
        }
    }
    
    if (empty($request['agreement'])) {
        $errors['agreement'] = 'Необходимо дать согласие';
    }
    
    return $errors;
}

function update_application($db, $data) {
    $stmt = $db->prepare("UPDATE applications SET 
        full_name = ?, email = ?, phone = ?, birth_date = ?,
        gender = ?, biography = ?, agreement = ?
        WHERE id = ?");
    $stmt->execute([
        $data['fio'], $data['email'], $data['phone'],
        $data['birth_date'], $data['gender'], $data['biography'],
        !empty($data['agreement']) ? 1 : 0, $data['uid']
    ]);
    
    update_languages($db, $data['uid'], $data['languages']);
}

function create_new_application($db, $data) {
    $stmt = $db->prepare("INSERT INTO applications 
        (full_name, email, phone, birth_date, gender, biography, agreement)
        VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $data['fio'], $data['email'], $data['phone'],
        $data['birth_date'], $data['gender'], $data['biography'],
        !empty($data['agreement']) ? 1 : 0
    ]);
    
    $app_id = $db->lastInsertId();
    update_languages($db, $app_id, $data['languages']);
}

function update_languages($db, $app_id, $languages) {
    $db->prepare("DELETE FROM application_languages WHERE application_id = ?")
       ->execute([$app_id]);
    
    if (!empty($languages)) {
        $stmt = $db->prepare("INSERT INTO application_languages 
            (application_id, language_id) VALUES (?, ?)");
        foreach ($languages as $lang_id) {
            $stmt->execute([$app_id, $lang_id]);
        }
    }
}
