<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

function users_list(): array
{
    return db()->query(
        'SELECT * FROM auth_users
         ORDER BY (email = \'admin\' AND role = \'sa\') DESC, name ASC'
    )->fetchAll();
}

function user_get(int $id): array|false
{
    $st = db()->prepare('SELECT * FROM auth_users WHERE id = :id LIMIT 1');
    $st->execute([':id' => $id]);
    return $st->fetch();
}

function user_email_exists(string $email, int $exclude_id = 0): bool
{
    $st = db()->prepare(
        'SELECT COUNT(*) FROM auth_users WHERE email = :email AND id != :exclude'
    );
    $st->execute([':email' => $email, ':exclude' => $exclude_id]);
    return (int) $st->fetchColumn() > 0;
}

function user_create(array $data): int
{
    $st = db()->prepare(
        'INSERT INTO auth_users (name, email, password, role, sso, is_active)
         VALUES (:name, :email, :password, :role, :sso, 1)'
    );
    $st->execute([
        ':name'     => $data['name'],
        ':email'    => $data['email'],
        ':password' => $data['password'],
        ':role'     => $data['role'],
        ':sso'      => (int) ($data['sso'] ?? 0),
    ]);
    return (int) db()->lastInsertId();
}

function user_update_name(int $id, string $name): void
{
    $st = db()->prepare('UPDATE auth_users SET name = :name WHERE id = :id');
    $st->execute([':name' => $name, ':id' => $id]);
}

function user_update_role(int $id, string $role): void
{
    $st = db()->prepare('UPDATE auth_users SET role = :role WHERE id = :id');
    $st->execute([':role' => $role, ':id' => $id]);
}

function user_toggle_active(int $id): void
{
    $st = db()->prepare('UPDATE auth_users SET is_active = 1 - is_active WHERE id = :id');
    $st->execute([':id' => $id]);
}

function user_toggle_sso(int $id): void
{
    $st = db()->prepare('UPDATE auth_users SET sso = 1 - sso WHERE id = :id');
    $st->execute([':id' => $id]);
}

function user_update_password(int $id, string $hash): void
{
    $st = db()->prepare('UPDATE auth_users SET password = :hash WHERE id = :id');
    $st->execute([':hash' => $hash, ':id' => $id]);
}

function user_delete(int $id): void
{
    $st = db()->prepare('DELETE FROM auth_users WHERE id = :id');
    $st->execute([':id' => $id]);
}

function user_generate_password(): string
{
    $upper   = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $lower   = 'abcdefghijklmnopqrstuvwxyz';
    $digits  = '0123456789';
    $special = '!@#$%^&*_+-=';

    // Guarantee at least one character from each required class (matches PWD_REGEX)
    $chars  = $upper[random_int(0, strlen($upper) - 1)];
    $chars .= $digits[random_int(0, strlen($digits) - 1)];
    $chars .= $special[random_int(0, strlen($special) - 1)];

    $all = $upper . $lower . $digits . $special;
    for ($i = 3; $i < 10; $i++) {
        $chars .= $all[random_int(0, strlen($all) - 1)];
    }

    // Fisher-Yates shuffle using CSPRNG
    $arr = str_split($chars);
    for ($i = count($arr) - 1; $i > 0; $i--) {
        $j        = random_int(0, $i);
        [$arr[$i], $arr[$j]] = [$arr[$j], $arr[$i]];
    }
    return implode('', $arr);
}
