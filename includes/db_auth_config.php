<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/audit.php';

function auth_config_get(): ?array
{
    $row = db()->query(
        'SELECT ac.*,
                c.name  AS created_by_name,
                up.name AS updated_by_name
         FROM auth_config ac
         LEFT JOIN auth_users c  ON c.id  = ac.created_by
         LEFT JOIN auth_users up ON up.id = ac.updated_by
         LIMIT 1'
    )->fetch();
    return $row ?: null;
}

function auth_config_save(array $data): void
{
    $pdo      = db();
    $existing = auth_config_get();
    $by       = audit_by();

    // Preserve original creator when replacing the singleton row
    $created_by = $existing ? ($existing['created_by'] ?? $by) : $by;

    $pdo->exec('DELETE FROM auth_config');
    $st = $pdo->prepare(
        'INSERT INTO auth_config
            (label, icon_url, type, issuer_url, client_id, client_secret,
             scopes, redirect_uri, is_active, created_by, updated_by)
         VALUES
            (:label, :icon_url, :type, :issuer_url, :client_id, :client_secret,
             :scopes, :redirect_uri, :is_active, :created_by, :updated_by)'
    );
    $st->execute([
        ':label'        => $data['label'],
        ':icon_url'     => $data['icon_url']     ?? null,
        ':type'         => $data['type'],
        ':issuer_url'   => $data['issuer_url'],
        ':client_id'    => $data['client_id'],
        ':client_secret'=> $data['client_secret'],
        ':scopes'       => $data['scopes'] ?: 'openid email profile',
        ':redirect_uri' => $data['redirect_uri'] ?? null,
        ':is_active'    => (int) ($data['is_active'] ?? 0),
        ':created_by'   => $created_by,
        ':updated_by'   => $by,
    ]);
}

function auth_config_clear(): void
{
    db()->exec('DELETE FROM auth_config');
}

function auth_config_toggle(): void
{
    $st = db()->prepare(
        'UPDATE auth_config SET is_active = 1 - is_active, updated_by = :by'
    );
    $st->execute([':by' => audit_by()]);
}
