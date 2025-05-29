<?php
/**
 * Инициализация приложения
 */

function init($request = [], $urlconf = []) {
    require_once __DIR__ . '/db.php';
    
    global $db;
    $db = db_connect();
    
    if (!$db) {
        if ($request['is_ajax'] ?? false) {
            return [
                'headers' => ['Content-Type' => 'application/json'],
                'entity' => ['success' => false, 'error' => 'Database error']
            ];
        }
        return [
            'headers' => ['HTTP/1.1 500 Internal Server Error'],
            'entity' => 'Database connection failed'
        ];
    }

    $response = array();
    $template = 'page';
    $c = array(
        '#content' => array(), 
        '#request' => $request
    );

    $q = $request['url'] ?? '';
    $method = strtolower($request['method'] ?? 'get');

    foreach ($urlconf as $url => $r) {
        $matches = array();

        // Проверка соответствия URL
        if ($url == '' || $url[0] != '/') {
            if ($url != $q) continue;
        } else {
            if (!preg_match($url, $q, $matches)) continue;
        }

        // Аутентификация
        if (!empty($r['auth'])) {
            require_once "./modules/{$r['auth']}.php";
            if (function_exists('auth')) {
                $auth_response = auth($request, $r);
                if ($auth_response) return $auth_response;
            }
        }

        // Переопределение шаблона
        if (isset($r['tpl'])) {
            $template = $r['tpl'];
        }

        // Загрузка модуля
        if (empty($r['module'])) continue;
        require_once "./modules/{$r['module']}.php";

        // Формирование имени функции
        $func = "{$r['module']}_{$method}";
        if (!function_exists($func)) continue;

        // Подготовка параметров
        $params = array('request' => $request);
        if (isset($matches[1])) {
            $params['url_param'] = $matches[1];
        }

        // Вызов обработчика
        $result = call_user_func_array($func, $params);
        
        if (is_array($result)) {
            if (!empty($result['headers'])) {
                return $result;
            }
            $response = array_merge($response, $result);
        } else {
            if (!isset($c['#content'])) {
              $c['#content'] = array();
            }
           $c['#content'][$r['module']] = $result;
        }
    }

    // Формирование ответа
    if (!empty($c)) {
        $c['#request'] = $request;
        $response['entity'] = theme($template, $c);
    } else {
        $response = not_found();
    }

    $response['headers']['Content-Type'] = 'text/html; charset=' . conf('charset');
    return $response;
}

function conf($key) {
    global $conf;
    return $conf[$key] ?? false;
}

function url($addr = '', $params = array()) {
    global $conf;

    if ($addr == '' && isset($_GET['q'])) {
        $addr = strip_tags($_GET['q']);
    }

    $clean = conf('clean_urls');
    $r = $clean ? '/' : '?q=';
    $r = conf('basedir') . ltrim($r . strip_tags($addr), '/');

    if (count($params) > 0) {
        $r .= $clean ? '?' : '&';
        $r .= http_build_query($params);
    }
    return $r;
}

function redirect($location = null, $statusCode = 302) {
    if (is_null($location)) {
        $location = $_SERVER['REQUEST_URI'];
    } else {
        $location = conf('basedir') . $location;
    }
    return [
        'headers' => ['Location' => $location],
        'statusCode' => $statusCode
    ];
}

function access_denied() {
    return [
        'headers' => ['HTTP/1.1 403 Forbidden'],
        'entity' => theme('403')
    ];
}

function not_found() {
    return [
        'headers' => ['HTTP/1.1 404 Not Found'],
        'entity' => theme('404')
    ];
}

function theme($template, $context = array()) {
    $template_path = conf('theme') . '/' . str_replace('/', '_', $template) . '.tpl.php';
    
    if (!file_exists($template_path)) {
        return implode('', $context);
    }

    ob_start();
    extract($context);
    include $template_path;
    return ob_get_clean();
}
