<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

function auth_config_get(): ?array
{
    $row = db()->query('SELECT * FROM auth_config LIMIT 1')->fetch();
    return $row ?: null;
}

function auth_config_save(array $data): void
{
    $pdo = db();
    $pdo->exec('DELETE FROM auth_config');
    $st = $pdo->prepare(
        'INSERT INTO auth_config
            (label, icon_url, type, issuer_url, client_id, client_secret,
             scopes, redirect_uri, is_active)
         VALUES
            (:label, :icon_url, :type, :issuer_url, :client_id, :client_secret,
             :scopes, :redirect_uri, :is_active)'
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
    ]);
}

function auth_config_clear(): void
{
    db()->exec('DELETE FROM auth_config');
}

function auth_config_toggle(): void
{
    db()->exec('UPDATE auth_config SET is_active = 1 - is_active');
}
