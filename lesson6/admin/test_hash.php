<?php
$hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
$password = '123';

echo "Проверка пароля '123': " . (password_verify($password, $hash) ? 'OK' : 'ОШИБКА');
echo "<br>Информация о хеше: ";
print_r(password_get_info($hash));
