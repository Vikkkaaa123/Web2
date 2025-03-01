<?php
// Устанавливаем кодировку
header('Content-Type: text/html; charset=UTF-8');

// Проверяем метод запроса
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    // Если есть параметр save, выводим сообщение
    if (!empty($_GET['save'])) {
        print('Спасибо, результаты сохранены.');
    }
    // Подключаем форму
    include('form.php');
    exit();
}

// Инициализируем массив для ошибок
$errors = FALSE;

// Проверка поля "ФИО"
if (empty($_POST['full_name'])) {
    print('Заполните ФИО.<br/>');
    $errors = TRUE;
} elseif (!preg_match('/^[а-яА-ЯёЁ\s]{1,150}$/u', $_POST['full_name'])) {
    print('ФИО должно содержать только буквы и пробелы и быть не длиннее 150 символов.<br/>');
    $errors = TRUE;
}

// Проверка поля "Телефон"
if (empty($_POST['phone'])) {
    print('Заполните телефон.<br/>');
    $errors = TRUE;
} elseif (!preg_match('/^\+?\d{10,15}$/', $_POST['phone'])) {
    print('Телефон должен содержать от 10 до 15 цифр.<br/>');
    $errors = TRUE;
}

// Проверка поля "Email"
if (empty($_POST['email'])) {
    print('Заполните email.<br/>');
    $errors = TRUE;
} elseif (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
    print('Некорректный email.<br/>');
    $errors = TRUE;
}

// Проверка поля "Дата рождения"
if (empty($_POST['birth_date'])) {
    print('Заполните дату рождения.<br/>');
    $errors = TRUE;
} elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST['birth_date'])) {
    print('Некорректный формат даты рождения.<br/>');
    $errors = TRUE;
}

// Проверка поля "Пол"
if (empty($_POST['gender'])) {
    print('Выберите пол.<br/>');
    $errors = TRUE;
} elseif (!in_array($_POST['gender'], ['male', 'female'])) {
    print('Некорректное значение пола.<br/>');
    $errors = TRUE;
}

// Проверка поля "Любимые языки программирования"
if (empty($_POST['languages'])) {
    print('Выберите хотя бы один язык программирования.<br/>');
    $errors = TRUE;
} else {
    $validLanguages = ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12'];
    foreach ($_POST['languages'] as $language) {
        if (!in_array($language, $validLanguages)) {
            print('Некорректный выбор языка программирования.<br/>');
            $errors = TRUE;
            break;
        }
    }
}

// Проверка поля "Биография"
if (empty($_POST['biography'])) {
    print('Заполните биографию.<br/>');
    $errors = TRUE;
}

// Проверка поля "Согласие с контрактом"
if (empty($_POST['agreement'])) {
    print('Необходимо согласие с контрактом.<br/>');
    $errors = TRUE;
}

// Если есть ошибки, завершаем выполнение
if ($errors) {
    exit();
}

// Подключение к базе данных
$user = 'u68606'; 
$pass = '9347178'; 
$db = new PDO('mysql:host=localhost;dbname=u68606', $user, $pass, [
    PDO::ATTR_PERSISTENT => true,
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);

try {
    // Сохраняем данные в таблицу applications
    $stmt = $db->prepare("INSERT INTO applications (full_name, phone, email, birth_date, gender, biography, agreement) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $_POST['full_name'],
        $_POST['phone'],
        $_POST['email'],
        $_POST['birth_date'],
        $_POST['gender'],
        $_POST['biography'],
        $_POST['agreement']
    ]);

    // Получаем ID последней вставленной записи
    $application_id = $db->lastInsertId();

    // Сохраняем выбранные языки программирования в таблицу application_languages
    foreach ($_POST['languages'] as $language_id) {
        $stmt = $db->prepare("INSERT INTO application_languages (application_id, language_id) VALUES (?, ?)");
        $stmt->execute([$application_id, $language_id]);
    }

    // Перенаправляем на страницу с сообщением об успехе
    header('Location: ?save=1');
    exit();
} catch (PDOException $e) {
    print('Ошибка: ' . $e->getMessage());
    exit();
}
?>