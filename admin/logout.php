<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

setcookie(session_name(), '', time() - 3600, '/');
session_destroy();

header('Location: /admin/login.php');
exit;
