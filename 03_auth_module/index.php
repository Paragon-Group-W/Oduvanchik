<?php
session_start();
header('Location: ' . (isset($_SESSION['UserID']) ? 'dashboard.php' : 'login.php'));
exit;
