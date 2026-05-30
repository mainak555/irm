<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config.php';

require_auth('sa');

$user = current_user();

$config_path = __DIR__ . '/../config/config.json';
$themes_dir  = __DIR__ . '/../public/css/themes';

// ---- POST handler ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        http_response_code(403);
        exit('Invalid CSRF token.');
    }

    $current_raw = (string) file_get_contents($config_path);
    $data = json_decode($current_raw, true) ?? [];

    $data['general']['title']           = trim($_POST['title']    ?? '');
    $data['general']['subtitle']        = trim($_POST['subtitle']  ?? '');
    $data['general']['logoUrl']         = trim($_POST['logoUrl']   ?? '');
    $data['general']['address']         = trim($_POST['address']   ?? '');
    $data['general']['phone']           = trim($_POST['phone']     ?? '');
    $data['general']['fax']             = trim($_POST['fax']       ?? '');
    $data['general']['email']           = trim($_POST['email']     ?? '');
    $data['general']['social']['facebook']  = trim($_POST['facebook']  ?? '');
    $data['general']['social']['twitter']   = trim($_POST['twitter']   ?? '');
    $data['general']['social']['instagram'] = trim($_POST['instagram'] ?? '');
    $data['general']['social']['youtube']   = trim($_POST['youtube']   ?? '');
    $data['public']['theme']            = trim($_POST['theme']     ?? 'classic');

    // Backup before write
    file_put_contents($config_path . '.bak', $current_raw);

    $written = file_put_contents(
        $config_path,
        json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        LOCK_EX
    );

    // Validate written file
    $check = json_decode((string) file_get_contents($config_path), true);
    if ($check === null) {
        // Restore backup
        file_put_contents($config_path, file_get_contents($config_path . '.bak'), LOCK_EX);
        $_SESSION['flash'] = ['type' => 'err', 'msg' => 'Save failed: JSON validation error. Previous file restored.'];
    } else {
        $_SESSION['flash'] = ['type' => 'ok', 'msg' => 'General settings saved.'];
    }

    header('Location: /admin/config_general.php');
    exit;
}

// ---- GET: read config ----
$raw  = (string) file_get_contents($config_path);
$cfg  = json_decode($raw, true) ?? [];
$gen  = $cfg['general'] ?? [];
$pub  = $cfg['public']  ?? [];

// Discover theme packs
$theme_files = glob($themes_dir . '/*.css') ?: [];
$theme_packs = [];
foreach ($theme_files as $f) {
    $slug  = basename($f, '.css');
    $label = ucwords(str_replace('-', ' ', $slug));
    $theme_packs[$slug] = $label;
}

$active_theme = $pub['theme'] ?? 'classic';

require __DIR__ . '/_layout.php';
?>

<div class="container-fluid px-4 py-3">
  <h4 class="mb-1">General Settings</h4>
  <p class="text-muted small mb-4">School identity and public theme pack.</p>

  <?php if (empty($theme_packs)): ?>
    <div class="alert alert-warning">
      No theme packs found in <code>public/css/themes/</code>. Drop a <code>.css</code> file there to add one.
    </div>
  <?php endif; ?>

  <form method="post" class="row g-3" style="max-width:720px" novalidate>
    <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf'] ?? '') ?>">

    <!-- School Identity -->
    <div class="col-12">
      <h6 class="text-muted text-uppercase" style="font-size:.7rem;letter-spacing:.08em;">School Identity</h6>
    </div>

    <div class="col-md-6">
      <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
      <input type="text" id="title" name="title" class="form-control"
             value="<?= h($gen['title'] ?? '') ?>" required>
    </div>

    <div class="col-md-6">
      <label for="subtitle" class="form-label">Subtitle</label>
      <input type="text" id="subtitle" name="subtitle" class="form-control"
             value="<?= h($gen['subtitle'] ?? '') ?>">
    </div>

    <div class="col-12">
      <label for="logoUrl" class="form-label">Logo URL</label>
      <input type="text" id="logoUrl" name="logoUrl" class="form-control"
             value="<?= h($gen['logoUrl'] ?? '') ?>"
             placeholder="/assets/img/logo.png">
    </div>

    <div class="col-12">
      <label for="address" class="form-label">Address</label>
      <textarea id="address" name="address" class="form-control" rows="3"><?= h($gen['address'] ?? '') ?></textarea>
    </div>

    <div class="col-md-4">
      <label for="phone" class="form-label">Phone</label>
      <input type="text" id="phone" name="phone" class="form-control"
             value="<?= h($gen['phone'] ?? '') ?>">
    </div>

    <div class="col-md-4">
      <label for="fax" class="form-label">Fax</label>
      <input type="text" id="fax" name="fax" class="form-control"
             value="<?= h($gen['fax'] ?? '') ?>">
    </div>

    <div class="col-md-4">
      <label for="email" class="form-label">Email</label>
      <input type="email" id="email" name="email" class="form-control"
             value="<?= h($gen['email'] ?? '') ?>">
    </div>

    <!-- Social -->
    <div class="col-12 mt-2">
      <h6 class="text-muted text-uppercase" style="font-size:.7rem;letter-spacing:.08em;">Social</h6>
    </div>

    <div class="col-md-6">
      <label for="facebook" class="form-label">Facebook URL</label>
      <input type="url" id="facebook" name="facebook" class="form-control"
             value="<?= h(($gen['social'] ?? [])['facebook'] ?? '') ?>"
             placeholder="https://facebook.com/yourpage">
    </div>

    <div class="col-md-6">
      <label for="twitter" class="form-label">Twitter / X URL</label>
      <input type="url" id="twitter" name="twitter" class="form-control"
             value="<?= h(($gen['social'] ?? [])['twitter'] ?? '') ?>"
             placeholder="https://x.com/yourhandle">
    </div>

    <div class="col-md-6">
      <label for="instagram" class="form-label">Instagram URL</label>
      <input type="url" id="instagram" name="instagram" class="form-control"
             value="<?= h(($gen['social'] ?? [])['instagram'] ?? '') ?>"
             placeholder="https://instagram.com/yourpage">
    </div>

    <div class="col-md-6">
      <label for="youtube" class="form-label">YouTube URL</label>
      <input type="url" id="youtube" name="youtube" class="form-control"
             value="<?= h(($gen['social'] ?? [])['youtube'] ?? '') ?>"
             placeholder="https://youtube.com/@yourchannel">
    </div>

    <!-- Public Theme -->
    <div class="col-12 mt-2">
      <h6 class="text-muted text-uppercase" style="font-size:.7rem;letter-spacing:.08em;">Public Theme</h6>
    </div>

    <div class="col-md-6">
      <label for="theme" class="form-label">Theme Pack</label>
      <select id="theme" name="theme" class="form-select">
        <?php foreach ($theme_packs as $slug => $label): ?>
          <option value="<?= h($slug) ?>" <?= $slug === $active_theme ? 'selected' : '' ?>>
            <?= h($label) ?>
          </option>
        <?php endforeach; ?>
        <?php if (empty($theme_packs)): ?>
          <option value="classic" selected>Classic (default)</option>
        <?php endif; ?>
      </select>
      <div class="form-text">CSS files in <code>public/css/themes/</code> appear here automatically.</div>
    </div>

    <div class="col-12 mt-3">
      <button type="submit" class="btn btn-primary">Save Changes</button>
    </div>
  </form>
</div>

<?php require __DIR__ . '/_layout_end.php'; ?>
