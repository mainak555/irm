<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config.php';

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
}

$user  = current_user();
$theme = $user['theme'] ?? 'system';
$role  = $user['role']  ?? '';

$current_page = basename($_SERVER['PHP_SELF'], '.php');
$logo_url     = cfg('general.logoUrl') ?: '/assets/img/logo.png';

// Resolve data-bs-theme attribute value for non-system themes
$bs_theme_attr = ($theme === 'system') ? '' : ' data-bs-theme="' . h($theme) . '"';
?>
<!DOCTYPE html>
<html lang="en"<?= $bs_theme_attr ?>>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= h(cfg('general.title') ?: 'IRM') ?> &mdash; Admin</title>
<?php if ($theme === 'system'): ?>
<script>
(function(){
  var t = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
  document.documentElement.setAttribute('data-bs-theme', t);
})();
</script>
<?php endif; ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="/assets/css/admin.css">
<script>
/* ------------------------------------------------------------------ *
 * IRM timestamp utility                                               *
 * Converts UTC "YYYY-MM-DD HH:MM:SS" strings to the browser's local  *
 * timezone. Intl.DateTimeFormat uses the IANA tz database so DST     *
 * (including US DST transitions) is handled automatically.           *
 * ------------------------------------------------------------------ */
window.IRM = window.IRM || {};
window.IRM.formatUtcTs = function (isoStr) {
  if (!isoStr) return '—';
  // MySQL stores without timezone marker — append Z to parse as UTC
  var d = new Date(String(isoStr).replace(' ', 'T') + 'Z');
  if (isNaN(d.getTime())) return isoStr;
  return new Intl.DateTimeFormat(navigator.language || undefined, {
    day: '2-digit', month: 'short', year: 'numeric',
    hour: '2-digit', minute: '2-digit'
  }).format(d);
};
document.addEventListener('DOMContentLoaded', function () {
  var tz = Intl.DateTimeFormat().resolvedOptions().timeZone;
  document.querySelectorAll('[data-utc-ts]').forEach(function (el) {
    var raw = el.dataset.utcTs;
    if (raw) {
      var local = window.IRM.formatUtcTs(raw);
      el.textContent = local;
      el.title = local + ' (' + tz + ')  ·  ' + raw + ' UTC';
    }
  });
});
</script>
</head>
<body class="grain-texture">

<!-- Top Navbar -->
<nav class="navbar navbar-expand px-3 border-bottom" style="height:56px">
  <a class="navbar-brand d-flex align-items-center gap-2" href="/admin/index.php">
    <img src="<?= h($logo_url) ?>" alt="Logo" class="navbar-logo">
    <?= h(cfg('general.title') ?: 'IRM Admin') ?>
  </a>
  <div class="ms-auto d-flex align-items-center gap-3">
    <span class="d-none d-sm-inline">
      <?= h($user['name'] ?? '') ?>
      <span class="badge badge-role-<?= h($role) ?> ms-1"><?= h(strtoupper($role)) ?></span>
    </span>

    <!-- Theme switcher -->
    <form method="post" action="/admin/profile.php" class="d-flex align-items-center gap-1">
      <input type="hidden" name="action" value="theme">
      <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf'] ?? '') ?>">
      <input type="hidden" name="redirect" value="<?= h($_SERVER['REQUEST_URI']) ?>">
      <select name="theme" class="form-select form-select-sm" style="width:auto"
              onchange="this.form.submit()" aria-label="Theme">
        <option value="light"  <?= $theme==='light'  ? 'selected' : '' ?>>Light</option>
        <option value="dark"   <?= $theme==='dark'   ? 'selected' : '' ?>>Dark</option>
        <option value="system" <?= $theme==='system' ? 'selected' : '' ?>>System</option>
      </select>
    </form>

    <a href="/admin/logout.php" class="btn btn-outline-secondary btn-sm">Logout</a>
  </div>
</nav>

<div class="admin-wrapper" id="adminWrapper">
<script>
if (localStorage.getItem('irm_sidebar') === 'collapsed')
  document.getElementById('adminWrapper').classList.add('sidebar-collapsed');
</script>

  <!-- Sidebar -->
  <aside class="admin-sidebar" id="adminSidebar">
    <div class="sidebar-brand">
      <span class="sidebar-brand-name">LUMIO</span>
    </div>
    <div class="accordion accordion-flush" id="sidebarAccordion">

      <a class="nav-link <?= $current_page === 'index'   ? 'active' : '' ?>"
         href="/admin/index.php">Dashboard</a>
      <a class="nav-link <?= $current_page === 'profile' ? 'active' : '' ?>"
         href="/admin/profile.php">Profile</a>

      <?php if (in_array($role, ['sa', 'admin'], true)): ?>
      <?php $authOpen = in_array($current_page, ['users', 'auth_config'], true); ?>
      <div class="accordion-item border-0">
        <h2 class="accordion-header">
          <button class="accordion-button <?= $authOpen ? '' : 'collapsed' ?> px-2 py-2"
                  type="button"
                  data-bs-toggle="collapse"
                  data-bs-target="#authMenu"
                  aria-expanded="<?= $authOpen ? 'true' : 'false' ?>"
                  aria-controls="authMenu">
            Authorization
          </button>
        </h2>
        <div id="authMenu"
             class="accordion-collapse collapse <?= $authOpen ? 'show' : '' ?>"
             data-bs-parent="#sidebarAccordion">
          <div class="accordion-body py-1 px-0">
            <a class="nav-link ps-3 <?= $current_page === 'users'       ? 'active' : '' ?>"
               href="/admin/users.php">Users</a>
            <?php if ($role === 'sa'): ?>
            <a class="nav-link ps-3 <?= $current_page === 'auth_config' ? 'active' : '' ?>"
               href="/admin/auth_config.php">SSO</a>
            <?php endif; ?>
          </div>
        </div>
      </div>
      <?php endif; ?>

      <?php if (in_array($role, ['sa', 'admin'], true)): ?>
      <?php $settingsOpen = str_starts_with($current_page, 'config_') || $current_page === 'carousel'; ?>
      <div class="accordion-item border-0">
        <h2 class="accordion-header">
          <button class="accordion-button <?= $settingsOpen ? '' : 'collapsed' ?> px-2 py-2"
                  type="button"
                  data-bs-toggle="collapse"
                  data-bs-target="#settingsMenu"
                  aria-expanded="<?= $settingsOpen ? 'true' : 'false' ?>"
                  aria-controls="settingsMenu">
            Settings
          </button>
        </h2>
        <div id="settingsMenu"
             class="accordion-collapse collapse <?= $settingsOpen ? 'show' : '' ?>"
             data-bs-parent="#sidebarAccordion">
          <div class="accordion-body py-1 px-0">
            <?php if ($role === 'sa'): ?>
            <a class="nav-link ps-3 <?= $current_page === 'config_general' ? 'active' : '' ?>"
               href="/admin/config_general.php">General</a>
            <?php endif; ?>
            <a class="nav-link ps-3 <?= $current_page === 'carousel' ? 'active' : '' ?>"
               href="/admin/carousel.php">Carousel</a>
          </div>
        </div>
      </div>
      <?php endif; ?>

    </div>
  </aside>

  <!-- Sidebar toggle chevron — straddles the sidebar right border -->
  <button class="sidebar-toggle-btn" id="sidebarToggle" aria-label="Toggle sidebar">
    <span class="sidebar-chevron">&#8249;</span>
  </button>
  <!-- Hover zone: thin strip at left edge, triggers floating sidebar when collapsed -->
  <div class="sidebar-hover-zone" id="sidebarHoverZone" aria-hidden="true"></div>

  <!-- Main content -->
  <main class="admin-main">

    <!-- Flash message -->
    <?php if (!empty($_SESSION['flash'])): ?>
      <?php $flash = $_SESSION['flash']; unset($_SESSION['flash']); ?>
      <div class="flash-area">
        <div class="alert alert-<?= $flash['type'] === 'ok' ? 'success' : 'danger' ?> alert-dismissible fade show" role="alert">
          <?= h($flash['msg']) ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      </div>
    <?php endif; ?>
