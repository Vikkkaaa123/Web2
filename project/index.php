<?php
// Подключение конфигурации
include('./settings.php');

// Настройки отображения ошибок
ini_set('display_errors', DISPLAY_ERRORS);
ini_set('include_path', INCLUDE_PATH);

// Подключение основных скриптов
include('./scripts/db.php');
include('./scripts/init.php');

// Определение типа запроса (AJAX или обычный)
$is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

// Подготовка данных запроса
$request = [
    'url' => $_GET['q'] ?? '',
    'method' => strtolower($_SERVER['REQUEST_METHOD']),
    'get' => $_GET,
    'post' => $_POST,
    'files' => $_FILES,
    'is_ajax' => $is_ajax,
    'Content-Type' => $is_ajax ? 'application/json' : 'text/html'
];

// Обработка raw POST данных для AJAX
if ($is_ajax && empty($_POST) && $input = file_get_contents('php://input')) {
    parse_str($input, $request['post']);
    $_POST = $request['post'];
}

// Установка заголовков для AJAX
if ($is_ajax) {
    header('Content-Type: application/json');
}

// Обработка запроса
$response = init($request, $urlconf);

// Отправка заголовков
if (!empty($response['headers'])) {
    foreach ($response['headers'] as $key => $value) {
        header(is_string($key) ? "$key: $value" : $value);
    }
}

// Вывод результата
if (!empty($response['entity'])) {
    if ($is_ajax && is_array($response['entity'])) {
        echo json_encode($response['entity']);
    } else {
        echo $response['entity'];
    }
}
