<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db_profile.php';
require_once __DIR__ . '/../config.php';

require_auth();

$user = current_user();

// ---- POST handlers ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        http_response_code(403);
        exit('Invalid CSRF token.');
    }

    $action = $_POST['action'] ?? '';

    // --- Change password ---
    if ($action === 'change_password') {
        $current  = $_POST['current_password'] ?? '';
        $new      = $_POST['new_password']     ?? '';
        $confirm  = $_POST['confirm_password'] ?? '';

        $row = auth_user_find_by_id((int) $user['id']);

        if (!$row || !password_verify($current, (string) $row['password'])) {
            $_SESSION['flash'] = ['type' => 'err', 'msg' => 'Current password is incorrect.'];
        } elseif ($new !== $confirm) {
            $_SESSION['flash'] = ['type' => 'err', 'msg' => 'New passwords do not match.'];
        } elseif (!preg_match(PWD_REGEX, $new)) {
            $_SESSION['flash'] = ['type' => 'err', 'msg' => 'New password must be 8+ chars with uppercase, number, and special character.'];
        } else {
            auth_user_update_password((int) $user['id'], password_hash($new, PASSWORD_BCRYPT));
            $_SESSION['flash'] = ['type' => 'ok', 'msg' => 'Password updated successfully.'];
        }
        header('Location: /admin/profile.php');
        exit;
    }

    // --- Change theme ---
    if ($action === 'theme') {
        $theme = $_POST['theme'] ?? '';
        if (in_array($theme, ['light', 'dark', 'system'], true)) {
            auth_user_update_theme((int) $user['id'], $theme);
            $_SESSION['auth']['theme'] = $theme;
        }
        $back = $_POST['redirect'] ?? '';
        $dest = (str_starts_with($back, '/admin/') && !str_contains($back, '//')) ? $back : '/admin/profile.php';
        header('Location: ' . $dest);
        exit;
    }
}

require __DIR__ . '/_layout.php';
?>

<h2 class="mb-4">Profile</h2>

<div class="row g-4" style="max-width:720px">

  <!-- Change Password -->
  <div class="col-12">
    <div class="card">
      <div class="card-header"><strong>Change Password</strong></div>
      <div class="card-body">
        <form method="post" novalidate>
          <input type="hidden" name="action" value="change_password">
          <input type="hidden" name="csrf"   value="<?= h($_SESSION['csrf']) ?>">

          <div class="mb-3">
            <label for="current_password" class="form-label">Current Password</label>
            <input type="password" id="current_password" name="current_password"
                   class="form-control" autocomplete="current-password" required>
          </div>
          <div class="mb-3">
            <label for="new_password" class="form-label">New Password</label>
            <input type="password" id="new_password" name="new_password"
                   class="form-control" autocomplete="new-password" required>
            <div class="pwd-hint mt-1">Min 8 chars &middot; 1 uppercase &middot; 1 number &middot; 1 special character</div>
          </div>
          <div class="mb-4">
            <label for="confirm_password" class="form-label">Confirm New Password</label>
            <input type="password" id="confirm_password" name="confirm_password"
                   class="form-control" autocomplete="new-password" required>
          </div>
          <button type="submit" class="btn btn-primary">Update Password</button>
        </form>
      </div>
    </div>
  </div>

  <!-- Theme -->
  <div class="col-12">
    <div class="card">
      <div class="card-header"><strong>Theme</strong></div>
      <div class="card-body">
        <form method="post" class="d-flex align-items-center gap-3">
          <input type="hidden" name="action" value="theme">
          <input type="hidden" name="csrf"   value="<?= h($_SESSION['csrf']) ?>">
          <select name="theme" id="theme" class="form-select" style="max-width:200px">
            <?php foreach (['light' => 'Light', 'dark' => 'Dark', 'system' => 'System (OS)'] as $val => $label): ?>
              <option value="<?= h($val) ?>" <?= ($user['theme'] ?? '') === $val ? 'selected' : '' ?>>
                <?= h($label) ?>
              </option>
            <?php endforeach; ?>
          </select>
          <button type="submit" class="btn btn-primary">Save Theme</button>
        </form>
      </div>
    </div>
  </div>

</div>

<?php require __DIR__ . '/_layout_end.php'; ?>
