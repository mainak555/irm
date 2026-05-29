<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('PWD_REGEX', '/^(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/');

function require_auth(string ...$roles): void
{
    if (empty($_SESSION['auth'])) {
        $_SESSION['login_redirect'] = $_SERVER['REQUEST_URI'];
        header('Location: /admin/login.php');
        exit;
    }
    if ($roles && !in_array($_SESSION['auth']['role'], $roles, true)) {
        http_response_code(403);
        require __DIR__ . '/../admin/403.php';
        exit;
    }
}

function current_user(): ?array
{
    return $_SESSION['auth'] ?? null;
}
