<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/audit.php';

function users_list(): array
{
    return db()->query(
        'SELECT u.*,
                c.name  AS created_by_name,
                up.name AS updated_by_name
         FROM auth_users u
         LEFT JOIN auth_users c  ON c.id  = u.created_by
         LEFT JOIN auth_users up ON up.id = u.updated_by
         ORDER BY (u.email = \'admin\' AND u.role = \'sa\') DESC, u.name ASC'
    )->fetchAll();
}

function user_get(int $id): array|false
{
    $st = db()->prepare(
        'SELECT u.*,
                c.name  AS created_by_name,
                up.name AS updated_by_name
         FROM auth_users u
         LEFT JOIN auth_users c  ON c.id  = u.created_by
         LEFT JOIN auth_users up ON up.id = u.updated_by
         WHERE u.id = :id LIMIT 1'
    );
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
    $by = audit_by();
    $st = db()->prepare(
        'INSERT INTO auth_users (name, email, password, role, sso, is_active, created_by, updated_by)
         VALUES (:name, :email, :password, :role, :sso, 1, :created_by, :updated_by)'
    );
    $st->execute([
        ':name'       => $data['name'],
        ':email'      => $data['email'],
        ':password'   => $data['password'],
        ':role'       => $data['role'],
        ':sso'        => (int) ($data['sso'] ?? 0),
        ':created_by' => $by,
        ':updated_by' => $by,
    ]);
    return (int) db()->lastInsertId();
}

function user_update_name(int $id, string $name): void
{
    $st = db()->prepare(
        'UPDATE auth_users SET name = :name, updated_by = :by WHERE id = :id'
    );
    $st->execute([':name' => $name, ':by' => audit_by(), ':id' => $id]);
}

function user_update_role(int $id, string $role): void
{
    $st = db()->prepare(
        'UPDATE auth_users SET role = :role, updated_by = :by WHERE id = :id'
    );
    $st->execute([':role' => $role, ':by' => audit_by(), ':id' => $id]);
}

function user_toggle_active(int $id): void
{
    $st = db()->prepare(
        'UPDATE auth_users SET is_active = 1 - is_active, updated_by = :by WHERE id = :id'
    );
    $st->execute([':by' => audit_by(), ':id' => $id]);
}

function user_toggle_sso(int $id): void
{
    $st = db()->prepare(
        'UPDATE auth_users SET sso = 1 - sso, updated_by = :by WHERE id = :id'
    );
    $st->execute([':by' => audit_by(), ':id' => $id]);
}

function user_update_password(int $id, string $hash): void
{
    $st = db()->prepare(
        'UPDATE auth_users SET password = :hash, updated_by = :by WHERE id = :id'
    );
    $st->execute([':hash' => $hash, ':by' => audit_by(), ':id' => $id]);
}

function user_delete(int $id): void
{
    $by = audit_by();
    $st = db()->prepare('UPDATE auth_users SET created_by = :by, updated_by = :by WHERE created_by = :id');
    $st->execute([':by' => $by, ':id' => $id]);

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
