<?php
session_start();
if (!isset($_SESSION['UserID'])) {
    header('Location: login.php');
    exit;
}
$successMessage = $_SESSION['flash_success'] ?? '';
unset($_SESSION['flash_success']);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Alpha Hotel - Главная</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="card">
    <h1>Добро пожаловать, <?= htmlspecialchars($_SESSION['UserName']) ?></h1>

    <?php if ($successMessage !== ''): ?>
        <div class="message-success"><?= htmlspecialchars($successMessage) ?></div>
    <?php endif; ?>

    <p>Роль: <?= $_SESSION['RoleID'] == 1 ? 'Администратор' : 'Пользователь' ?></p>

    <?php if ($_SESSION['RoleID'] == 1): ?>
        <p><a href="admin_users.php">Управление пользователями</a></p>
    <?php endif; ?>

    <p><a href="logout.php">Выйти</a></p>
</div>
</body>
</html>
