<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db_login.php';

// Already logged in → redirect to dashboard
if (!empty($_SESSION['auth'])) {
    header('Location: /admin/index.php');
    exit;
}

$user_count = auth_user_count();
$is_setup   = ($user_count === 0);
$error      = '';
$success    = '';

// ---- CSRF token ----
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
}

// ---- POST handler ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // CSRF check
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        http_response_code(403);
        exit('Invalid CSRF token.');
    }

    $action = $_POST['action'] ?? '';

    // --- First-launch setup ---
    if ($is_setup && $action === 'setup') {
        $pwd     = $_POST['password']         ?? '';
        $confirm = $_POST['password_confirm'] ?? '';

        if ($pwd !== $confirm) {
            $error = 'Passwords do not match.';
        } elseif (!preg_match(PWD_REGEX, $pwd)) {
            $error = 'Password must be at least 8 characters and include an uppercase letter, a number, and a special character.';
        } else {
            auth_user_create_sa(password_hash($pwd, PASSWORD_BCRYPT));
            $_SESSION['csrf'] = bin2hex(random_bytes(16));
            $_SESSION['flash'] = ['type' => 'ok', 'msg' => 'Admin account created. Please log in.'];
            header('Location: /admin/login.php');
            exit;
        }
    }

    // --- Login ---
    if (!$is_setup && $action === 'login') {
        $identifier = trim($_POST['email'] ?? '');
        $password   = $_POST['password'] ?? '';

        $user = auth_user_find_by_email($identifier);

        if (
            $user === null
            || empty($user['password'])
            || !password_verify($password, $user['password'])
            || !$user['is_active']
        ) {
            $error = 'Invalid username or password, or account is inactive.';
        } else {
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
        }
    }
}

$oidc = $is_setup ? null : auth_config_active();
$school_title = cfg('school.title') ?: 'IRM';
$logo_url     = cfg('school.logoUrl') ?: '/assets/img/logo.png';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= h($school_title) ?> &mdash; Admin <?= $is_setup ? 'Setup' : 'Login' ?></title>
<script>(function(){var t=window.matchMedia('(prefers-color-scheme: dark)').matches?'dark':'light';document.documentElement.setAttribute('data-bs-theme',t);})();</script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="/admin/style.css">
</head>
<body class="grain-texture auth-page-bg">

<div class="auth-card w-100 px-3">

  <?php if ($is_setup): ?>
  <!-- ===== FIRST-LAUNCH SETUP ===== -->
  <div class="card shadow-sm">
    <div class="card-body p-4">
      <div class="text-center mb-3">
        <img src="<?= h($logo_url) ?>" alt="Logo" class="auth-logo">
      </div>
      <h4 class="card-title mb-1">Initial Setup</h4>
      <p class="text-muted small mb-4">Create the administrator password to get started.</p>

      <?php if ($error): ?>
        <div class="alert alert-danger py-2"><?= h($error) ?></div>
      <?php endif; ?>

      <form method="post" novalidate>
        <input type="hidden" name="action" value="setup">
        <input type="hidden" name="csrf"   value="<?= h($_SESSION['csrf']) ?>">

        <div class="mb-3">
          <label class="form-label">Username</label>
          <input type="text" class="form-control" value="admin" disabled readonly>
        </div>

        <div class="mb-3">
          <label for="password" class="form-label">Password</label>
          <input type="password" id="password" name="password" class="form-control"
                 autocomplete="new-password" required>
          <div class="pwd-hint mt-1">Min 8 chars &middot; 1 uppercase &middot; 1 number &middot; 1 special character</div>
        </div>

        <div class="mb-4">
          <label for="password_confirm" class="form-label">Confirm Password</label>
          <input type="password" id="password_confirm" name="password_confirm"
                 class="form-control" autocomplete="new-password" required>
        </div>

        <button type="submit" class="btn btn-primary w-100">Create Admin Account</button>
      </form>
    </div>
  </div>

  <?php else: ?>
  <!-- ===== LOGIN ===== -->
  <div class="card shadow-sm">
    <div class="card-body p-4">
      <div class="text-center mb-3">
        <img src="<?= h($logo_url) ?>" alt="Logo" class="auth-logo">
      </div>
      <h4 class="card-title mb-1 text-center"><?= h($school_title) ?></h4>
      <p class="text-muted small mb-4 text-center">Admin Panel</p>

      <?php if ($error): ?>
        <div class="alert alert-danger py-2"><?= h($error) ?></div>
      <?php endif; ?>
      <?php if (!empty($_SESSION['flash'])): ?>
        <?php $f = $_SESSION['flash']; unset($_SESSION['flash']); ?>
        <div class="alert alert-<?= $f['type'] === 'ok' ? 'success' : 'danger' ?> py-2"><?= h($f['msg']) ?></div>
      <?php endif; ?>

      <form method="post" novalidate>
        <input type="hidden" name="action" value="login">
        <input type="hidden" name="csrf"   value="<?= h($_SESSION['csrf']) ?>">

        <div class="mb-3">
          <label for="email" class="form-label">Email / Username</label>
          <input type="text" id="email" name="email" class="form-control"
                 autocomplete="username" required autofocus
                 value="<?= h($_POST['email'] ?? '') ?>">
        </div>

        <div class="mb-4">
          <label for="password" class="form-label">Password</label>
          <input type="password" id="password" name="password" class="form-control"
                 autocomplete="current-password" required>
        </div>

        <button type="submit" class="btn btn-primary w-100">Sign In</button>
      </form>

      <?php if ($oidc): ?>
      <div class="my-3 text-center text-muted">or</div>
      <a href="/admin/auth/redirect.php" class="btn btn-outline-secondary w-100 d-flex align-items-center justify-content-center gap-2">
        <?php if ($oidc['icon_url']): ?>
          <img src="<?= h($oidc['icon_url']) ?>" alt="" width="20" height="20">
        <?php endif; ?>
        <?= h($oidc['label']) ?>
      </a>
      <?php endif; ?>

    </div>
  </div>
  <?php endif; ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
