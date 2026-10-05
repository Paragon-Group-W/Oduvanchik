<?php
session_start();
require_once __DIR__ . '/db.php';

if (isset($_SESSION['UserID'])) {
    header('Location: dashboard.php');
    exit;
}

const MAX_FAILED_ATTEMPTS = 3;
const INACTIVITY_LIMIT_DAYS = 30;

$errorMessage = '';
$successMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userName = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($userName === '' || $password === '') {
        $errorMessage = 'Поля "Логин" и "Пароль" обязательны для заполнения.';
    } else {
        $db = getConnection();
        $stmt = $db->prepare('SELECT * FROM Users WHERE UserName = ?');
        $stmt->bind_param('s', $userName);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if ($user === null) {
            // Логина не существует вовсе - блокировать нечего, просто общий текст ошибки
            $errorMessage = 'Вы ввели неверный логин или пароль. Пожалуйста проверьте ещё раз введенные данные';
        } else {
            // Ленивая проверка блокировки по неактивности: если с последнего входа
            // прошло больше месяца - блокируем учётную запись прямо сейчас
            if (!$user['Blocked'] && $user['LastLoginDate'] !== null) {
                $daysSinceLogin = (strtotime('now') - strtotime($user['LastLoginDate'])) / 86400;
                if ($daysSinceLogin > INACTIVITY_LIMIT_DAYS) {
                    $block = $db->prepare('UPDATE Users SET Blocked = 1 WHERE UserID = ?');
                    $block->bind_param('i', $user['UserID']);
                    $block->execute();
                    $user['Blocked'] = 1;
                }
            }

            if ($user['Blocked']) {
                $errorMessage = 'Вы заблокированы. Обратитесь к администратору.';
            } elseif (password_verify($password, $user['PasswordHash'])) {
                // Успешный вход: сбрасываем счётчик попыток, фиксируем дату входа
                $update = $db->prepare('UPDATE Users SET FailedAttempts = 0, LastLoginDate = NOW() WHERE UserID = ?');
                $update->bind_param('i', $user['UserID']);
                $update->execute();

                $_SESSION['UserID'] = $user['UserID'];
                $_SESSION['UserName'] = $user['UserName'];
                $_SESSION['RoleID'] = (int)$user['RoleID'];

                if (!$user['PasswordChanged']) {
                    header('Location: change_password.php');
                    exit;
                }

                $_SESSION['flash_success'] = 'Вы успешно авторизовались';
                header('Location: dashboard.php');
                exit;
            } else {
                $attempts = (int)$user['FailedAttempts'] + 1;
                if ($attempts >= MAX_FAILED_ATTEMPTS) {
                    $update = $db->prepare('UPDATE Users SET FailedAttempts = ?, Blocked = 1 WHERE UserID = ?');
                    $update->bind_param('ii', $attempts, $user['UserID']);
                    $update->execute();
                    $errorMessage = 'Вы заблокированы. Обратитесь к администратору.';
                } else {
                    $update = $db->prepare('UPDATE Users SET FailedAttempts = ? WHERE UserID = ?');
                    $update->bind_param('ii', $attempts, $user['UserID']);
                    $update->execute();
                    $errorMessage = 'Вы ввели неверный логин или пароль. Пожалуйста проверьте ещё раз введенные данные';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Alpha Hotel - Вход</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="card">
    <h1>Alpha Hotel - Вход в систему</h1>

    <?php if ($errorMessage !== ''): ?>
        <div class="message-error"><?= htmlspecialchars($errorMessage) ?></div>
    <?php endif; ?>

    <form method="post" action="login.php">
        <label for="username">Логин</label>
        <input type="text" id="username" name="username" required>

        <label for="password">Пароль</label>
        <input type="password" id="password" name="password" required>

        <button type="submit">Войти</button>
    </form>
</div>
</body>
</html>
