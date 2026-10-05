<?php
session_start();
require_once __DIR__ . '/db.php';

if (!isset($_SESSION['UserID'])) {
    header('Location: login.php');
    exit;
}

$db = getConnection();
$errorMessage = '';
$successMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
        $errorMessage = 'Все поля обязательны для заполнения.';
    } else {
        $stmt = $db->prepare('SELECT PasswordHash FROM Users WHERE UserID = ?');
        $stmt->bind_param('i', $_SESSION['UserID']);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if (!password_verify($currentPassword, $user['PasswordHash'])) {
            $errorMessage = 'Текущий пароль указан неверно.';
        } elseif ($newPassword !== $confirmPassword) {
            $errorMessage = 'Новый пароль и подтверждение не совпадают.';
        } else {
            $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $update = $db->prepare('UPDATE Users SET PasswordHash = ?, PasswordChanged = 1 WHERE UserID = ?');
            $update->bind_param('si', $newHash, $_SESSION['UserID']);
            $update->execute();
            $successMessage = 'Пароль успешно изменён.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Alpha Hotel - Смена пароля</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="card">
    <h1>Смена пароля</h1>
    <p>Это ваш первый вход - необходимо задать новый пароль.</p>

    <?php if ($errorMessage !== ''): ?>
        <div class="message-error"><?= htmlspecialchars($errorMessage) ?></div>
    <?php endif; ?>
    <?php if ($successMessage !== ''): ?>
        <div class="message-success"><?= htmlspecialchars($successMessage) ?></div>
        <p><a href="dashboard.php">Перейти в систему</a></p>
    <?php else: ?>
        <form method="post" action="change_password.php">
            <label for="current_password">Текущий пароль</label>
            <input type="password" id="current_password" name="current_password" required>

            <label for="new_password">Новый пароль</label>
            <input type="password" id="new_password" name="new_password" required>

            <label for="confirm_password">Подтверждение нового пароля</label>
            <input type="password" id="confirm_password" name="confirm_password" required>

            <button type="submit">Изменить пароль</button>
        </form>
    <?php endif; ?>
</div>
</body>
</html>
