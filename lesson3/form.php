<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet"  href="style.css">
    <title>Форма</title>
</head>
<body>
    <form action="index.php" method="POST">
     <h1 style="text-align: center;">ФОРМА</h1>
        <label for="full_name">ФИО:</label>
        <input type="text" id="full_name" name="full_name" required maxlength="150"><br>

        <label for="phone">Телефон:</label>
        <input type="tel" id="phone" name="phone" required><br>

        <label for="email">E-mail:</label>
        <input type="email" id="email" name="email" required><br>

        <label for="birth_date">Дата рождения:</label>
        <input type="date" id="birth_date" name="birth_date" required><br>

        <label>Пол:</label>
        <input type="radio" id="male" name="gender" value="male" required>
        <label for="male">Мужской</label>
        <input type="radio" id="female" name="gender" value="female" required>
        <label for="female">Женский</label><br>

        <label for="languages">Любимый язык программирования:</label>
        <select id="languages" name="languages[]" multiple required>
            <option value="1">Pascal</option>
            <option value="2">C</option>
            <option value="3">C++</option>
            <option value="4">JavaScript</option>
            <option value="5">PHP</option>
            <option value="6">Python</option>
            <option value="7">Java</option>
            <option value="8">Haskell</option>
            <option value="9">Clojure</option>
            <option value="10">Prolog</option>
            <option value="11">Scala</option>
            <option value="12">Go</option>
        </select><br>

        <label for="biography">Биография:</label>
        <textarea id="biography" name="biography" required></textarea><br>

        <input type="checkbox" id="agreement" name="agreement" required>
        <label for="agreement">С контрактом ознакомлен(а)</label><br>

        <input type="submit" value="Сохранить">
    </form>
</body>
</html>
