<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db_auth_config.php';
require_once __DIR__ . '/../config.php';

require_auth('sa');

// ---- POST handlers ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        http_response_code(403);
        exit('Invalid CSRF token.');
    }

    $action = $_POST['action'] ?? '';

    // --- Save config ---
    if ($action === 'save') {
        // Allow blank client_secret when editing (keep existing); required only on first save
        $existing_secret = auth_config_get()['client_secret'] ?? null;
        $client_secret   = $_POST['client_secret'] ?: $existing_secret;

        $required_fields = ['type', 'issuer_url', 'client_id'];
        $missing = array_filter($required_fields, fn($f) => empty($_POST[$f]));
        if (!$client_secret) {
            $missing[] = 'client_secret';
        }

        if ($missing) {
            $_SESSION['flash'] = ['type' => 'err', 'msg' => 'All required fields must be filled in.'];
        } else {
            $icon_url = trim($_POST['icon_url'] ?? '');
            auth_config_save([
                'client_secret' => $client_secret,
                'label'         => $_POST['label']        ?: 'Login with SSO',
                'icon_url'      => '' === $icon_url ? null : $icon_url,
                'type'          => $_POST['type'],
                'issuer_url'    => $_POST['issuer_url'],
                'client_id'     => $_POST['client_id'],
                'scopes'        => $_POST['scopes']       ?: 'openid email profile',
                'redirect_uri'  => $_POST['redirect_uri'] ?: null,
                'is_active'     => isset($_POST['is_active']) ? 1 : 0,
            ]);
            $_SESSION['flash'] = ['type' => 'ok', 'msg' => 'Provider configuration saved.'];
        }
        header('Location: /admin/auth_config.php');
        exit;
    }

    // --- Clear config ---
    if ($action === 'clear') {
        auth_config_clear();
        $_SESSION['flash'] = ['type' => 'ok', 'msg' => 'Provider configuration cleared.'];
        header('Location: /admin/auth_config.php');
        exit;
    }

    // --- Toggle active ---
    if ($action === 'toggle') {
        $current = auth_config_get();
        if ($current) {
            auth_config_toggle();
            $new_state = $current['is_active'] ? 'inactive' : 'active';
            $_SESSION['flash'] = ['type' => 'ok', 'msg' => "Provider is now {$new_state}."];
        }
        header('Location: /admin/auth_config.php');
        exit;
    }
}

$config = auth_config_get();

require __DIR__ . '/_layout.php';
?>

<div class="d-flex align-items-center justify-content-between mb-4">
  <h2 class="mb-0">Auth Config</h2>
  <?php if ($config): ?>
    <div class="d-flex gap-2">
      <form method="post" class="d-inline">
        <input type="hidden" name="action" value="toggle">
        <input type="hidden" name="csrf"   value="<?= h($_SESSION['csrf']) ?>">
        <button type="submit" class="btn btn-sm btn-<?= $config['is_active'] ? 'warning' : 'success' ?>">
          <?= $config['is_active'] ? 'Deactivate' : 'Activate' ?>
        </button>
      </form>
      <form method="post" class="d-inline"
            onsubmit="return confirm('Clear all OIDC/SAML configuration?')">
        <input type="hidden" name="action" value="clear">
        <input type="hidden" name="csrf"   value="<?= h($_SESSION['csrf']) ?>">
        <button type="submit" class="btn btn-sm btn-danger">Clear Config</button>
      </form>
    </div>
  <?php endif; ?>
</div>

<?php if ($config): ?>
  <p class="text-muted small mb-3">
    Status:
    <span class="badge <?= $config['is_active'] ? 'bg-success' : 'bg-secondary' ?>">
      <?= $config['is_active'] ? 'Active' : 'Inactive' ?>
    </span>
  </p>
<?php endif; ?>

<div class="card" style="max-width:640px">
  <div class="card-body">
    <form method="post" novalidate>
      <input type="hidden" name="action" value="save">
      <input type="hidden" name="csrf"   value="<?= h($_SESSION['csrf']) ?>">

      <div class="row g-3">

        <div class="col-12">
          <label class="form-label d-block mb-2">Type <span class="text-danger">*</span></label>
          <?php $selected_type = $config['type'] ?? 'OIDC'; ?>
          <?php foreach (['OIDC' => 'OpenID Connect (OIDC)', 'SAML' => 'SAML 2.0'] as $val => $label): ?>
          <div class="form-check form-check-inline">
            <input class="form-check-input" type="radio" name="type" id="type_<?= h(strtolower($val)) ?>"
                   value="<?= h($val) ?>" <?= $selected_type === $val ? 'checked' : '' ?> required>
            <label class="form-check-label" for="type_<?= h(strtolower($val)) ?>"><?= h($label) ?></label>
          </div>
          <?php endforeach; ?>
        </div>

        <div class="col-12">
          <label for="label" class="form-label">Button Label</label>
          <input type="text" id="label" name="label" class="form-control"
                 placeholder="Login with SSO"
                 value="<?= h($config['label'] ?? '') ?>">
        </div>

        <div class="col-12">
          <label for="icon_url" class="form-label">Button Icon URL</label>
          <input type="url" id="icon_url" name="icon_url" class="form-control"
                 placeholder="https://example.com/icon.svg"
                 value="<?= h($config['icon_url'] ?? '') ?>">
          <div class="form-text">Optional. Icon displayed to the left of the button label on the login page.</div>
        </div>

        <div class="col-12">
          <label for="issuer_url" class="form-label">Issuer URL <span class="text-danger">*</span></label>
          <input type="url" id="issuer_url" name="issuer_url" class="form-control" required
                 placeholder="https://accounts.google.com"
                 value="<?= h($config['issuer_url'] ?? '') ?>">
          <div class="form-text">Base URL of the OIDC provider. The discovery document is fetched automatically.</div>
        </div>

        <div class="col-12">
          <label for="client_id" class="form-label">Client ID <span class="text-danger">*</span></label>
          <input type="text" id="client_id" name="client_id" class="form-control" required
                 value="<?= h($config['client_id'] ?? '') ?>">
        </div>

        <div class="col-12">
          <label for="client_secret" class="form-label">Client Secret <span class="text-danger">*</span></label>
          <input type="password" id="client_secret" name="client_secret" class="form-control" required
                 placeholder="<?= $config ? '••••••••  (enter new value to update)' : '' ?>"
                 autocomplete="off">
          <?php if ($config): ?>
            <div class="form-text">Leave blank to keep the existing secret.</div>
          <?php endif; ?>
        </div>

        <div class="col-12">
          <label for="scopes" class="form-label">Scopes</label>
          <input type="text" id="scopes" name="scopes" class="form-control"
                 value="<?= h($config['scopes'] ?? 'openid email profile') ?>">
        </div>

        <div class="col-12">
          <label for="redirect_uri" class="form-label">Redirect URI Override</label>
          <input type="url" id="redirect_uri" name="redirect_uri" class="form-control"
                 placeholder="Leave blank to use default"
                 value="<?= h($config['redirect_uri'] ?? '') ?>">
        </div>

        <div class="col-12">
          <div class="form-check">
            <input type="checkbox" id="is_active" name="is_active"
                   class="form-check-input" value="1"
                   <?= ($config['is_active'] ?? 0) ? 'checked' : '' ?>>
            <label for="is_active" class="form-check-label">Active</label>
          </div>
        </div>

      </div><!-- /.row -->

      <hr class="mt-4">
      <button type="submit" class="btn btn-primary">
        <?= $config ? 'Update Configuration' : 'Save Configuration' ?>
      </button>
    </form>
  </div>
</div>

<?php require __DIR__ . '/_layout_end.php'; ?>
