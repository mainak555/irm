<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db_users.php';
require_once __DIR__ . '/../config.php';

require_auth('sa', 'admin');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'msg' => 'Method not allowed.']);
    exit;
}

if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'msg' => 'Invalid CSRF token.']);
    exit;
}

$action = $_POST['action'] ?? '';
$id     = (int) ($_POST['id'] ?? 0);

function ajax_ok(array $extra = []): void
{
    echo json_encode(array_merge(['ok' => true], $extra));
    exit;
}

function ajax_err(string $msg, int $code = 400): void
{
    http_response_code($code);
    echo json_encode(['ok' => false, 'msg' => $msg]);
    exit;
}

function guard_target(int $id): array
{
    if ($id <= 0) ajax_err('Invalid user ID.');
    $target = user_get($id);
    if (!$target) ajax_err('User not found.', 404);
    if ($target['email'] === 'admin' && $target['role'] === 'sa') ajax_err('The SA account cannot be modified.', 403);
    $me = current_user();
    if ($me && (int) $me['id'] === $id) ajax_err('You cannot modify your own account from this page.', 403);
    return $target;
}

switch ($action) {

    case 'add_user':
        $name  = trim($_POST['name']  ?? '');
        $email = trim($_POST['email'] ?? '');
        $role  = $_POST['role']  ?? 'user';
        $sso   = (int) ($_POST['sso'] ?? 0);
        $pwd   = $_POST['password'] ?? '';

        if ($name === '') ajax_err('Name is required.');
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            ajax_err('A valid email address is required.');
        }
        if (!in_array($role, ['sa', 'admin', 'faculty', 'user'], true)) {
            ajax_err('Invalid role selected.');
        }
        if (user_email_exists($email)) ajax_err('That email address is already in use.');
        if (!$sso && !preg_match(PWD_REGEX, $pwd)) {
            ajax_err('Password must be 8+ characters with uppercase, number, and special character.');
        }
        user_create([
            'name'     => $name,
            'email'    => $email,
            'password' => $sso ? null : password_hash($pwd, PASSWORD_BCRYPT),
            'role'     => $role,
            'sso'      => $sso,
        ]);
        ajax_ok();
        break;

    case 'toggle_active':
        guard_target($id);
        user_toggle_active($id);
        ajax_ok();
        break;

    case 'update_role':
        guard_target($id);
        $role = $_POST['role'] ?? '';
        if (!in_array($role, ['sa', 'admin', 'faculty', 'user'], true)) {
            ajax_err('Invalid role value.');
        }
        $me = current_user();
        if ($me['role'] !== 'sa' && $role === 'sa') {
            ajax_err('Only a Super Admin can assign the SA role.', 403);
        }
        user_update_role($id, $role);
        ajax_ok();
        break;

    case 'toggle_sso':
        guard_target($id);
        user_toggle_sso($id);
        ajax_ok();
        break;

    case 'reset_password':
        $target = guard_target($id);
        if ((int) $target['sso'] === 1) {
            ajax_err('Cannot reset password for SSO users.');
        }
        $pwd = user_generate_password();
        user_update_password($id, password_hash($pwd, PASSWORD_BCRYPT));
        ajax_ok(['password' => $pwd]);
        break;

    case 'delete':
        guard_target($id);
        user_delete($id);
        ajax_ok();
        break;

    case 'update_name':
        guard_target($id);
        $name = trim($_POST['name'] ?? '');
        if ($name === '') ajax_err('Name cannot be empty.');
        user_update_name($id, $name);
        ajax_ok();
        break;

    default:
        ajax_err('Unknown action.', 400);
}
