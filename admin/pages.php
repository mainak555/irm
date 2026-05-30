<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config.php';

require_auth('admin', 'sa');

$user = current_user();

// --- POST: delete ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    if (!hash_equals((string)($_SESSION['csrf'] ?? ''), (string)($_POST['csrf'] ?? ''))) {
        http_response_code(403);
        exit('CSRF mismatch');
    }
    $del_slug = preg_replace('/[^a-z0-9-]/', '', (string)($_POST['slug'] ?? ''));
    if ($del_slug !== '') {
        $del_file = __DIR__ . '/../config/public/' . $del_slug . '.json';
        if (is_file($del_file)) {
            unlink($del_file);
        }
    }
    $_SESSION['flash'] = ['type' => 'ok', 'msg' => 'Page deleted.'];
    header('Location: /admin/pages.php');
    exit;
}

// --- Load pages ---
$pub_dir = __DIR__ . '/../config/public/';
$pages   = [];
foreach (glob($pub_dir . '*.json') ?: [] as $f) {
    $raw  = (string)file_get_contents($f);
    $data = json_decode($raw, true) ?? [];
    $slug = basename($f, '.json');
    $pages[] = [
        'slug'     => $slug,
        'title'    => (string)($data['title'] ?? '(untitled)'),
        'modified' => filemtime($f),
    ];
}
usort($pages, fn($a, $b) => $b['modified'] <=> $a['modified']);

// --- Menu slugs for delete warning ---
$menu_raw   = is_readable(__DIR__ . '/../config/menu.json')
    ? (string)file_get_contents(__DIR__ . '/../config/menu.json')
    : '[]';
$menu_data  = json_decode($menu_raw, true) ?? [];
$menu_slugs = [];
foreach ($menu_data as $item) {
    if (!empty($item['slug'])) {
        $menu_slugs[] = $item['slug'];
    }
    foreach ((array)($item['children'] ?? []) as $child) {
        if (!empty($child['slug'])) {
            $menu_slugs[] = $child['slug'];
        }
    }
}

require __DIR__ . '/_layout.php';
?>

<div class="container-fluid px-4 py-3">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h4 class="mb-0">Pages</h4>
    <a href="/admin/page_designer.php" class="btn btn-primary btn-sm">+ New Page</a>
  </div>

  <?php if (empty($pages)): ?>
  <div class="card border-0 shadow-sm">
    <div class="card-body text-center py-5">
      <p class="text-muted mb-3">No pages yet.</p>
      <a href="/admin/page_designer.php" class="btn btn-primary">Create your first page</a>
    </div>
  </div>
  <?php else: ?>
  <div class="card border-0 shadow-sm">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>Title</th>
            <th>Slug</th>
            <th>Last Modified</th>
            <th class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($pages as $pg): ?>
          <tr>
            <td><?= h($pg['title']) ?></td>
            <td><code><?= h($pg['slug']) ?></code></td>
            <td><?= h(date('j M Y, H:i', $pg['modified'])) ?></td>
            <td class="text-end">
              <a href="/admin/page_designer.php?slug=<?= urlencode($pg['slug']) ?>"
                 class="btn btn-sm btn-outline-secondary me-1">Edit</a>
              <button type="button"
                      class="btn btn-sm btn-outline-danger"
                      data-slug="<?= h($pg['slug']) ?>"
                      data-in-menu="<?= in_array($pg['slug'], $menu_slugs, true) ? '1' : '0' ?>"
                      onclick="confirmDelete(this)">Delete</button>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif; ?>
</div>

<!-- Delete form (reused) -->
<form id="deleteForm" method="post" action="/admin/pages.php">
  <input type="hidden" name="action" value="delete">
  <input type="hidden" name="csrf"   value="<?= h($_SESSION['csrf'] ?? '') ?>">
  <input type="hidden" name="slug"   id="deleteSlug">
</form>

<script>
function confirmDelete(btn) {
  var slug   = btn.dataset.slug;
  var inMenu = btn.dataset.inMenu === '1';
  var msg    = 'Delete page "' + slug + '"?';
  if (inMenu) {
    msg += '\n\nWarning: this slug is linked in the navigation menu. The menu entry will remain after deletion.';
  }
  if (confirm(msg)) {
    document.getElementById('deleteSlug').value = slug;
    document.getElementById('deleteForm').submit();
  }
}
</script>

<?php require __DIR__ . '/_layout_end.php'; ?>
