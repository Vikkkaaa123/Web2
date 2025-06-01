<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Добавьте в самое начало
if (headers_sent()) {
    die('Ошибка: заголовки уже отправлены в ' . __FILE__);
}

function checkAdminAuth() {
    if (empty($_SESSION['login']) || empty($_SESSION['admin']) || $_SESSION['admin'] !== true) {
        // Очищаем буфер перед редиректом
        if (ob_get_level() > 0) ob_clean();
        header('Location: login.php?error=' . urlencode('Доступ запрещен'));
        exit;
    }
}




/*<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


function checkAdminAuth() {
    if (empty($_SESSION['login']) || empty($_SESSION['admin']) || $_SESSION['admin'] !== true) {
        // Не админ — редирект на логин
        header('Location: login.php?error=' . urlencode('Доступ запрещен'));
        exit;
    }
}

*/
