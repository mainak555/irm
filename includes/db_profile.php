<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

function auth_user_find_by_id(int $id): ?array
{
    $st = db()->prepare('SELECT * FROM auth_users WHERE id = :id LIMIT 1');
    $st->execute([':id' => $id]);
    $row = $st->fetch();
    return $row ?: null;
}

function auth_user_update_password(int $id, string $hash): void
{
    $st = db()->prepare('UPDATE auth_users SET password = :p WHERE id = :id');
    $st->execute([':p' => $hash, ':id' => $id]);
}

function auth_user_update_theme(int $id, string $theme): void
{
    $st = db()->prepare('UPDATE auth_users SET theme = :t WHERE id = :id');
    $st->execute([':t' => $theme, ':id' => $id]);
}
