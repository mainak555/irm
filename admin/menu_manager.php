<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config.php';

require_auth('sa');

$user     = current_user();
$menu_file = __DIR__ . '/../config/menu.json';

function mm_load(): array
{
    global $menu_file;
    if (!is_readable($menu_file)) {
        return [];
    }
    $data = json_decode((string)file_get_contents($menu_file), true);
    if (!is_array($data)) {
        return [];
    }
    usort($data, fn($a, $b) => (int)($a['sort_order'] ?? 0) <=> (int)($b['sort_order'] ?? 0));
    return $data;
}

function mm_save(array $items): void
{
    global $menu_file;
    $dir = dirname($menu_file);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    file_put_contents($menu_file, json_encode(array_values($items), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

function mm_page_exists(string $slug): bool
{
    if ($slug === '') {
        return false;
    }
    return is_file(__DIR__ . '/../config/public/' . $slug . '.json');
}

function mm_csrf_check(): void
{
    if (!hash_equals((string)($_SESSION['csrf'] ?? ''), (string)($_POST['csrf'] ?? ''))) {
        http_response_code(403);
        exit('CSRF mismatch');
    }
}

$errors = [];

// --- POST handlers ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    mm_csrf_check();
    $action = (string)($_POST['action'] ?? '');
    $items  = mm_load();

    // --- Save top-level item ---
    if ($action === 'save_top') {
        $idx       = $_POST['edit_index'] ?? '';
        $label     = trim((string)($_POST['label']  ?? ''));
        $link_type = $_POST['link_type'] ?? 'internal';
        $slug      = preg_replace('/[^a-z0-9-]/', '', strtolower(trim((string)($_POST['slug'] ?? ''))));
        $url       = trim((string)($_POST['url']   ?? ''));
        $is_ext    = $link_type === 'external' ? 1 : 0;
        $sort      = (int)($_POST['sort_order'] ?? (count($items) + 1) * 10);

        if ($label === '') {
            $errors[] = 'Label is required.';
        } else {
            $entry = [
                'label'      => $label,
                'slug'       => $is_ext ? ($slug ?: 'link') : $slug,
                'sort_order' => $sort,
                'is_external'=> $is_ext,
                'children'   => [],
            ];
            if ($is_ext) {
                $entry['url'] = $url;
            }
            if ($idx !== '' && isset($items[(int)$idx])) {
                $entry['children'] = $items[(int)$idx]['children'] ?? [];
                $items[(int)$idx]  = $entry;
            } else {
                $items[] = $entry;
            }
            mm_save($items);
            $_SESSION['flash'] = ['type' => 'ok', 'msg' => 'Menu item saved.'];
            header('Location: /admin/menu_manager.php');
            exit;
        }
    }

    // --- Save child item ---
    if ($action === 'save_child') {
        $parent_idx = (int)($_POST['parent_index'] ?? -1);
        $child_idx  = $_POST['child_index'] ?? '';
        $label      = trim((string)($_POST['label'] ?? ''));
        $link_type  = $_POST['link_type'] ?? 'internal';
        $slug       = preg_replace('/[^a-z0-9-]/', '', strtolower(trim((string)($_POST['slug'] ?? ''))));
        $url        = trim((string)($_POST['url']  ?? ''));
        $is_ext     = $link_type === 'external' ? 1 : 0;

        if ($label === '') {
            $errors[] = 'Label is required.';
        } elseif (!isset($items[$parent_idx])) {
            $errors[] = 'Parent item not found.';
        } else {
            $child = [
                'label'       => $label,
                'slug'        => $slug,
                'is_external' => $is_ext,
                'sort_order'  => 0,
            ];
            if ($is_ext) {
                $child['url'] = $url;
            }
            if ($child_idx !== '' && isset($items[$parent_idx]['children'][(int)$child_idx])) {
                $items[$parent_idx]['children'][(int)$child_idx] = $child;
            } else {
                $items[$parent_idx]['children'][] = $child;
            }
            mm_save($items);
            $_SESSION['flash'] = ['type' => 'ok', 'msg' => 'Child item saved.'];
            header('Location: /admin/menu_manager.php');
            exit;
        }
    }

    // --- Delete top-level item ---
    if ($action === 'delete_top') {
        $idx = (int)($_POST['idx'] ?? -1);
        if (isset($items[$idx])) {
            array_splice($items, $idx, 1);
            mm_save($items);
            $_SESSION['flash'] = ['type' => 'ok', 'msg' => 'Menu item deleted.'];
        }
        header('Location: /admin/menu_manager.php');
        exit;
    }

    // --- Delete child item ---
    if ($action === 'delete_child') {
        $parent_idx = (int)($_POST['parent_idx'] ?? -1);
        $child_idx  = (int)($_POST['child_idx']  ?? -1);
        if (isset($items[$parent_idx]['children'][$child_idx])) {
            array_splice($items[$parent_idx]['children'], $child_idx, 1);
            mm_save($items);
            $_SESSION['flash'] = ['type' => 'ok', 'msg' => 'Child item deleted.'];
        }
        header('Location: /admin/menu_manager.php');
        exit;
    }

    // --- Reorder top-level ---
    if ($action === 'move_top') {
        $idx = (int)($_POST['idx'] ?? -1);
        $dir = (int)($_POST['dir'] ?? 0);
        $target = $idx + $dir;
        if (isset($items[$idx], $items[$target])) {
            [$items[$idx], $items[$target]] = [$items[$target], $items[$idx]];
            // Swap sort_order values too
            [$items[$idx]['sort_order'], $items[$target]['sort_order']] =
                [$items[$target]['sort_order'], $items[$idx]['sort_order']];
            mm_save($items);
        }
        header('Location: /admin/menu_manager.php');
        exit;
    }

    // --- Reorder child ---
    if ($action === 'move_child') {
        $parent_idx = (int)($_POST['parent_idx'] ?? -1);
        $child_idx  = (int)($_POST['child_idx']  ?? -1);
        $dir        = (int)($_POST['dir']         ?? 0);
        $target     = $child_idx + $dir;
        if (isset($items[$parent_idx]['children'][$child_idx], $items[$parent_idx]['children'][$target])) {
            [$items[$parent_idx]['children'][$child_idx], $items[$parent_idx]['children'][$target]] =
                [$items[$parent_idx]['children'][$target], $items[$parent_idx]['children'][$child_idx]];
            mm_save($items);
        }
        header('Location: /admin/menu_manager.php');
        exit;
    }
}

// --- GET: load data ---
$items = mm_load();

require __DIR__ . '/_layout.php';
?>

<div class="container-fluid px-4 py-3">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h4 class="mb-0">Menu Manager</h4>
  </div>

  <?php foreach ($errors as $e): ?>
    <div class="alert alert-danger"><?= h($e) ?></div>
  <?php endforeach; ?>

  <div class="row g-3">

    <!-- Left: current menu -->
    <div class="col-lg-7">
      <div class="card border-0 shadow-sm mb-3">
        <div class="card-header py-2 fw-semibold small">Navigation Items</div>
        <?php if (empty($items)): ?>
        <div class="card-body text-muted">No items yet. Add one using the form.</div>
        <?php else: ?>
        <ul class="list-group list-group-flush">
          <?php foreach ($items as $ti => $item): ?>
          <li class="list-group-item">
            <div class="d-flex align-items-center justify-content-between">
              <div>
                <strong><?= h($item['label']) ?></strong>
                <span class="badge bg-secondary ms-1"><?= (int)($item['is_external'] ?? 0) ? 'ext' : h($item['slug'] ?? '') ?></span>
              </div>
              <div class="d-flex gap-1 flex-wrap">
                <!-- Reorder -->
                <?php if ($ti > 0): ?>
                <form method="post" class="d-inline">
                  <input type="hidden" name="csrf"   value="<?= h($_SESSION['csrf'] ?? '') ?>">
                  <input type="hidden" name="action" value="move_top">
                  <input type="hidden" name="idx"    value="<?= $ti ?>">
                  <input type="hidden" name="dir"    value="-1">
                  <button class="btn btn-sm btn-outline-secondary py-0 px-1">↑</button>
                </form>
                <?php endif; ?>
                <?php if ($ti < count($items) - 1): ?>
                <form method="post" class="d-inline">
                  <input type="hidden" name="csrf"   value="<?= h($_SESSION['csrf'] ?? '') ?>">
                  <input type="hidden" name="action" value="move_top">
                  <input type="hidden" name="idx"    value="<?= $ti ?>">
                  <input type="hidden" name="dir"    value="1">
                  <button class="btn btn-sm btn-outline-secondary py-0 px-1">↓</button>
                </form>
                <?php endif; ?>
                <!-- Edit -->
                <button class="btn btn-sm btn-outline-secondary py-0 px-2"
                        onclick="editTop(<?= $ti ?>, <?= json_encode($item, JSON_HEX_TAG) ?>)">Edit</button>
                <!-- Delete -->
                <?php
                $has_children   = !empty($item['children']);
                $page_warn      = mm_page_exists($item['slug'] ?? '');
                $del_msg        = 'Delete "' . addslashes($item['label']) . '"?';
                if ($has_children) $del_msg .= '\n\nThis will also remove ' . count($item['children']) . ' child item(s).';
                if ($page_warn)    $del_msg .= '\n\nNote: the page "' . addslashes($item['slug'] ?? '') . '" will remain on disk.';
                ?>
                <form method="post" class="d-inline"
                      onsubmit="return confirm('<?= addslashes($del_msg) ?>')">
                  <input type="hidden" name="csrf"   value="<?= h($_SESSION['csrf'] ?? '') ?>">
                  <input type="hidden" name="action" value="delete_top">
                  <input type="hidden" name="idx"    value="<?= $ti ?>">
                  <button class="btn btn-sm btn-outline-danger py-0 px-2">Delete</button>
                </form>
                <!-- Add child -->
                <button class="btn btn-sm btn-outline-primary py-0 px-2"
                        onclick="addChild(<?= $ti ?>)">+ Child</button>
              </div>
            </div>

            <!-- Children -->
            <?php if (!empty($item['children'])): ?>
            <ul class="list-group list-group-flush ms-3 mt-2">
              <?php foreach ($item['children'] as $ci => $child): ?>
              <li class="list-group-item py-1">
                <div class="d-flex align-items-center justify-content-between">
                  <div>
                    &rsaquo; <?= h($child['label']) ?>
                    <span class="badge bg-secondary ms-1"><?= (int)($child['is_external'] ?? 0) ? 'ext' : h($child['slug'] ?? '') ?></span>
                  </div>
                  <div class="d-flex gap-1">
                    <?php if ($ci > 0): ?>
                    <form method="post" class="d-inline">
                      <input type="hidden" name="csrf"       value="<?= h($_SESSION['csrf'] ?? '') ?>">
                      <input type="hidden" name="action"     value="move_child">
                      <input type="hidden" name="parent_idx" value="<?= $ti ?>">
                      <input type="hidden" name="child_idx"  value="<?= $ci ?>">
                      <input type="hidden" name="dir"        value="-1">
                      <button class="btn btn-sm btn-outline-secondary py-0 px-1">↑</button>
                    </form>
                    <?php endif; ?>
                    <?php if ($ci < count($item['children']) - 1): ?>
                    <form method="post" class="d-inline">
                      <input type="hidden" name="csrf"       value="<?= h($_SESSION['csrf'] ?? '') ?>">
                      <input type="hidden" name="action"     value="move_child">
                      <input type="hidden" name="parent_idx" value="<?= $ti ?>">
                      <input type="hidden" name="child_idx"  value="<?= $ci ?>">
                      <input type="hidden" name="dir"        value="1">
                      <button class="btn btn-sm btn-outline-secondary py-0 px-1">↓</button>
                    </form>
                    <?php endif; ?>
                    <button class="btn btn-sm btn-outline-secondary py-0 px-2"
                            onclick="editChild(<?= $ti ?>, <?= $ci ?>, <?= json_encode($child, JSON_HEX_TAG) ?>)">Edit</button>
                    <?php
                    $cdel_msg  = 'Delete child "' . addslashes($child['label']) . '"?';
                    if (mm_page_exists($child['slug'] ?? '')) {
                        $cdel_msg .= '\n\nNote: the page "' . addslashes($child['slug'] ?? '') . '" will remain on disk.';
                    }
                    ?>
                    <form method="post" class="d-inline"
                          onsubmit="return confirm('<?= addslashes($cdel_msg) ?>')">
                      <input type="hidden" name="csrf"       value="<?= h($_SESSION['csrf'] ?? '') ?>">
                      <input type="hidden" name="action"     value="delete_child">
                      <input type="hidden" name="parent_idx" value="<?= $ti ?>">
                      <input type="hidden" name="child_idx"  value="<?= $ci ?>">
                      <button class="btn btn-sm btn-outline-danger py-0 px-2">Del</button>
                    </form>
                  </div>
                </div>
              </li>
              <?php endforeach; ?>
            </ul>
            <?php endif; ?>
          </li>
          <?php endforeach; ?>
        </ul>
        <?php endif; ?>
      </div>
    </div>

    <!-- Right: add/edit form -->
    <div class="col-lg-5">
      <div class="card border-0 shadow-sm" id="mmFormCard">
        <div class="card-header py-2 fw-semibold small" id="mmFormTitle">Add Top-Level Item</div>
        <div class="card-body">
          <form method="post" action="/admin/menu_manager.php" id="mmForm">
            <input type="hidden" name="csrf"         value="<?= h($_SESSION['csrf'] ?? '') ?>">
            <input type="hidden" name="action"       value="save_top" id="mmAction">
            <input type="hidden" name="edit_index"   value=""         id="mmEditIndex">
            <input type="hidden" name="parent_index" value=""         id="mmParentIndex">
            <input type="hidden" name="child_index"  value=""         id="mmChildIndex">

            <div class="mb-2">
              <label class="form-label small fw-semibold">Label</label>
              <input type="text" name="label" id="mmLabel" class="form-control form-control-sm" required>
            </div>
            <div class="mb-2">
              <label class="form-label small fw-semibold">Link type</label>
              <div class="d-flex gap-3">
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="link_type" value="internal"
                         id="mmInternal" checked onchange="toggleLinkType()">
                  <label class="form-check-label" for="mmInternal">Internal page</label>
                </div>
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="link_type" value="external"
                         id="mmExternal" onchange="toggleLinkType()">
                  <label class="form-check-label" for="mmExternal">External URL</label>
                </div>
              </div>
            </div>
            <div class="mb-2" id="mmSlugRow">
              <label class="form-label small fw-semibold">Page slug</label>
              <input type="text" name="slug" id="mmSlug" class="form-control form-control-sm"
                     pattern="[a-z0-9-]*" placeholder="e.g. about">
            </div>
            <div class="mb-2 d-none" id="mmUrlRow">
              <label class="form-label small fw-semibold">URL</label>
              <input type="url" name="url" id="mmUrl" class="form-control form-control-sm" placeholder="https://…">
            </div>
            <div class="mb-3" id="mmSortRow">
              <label class="form-label small fw-semibold">Sort order</label>
              <input type="number" name="sort_order" id="mmSort" class="form-control form-control-sm" value="10" min="0" step="10">
            </div>

            <div class="d-flex gap-2">
              <button type="submit" class="btn btn-primary btn-sm">Save</button>
              <button type="button" class="btn btn-outline-secondary btn-sm" onclick="resetForm()">Reset</button>
            </div>
          </form>
        </div>
      </div>
    </div>

  </div>
</div>

<script>
function toggleLinkType() {
  const isExt = document.getElementById('mmExternal').checked;
  document.getElementById('mmSlugRow').classList.toggle('d-none',  isExt);
  document.getElementById('mmUrlRow').classList.toggle('d-none', !isExt);
}

function resetForm() {
  document.getElementById('mmFormTitle').textContent = 'Add Top-Level Item';
  document.getElementById('mmAction').value       = 'save_top';
  document.getElementById('mmEditIndex').value    = '';
  document.getElementById('mmParentIndex').value  = '';
  document.getElementById('mmChildIndex').value   = '';
  document.getElementById('mmLabel').value        = '';
  document.getElementById('mmSlug').value         = '';
  document.getElementById('mmUrl').value          = '';
  document.getElementById('mmSort').value         = '10';
  document.getElementById('mmSortRow').classList.remove('d-none');
  document.getElementById('mmInternal').checked   = true;
  toggleLinkType();
}

function editTop(idx, item) {
  document.getElementById('mmFormTitle').textContent = 'Edit: ' + item.label;
  document.getElementById('mmAction').value       = 'save_top';
  document.getElementById('mmEditIndex').value    = idx;
  document.getElementById('mmParentIndex').value  = '';
  document.getElementById('mmChildIndex').value   = '';
  document.getElementById('mmLabel').value        = item.label  || '';
  document.getElementById('mmSlug').value         = item.slug   || '';
  document.getElementById('mmUrl').value          = item.url    || '';
  document.getElementById('mmSort').value         = item.sort_order || 10;
  document.getElementById('mmSortRow').classList.remove('d-none');
  if (item.is_external) {
    document.getElementById('mmExternal').checked = true;
  } else {
    document.getElementById('mmInternal').checked = true;
  }
  toggleLinkType();
  document.getElementById('mmFormCard').scrollIntoView({ behavior: 'smooth' });
}

function addChild(parentIdx) {
  document.getElementById('mmFormTitle').textContent = 'Add Child Item';
  document.getElementById('mmAction').value       = 'save_child';
  document.getElementById('mmEditIndex').value    = '';
  document.getElementById('mmParentIndex').value  = parentIdx;
  document.getElementById('mmChildIndex').value   = '';
  document.getElementById('mmLabel').value        = '';
  document.getElementById('mmSlug').value         = '';
  document.getElementById('mmUrl').value          = '';
  document.getElementById('mmSortRow').classList.add('d-none');
  document.getElementById('mmInternal').checked   = true;
  toggleLinkType();
  document.getElementById('mmFormCard').scrollIntoView({ behavior: 'smooth' });
}

function editChild(parentIdx, childIdx, child) {
  document.getElementById('mmFormTitle').textContent = 'Edit child: ' + child.label;
  document.getElementById('mmAction').value       = 'save_child';
  document.getElementById('mmEditIndex').value    = '';
  document.getElementById('mmParentIndex').value  = parentIdx;
  document.getElementById('mmChildIndex').value   = childIdx;
  document.getElementById('mmLabel').value        = child.label || '';
  document.getElementById('mmSlug').value         = child.slug  || '';
  document.getElementById('mmUrl').value          = child.url   || '';
  document.getElementById('mmSortRow').classList.add('d-none');
  if (child.is_external) {
    document.getElementById('mmExternal').checked = true;
  } else {
    document.getElementById('mmInternal').checked = true;
  }
  toggleLinkType();
  document.getElementById('mmFormCard').scrollIntoView({ behavior: 'smooth' });
}
</script>

<?php require __DIR__ . '/_layout_end.php'; ?>
