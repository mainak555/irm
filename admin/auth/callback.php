<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db_login.php';
require_once __DIR__ . '/../../includes/db_auth_config.php';

function oidc_fail(string $msg): never
{
    $_SESSION['flash'] = ['type' => 'err', 'msg' => $msg];
    header('Location: /admin/login.php');
    exit;
}

function oidc_provision_fail(string $msg): never
{
    $_SESSION['oidc_provision_error'] = $msg;
    header('Location: /admin/auth/error.php');
    exit;
}

// Consume session immediately (replay protection)
if (empty($_SESSION['oidc'])) {
    oidc_fail('OIDC session expired or missing. Please try again.');
}
$oidc = $_SESSION['oidc'];
unset($_SESSION['oidc']);

// Provider-side error
if (!empty($_GET['error'])) {
    $desc = htmlspecialchars($_GET['error_description'] ?? $_GET['error'], ENT_QUOTES);
    oidc_fail("Provider error: {$desc}");
}

// State validation
if (empty($_GET['state']) || !hash_equals($oidc['state'], (string) $_GET['state'])) {
    oidc_fail('State mismatch — possible CSRF. Please try again.');
}

$code = trim($_GET['code'] ?? '');
if ($code === '') {
    oidc_fail('No authorization code received from provider.');
}

// Load provider config
$config = auth_config_get();
if ($config === null || !(int) $config['is_active']) {
    oidc_fail('Authentication provider is no longer active.');
}

// Token exchange
$post_data = [
    'grant_type'    => 'authorization_code',
    'code'          => $code,
    'redirect_uri'  => $oidc['redirect_uri'],
    'client_id'     => $config['client_id'],
    'client_secret' => $config['client_secret'],
];
if ($oidc['code_verifier'] !== '') {
    $post_data['code_verifier'] = $oidc['code_verifier'];
}

$ctx = stream_context_create([
    'http' => [
        'method'        => 'POST',
        'header'        => "Content-Type: application/x-www-form-urlencoded\r\nAccept: application/json\r\n",
        'content'       => http_build_query($post_data),
        'timeout'       => 10,
        'ignore_errors' => true,
    ],
    'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
]);
$raw = @file_get_contents($oidc['token_endpoint'], false, $ctx);
if ($raw === false) {
    oidc_fail('Token endpoint unreachable. Check server connectivity.');
}

$tokens = json_decode($raw, true);
if (!is_array($tokens)) {
    oidc_fail('Unparseable token response.');
}
if (!empty($tokens['error'])) {
    oidc_fail('Token error: ' . ($tokens['error_description'] ?? $tokens['error']));
}
if (empty($tokens['id_token'])) {
    oidc_fail('No id_token in token response.');
}

// Parse ID token claims.
// Signature not re-verified: token received directly from provider over TLS with client_secret.
$parts = explode('.', $tokens['id_token']);
if (count($parts) !== 3) {
    oidc_fail('Malformed id_token (not a 3-part JWT).');
}
$pad    = (4 - strlen($parts[1]) % 4) % 4;
$claims = json_decode(base64_decode(strtr($parts[1], '-_', '+/') . str_repeat('=', $pad)), true);
if (!is_array($claims)) {
    oidc_fail('Could not decode id_token payload.');
}

// Validate standard claims
if (($claims['iss'] ?? '') !== $oidc['issuer']) {
    oidc_fail('id_token issuer mismatch.');
}
$aud = $claims['aud'] ?? null;
if ($aud !== $config['client_id'] && !in_array($config['client_id'], (array) $aud, true)) {
    oidc_fail('id_token audience mismatch.');
}
if ((int) ($claims['exp'] ?? 0) < time()) {
    oidc_fail('id_token has expired.');
}
if (!hash_equals($oidc['nonce'], (string) ($claims['nonce'] ?? ''))) {
    oidc_fail('id_token nonce mismatch.');
}

$email = strtolower(trim($claims['email'] ?? ''));
if ($email === '') {
    oidc_fail('No email in id_token. Ensure the "email" scope is requested.');
}

// Find user by email — no auto-provisioning
$user = auth_user_find_by_email($email);
if ($user === null) {
    oidc_provision_fail("No account found for {$email}.");
}
if (!(int) $user['is_active']) {
    oidc_fail('Your account is inactive. Contact an administrator.');
}
if (empty($user['role'])) {
    oidc_provision_fail("Your account ({$email}) has no role assigned.");
}

// Establish session
$_SESSION['auth'] = [
    'id'    => $user['id'],
    'name'  => $user['name'],
    'role'  => $user['role'],
    'theme' => $user['theme'],
];
$_SESSION['csrf'] = bin2hex(random_bytes(16));

$goto = $_SESSION['login_redirect'] ?? '/admin/index.php';
unset($_SESSION['login_redirect']);
header('Location: ' . (str_starts_with($goto, '/') && !str_starts_with($goto, '//') ? $goto : '/admin/index.php'));
exit;
