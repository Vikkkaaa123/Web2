<?php

function init($request = array(), $urlconf = array()) {
    global $conf;

    try {
        $db = db_connect();
        if (!$db) {
            throw new Exception('Database connection failed');
        }
    } catch (Exception $e) {
        error_log('Init DB Error: ' . $e->getMessage());
        return [
            'headers' => ['HTTP/1.1 500 Internal Server Error'],
            'entity' => 'Database connection error'
        ];
    }

    $response = array();
    $template = 'page';
    $c = array();

    $q = $request['url'] ?? '';
    $method = strtolower($request['method'] ?? 'get');

    foreach ($urlconf as $url => $r) {
        $matches = array();

        if ($url == '' || $url[0] != '/') {
            if ($url != $q) continue;
        } else {
            if (!preg_match($url, $q, $matches)) continue;
        }

        if (!empty($r['auth'])) {
            require_once "./modules/{$r['auth']}.php";
            if (function_exists('auth')) {
                $auth_response = auth($request, $r);
                if ($auth_response) return $auth_response;
            }
        }

        if (empty($r['module'])) continue;
        require_once "./modules/{$r['module']}.php";

        $func = "{$r['module']}_{$method}";
        if (!function_exists($func)) continue;

        $params = ['request' => $request];
        if (isset($matches[1])) {
            $params['url_param'] = $matches[1];
        }

        $result = call_user_func_array($func, $params);

        if (is_array($result)) {
            if ($request['is_ajax'] ?? false) {
                return [
                    'headers' => ['Content-Type' => 'application/json'],
                    'entity' => $result
                ];
            }

            if (!empty($result['headers'])) {
                return $result;
            }
            $response = array_merge($response, $result);
        } else {
            $c['#content'][$r['module']] = $result;
        }
    }

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

function url($addr = '', $params = []) {
    $clean = conf('clean_urls');
    $r = $clean ? '/' : '?q=';
    $r = conf('basedir') . ltrim($r . strip_tags($addr), '/');
    if (count($params) > 0) {
        $r .= $clean ? '?' : '&';
        $r .= implode('&', $params);
    }
    return $r;
}

function redirect($l = null, $statusCode = 302) {
    $location = is_null($l) ? $_SERVER['REQUEST_URI'] : conf('basedir') . $l;
    return ['headers' => ['Location' => $location], 'statusCode' => $statusCode];
}

function access_denied() {
    return ['headers' => ['HTTP/1.1 403 Forbidden'], 'entity' => theme('403')];
}

function not_found() {
    return ['headers' => ['HTTP/1.1 404 Not Found'], 'entity' => theme('404')];
}

function theme($t, $c = []) {
    $template = conf('theme') . '/' . str_replace('/', '_', $t) . '.tpl.php';
    if (!file_exists($template)) {
        return implode('', $c);
    }
    ob_start();
    extract($c);
    include $template;
    return ob_get_clean();
}
