<?php
include('./settings.php');

ini_set('display_errors', DISPLAY_ERRORS);
ini_set('include_path', INCLUDE_PATH);

include('./scripts/db.php');
include('./scripts/init.php');

// Определяем тип запроса
$is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

$request = array(
  'url' => isset($_GET['q']) ? $_GET['q'] : '',
  'method' => $_SERVER['REQUEST_METHOD'],
  'get' => $_GET,
  'post' => $_POST,
  'files' => $_FILES,
  'is_ajax' => $is_ajax,
  'Content-Type' => $is_ajax ? 'application/json' : 'text/html',
);

// Обработка raw POST данных для AJAX
/*
if ($is_ajax && empty($_POST) && $input = file_get_contents('php://input')) {
    parse_str($input, $request['post']);
    $_POST = $request['post'];
}
*/

$response = init($request, $urlconf);

// Установка заголовков
if (!empty($response['headers'])) {
    foreach ($response['headers'] as $key => $value) {
        header(is_string($key) ? "$key: $value" : $value);
    }
}

// Вывод ответа
if (!empty($response['entity'])) {
    if ($is_ajax && is_array($response['entity'])) {
        header('Content-Type: application/json');
        echo json_encode($response['entity']);
    } else {
        echo $response['entity'];
    }
}
