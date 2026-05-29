<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

function auth_user_count(): int
{
    return (int) db()->query('SELECT COUNT(*) FROM auth_users')->fetchColumn();
}

function auth_user_find_by_username(string $username): ?array
{
    $st = db()->prepare('SELECT * FROM auth_users WHERE username = :username LIMIT 1');
    $st->execute([':username' => $username]);
    $row = $st->fetch();
    return $row ?: null;
}

function auth_user_create_sa(string $password_hash): void
{
    $st = db()->prepare(
        'INSERT INTO auth_users (username, name, role, password) VALUES (:u, :n, :r, :p)'
    );
    $st->execute([
        ':u' => 'admin',
        ':n' => 'Administrator',
        ':r' => 'sa',
        ':p' => $password_hash,
    ]);
}

function auth_user_find_by_email(string $email): ?array
{
    $st = db()->prepare('SELECT * FROM auth_users WHERE email = :email LIMIT 1');
    $st->execute([':email' => $email]);
    $row = $st->fetch();
    return $row ?: null;
}

function auth_config_active(): ?array
{
    $st = db()->prepare('SELECT * FROM auth_config WHERE is_active = 1 LIMIT 1');
    $st->execute();
    $row = $st->fetch();
    return $row ?: null;
}
