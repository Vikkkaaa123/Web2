<?php
header('Content-Type: text/html; charset=UTF-8');

// Подключение к базе данных
$user = 'u68606'; 
$pass = '9347178'; 

try {
    $db = new PDO('mysql:host=localhost;dbname=u68606', $user, $pass, [
        PDO::ATTR_PERSISTENT => true,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    die('Ошибка подключения: ' . $e->getMessage());
}

// Функция получения языков программирования
function getLangs($db) {
    try {
        $allowed_lang = [];
        $data = $db->query("SELECT id, name FROM programming_languages")->fetchAll();
        foreach ($data as $lang) {
            $allowed_lang[$lang['id']] = $lang['name'];
        }
        return $allowed_lang;
    } catch (PDOException $e) {
        die('Ошибка: ' . $e->getMessage());
    }
}

$allowed_lang = getLangs($db);

// Если метод запроса GET, показываем форму
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    if (!empty($_GET['save'])) {
        echo '<p style="color: black; font-size: 24px; text-align: center; margin-top: 20px;">Спасибо, данные сохранены!</p>';
    } else {
        include('form.php');  // Показываем форму
    }
    exit();
}

// Проверяем метод POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // Получение данных из формы
    $fio = trim($_POST['full_name'] ?? '');
    $num = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $day = trim($_POST['birth_day'] ?? '');
    $month = trim($_POST['birth_month'] ?? ''); 
    $year = trim($_POST['birth_year'] ?? '');
    $biography = trim($_POST['biography'] ?? '');
    $gen = $_POST['gender'] ?? '';
    $languages = is_array($_POST['languages']) ? $_POST['languages'] : [];
    $agreement = isset($_POST['agreement']) && $_POST['agreement'] === 'on' ? 1 : 0;

    // Форматируем дату в строку YYYY-MM-DD
    $birth_date = sprintf("%04d-%02d-%02d", $year, $month, $day);
    
    // Валидация данных
    $errors = [];

    if (empty($fio) || strlen($fio) > 128 || !preg_match('/^[a-zA-Zа-яА-ЯёЁ\s]+$/u', $fio)) {
        $errors[] = 'Некорректное имя.';
    }
    if (empty($num) || !preg_match('/^\+7\d{10}$/', $num)) {
        $errors[] = 'Некорректный номер телефона.';
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Некорректный email.';
    }
    if (empty($gen) || !in_array($gen, ["male", "female"])) {
        $errors[] = 'Некорректный пол.';
    }
    if (empty($biography) || strlen($biography) > 512) {
        $errors[] = 'Некорректная биография.';
    }
    if (!checkdate($month, $day, $year)) {
        $errors[] = 'Некорректная дата.';
    }
    if (empty($languages)) {
        $errors[] = 'Не выбран язык программирования.';
    }
    if (!$agreement) {
        $errors[] = 'Необходимо согласие.';
    }

    // Если есть ошибки, показываем их и останавливаем выполнение
    if ($errors) {
        foreach ($errors as $error) {
            echo "<p style='color:red;'>$error</p>";
        }
        include('form.php');  // Показываем форму снова, если есть ошибки
        exit();
    }

    // Запись данных в базу
    try {
        // Запись заявки в таблицу applications
        $stmt = $db->prepare("INSERT INTO applications (full_name, phone, email, birth_date, gender, biography, agreement) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$fio, $num, $email, $birth_date, $gen, $biography, $agreement]); // Используем $birth_date

        // Получаем ID последней вставленной заявки
        $application_id = $db->lastInsertId();

        // Записываем языки программирования в базу
        $stmt_insert = $db->prepare("INSERT INTO application_languages (application_id, language_id) VALUES (?, ?)");
        foreach ($languages as $language_id) {
            $stmt_insert->execute([$application_id, $language_id]);
        }

        // Перенаправляем пользователя на страницу с сообщением об успехе
        header('Location: index.php?save=1');
        exit();
    } catch (PDOException $e) {
        die('Ошибка сохранения: ' . $e->getMessage());
    }
}
?>
