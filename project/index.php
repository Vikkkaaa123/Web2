<?php
include('./settings.php');

ini_set('display_errors', DISPLAY_ERRORS);
ini_set('include_path', INCLUDE_PATH);

include('./scripts/db.php');
include('./scripts/init.php');

$request = [
  'url' => $_GET['q'] ?? '',
  'method' => in_array($_POST['method'] ?? '', ['get', 'post', 'put', 'delete']) 
              ? $_POST['method'] 
              : strtolower($_SERVER['REQUEST_METHOD']),
  'get' => $_GET,
  'post' => $_POST,
  'put' => ($_POST['method'] ?? '') == 'put' ? $_POST : [],
  'delete' => ($_POST['method'] ?? '') == 'delete' ? $_POST : [],
  'Content-Type' => 'text/html'
];

$response = init($request);

foreach ($response['headers'] ?? [] as $key => $value) {
    header(is_string($key) ? "$key: $value" : $value);
}

echo $response['entity'] ?? '';
