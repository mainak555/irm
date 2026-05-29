<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db_auth_config.php';

$config = auth_config_get();

if ($config === null || !(int) $config['is_active']) {
    $_SESSION['flash'] = ['type' => 'err', 'msg' => 'No active authentication provider is configured.'];
    header('Location: /admin/login.php');
    exit;
}

// Strip full discovery URL if someone pasted it — normalize to issuer base, then append suffix.
$issuer_base   = rtrim(preg_replace('#/\.well-known/openid-configuration$#', '', rtrim($config['issuer_url'], '/')), '/');
$discovery_url = $issuer_base . '/.well-known/openid-configuration';

$ctx = stream_context_create([
    'http' => ['timeout' => 8, 'ignore_errors' => true],
    'ssl'  => ['verify_peer' => false, 'verify_peer_name' => false],
]);
$raw = @file_get_contents($discovery_url, false, $ctx);
if ($raw === false) {
    error_log('[OIDC] fetch_failed: ' . json_encode(error_get_last()));
    $_SESSION['flash'] = ['type' => 'err', 'msg' => 'Could not fetch OIDC discovery document. Check the Issuer URL.'];
    header('Location: /admin/login.php');
    exit;
}

$discovery = json_decode($raw, true);
if (
    !is_array($discovery)
    || empty($discovery['authorization_endpoint'])
    || empty($discovery['token_endpoint'])
    || empty($discovery['issuer'])
) {
    $_SESSION['flash'] = ['type' => 'err', 'msg' => 'Invalid OIDC discovery document.'];
    header('Location: /admin/login.php');
    exit;
}

$state = bin2hex(random_bytes(16));
$nonce = bin2hex(random_bytes(16));

// PKCE — only when provider advertises S256 support
$pkce_supported = in_array('S256', $discovery['code_challenge_methods_supported'] ?? [], true);
$verifier  = $pkce_supported ? rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=') : '';
$challenge = $pkce_supported ? rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=') : '';

$redirect_uri = $config['redirect_uri'] ?: null;
if ($redirect_uri === null) {
    $scheme       = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $redirect_uri = $scheme . '://' . $_SERVER['HTTP_HOST'] . '/admin/auth/callback.php';
}

$_SESSION['oidc'] = [
    'state'          => $state,
    'nonce'          => $nonce,
    'code_verifier'  => $verifier,
    'redirect_uri'   => $redirect_uri,
    'token_endpoint' => $discovery['token_endpoint'],
    'issuer'         => $discovery['issuer'],
];

$params = [
    'response_type' => 'code',
    'client_id'     => $config['client_id'],
    'redirect_uri'  => $redirect_uri,
    'scope'         => $config['scopes'],
    'state'         => $state,
    'nonce'         => $nonce,
];
if ($pkce_supported) {
    $params['code_challenge_method'] = 'S256';
    $params['code_challenge']        = $challenge;
}

header('Location: ' . $discovery['authorization_endpoint'] . '?' . http_build_query($params));
exit;
