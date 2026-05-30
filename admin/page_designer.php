<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config.php';

require_auth('admin', 'sa');

$user = current_user();

const PD_RESERVED = ['admin', 'assets', 'config', 'includes', 'components', 'api'];

// --- Discover available components ---
$component_names = [];
foreach (glob(__DIR__ . '/../components/*.php') ?: [] as $f) {
    $component_names[] = basename($f, '.php');
}
sort($component_names);

// --- Load layout manifest ---
$_layout_slug          = basename((string)cfg('public.layout', ''));
$_layout_manifest_file = __DIR__ . '/../assets/css/layouts/' . $_layout_slug . '/manifest.json';
$_layout_pages         = [];
if ($_layout_slug !== '' && is_file($_layout_manifest_file)) {
    $_m = json_decode((string)file_get_contents($_layout_manifest_file), true) ?? [];
    $_layout_pages = is_array($_m['pages'] ?? null) ? $_m['pages'] : [];
}

// --- Load page if editing ---
$edit_slug = preg_replace('/[^a-z0-9-]/', '', (string)($_GET['slug'] ?? ''));
$is_edit   = $edit_slug !== '';
$page_data = ['slug' => '', 'title' => '', 'description' => '', 'layout_id' => '', 'slots' => []];
$errors    = [];

if ($is_edit) {
    $edit_file = __DIR__ . '/../config/public/' . $edit_slug . '.json';
    if (is_readable($edit_file)) {
        $raw       = (string)file_get_contents($edit_file);
        $page_data = array_merge($page_data, json_decode($raw, true) ?? []);
        $page_data['slug'] = $edit_slug;
        if (!is_array($page_data['slots'])) {
            $page_data['slots'] = [];
        }
    } else {
        $_SESSION['flash'] = ['type' => 'err', 'msg' => 'Page not found.'];
        header('Location: /admin/pages.php');
        exit;
    }
}

// --- POST: save ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals((string)($_SESSION['csrf'] ?? ''), (string)($_POST['csrf'] ?? ''))) {
        http_response_code(403);
        exit('CSRF mismatch');
    }

    $slug        = strtolower(trim((string)($_POST['slug'] ?? '')));
    $title       = trim((string)($_POST['title'] ?? ''));
    $description = trim((string)($_POST['description'] ?? ''));
    $layout_id   = preg_replace('/[^a-z0-9-]/', '', (string)($_POST['layout_id'] ?? ''));

    if (!preg_match('/^[a-z0-9-]+$/', $slug)) {
        $errors[] = 'Slug must contain only lowercase letters, numbers, and hyphens.';
    } elseif (in_array($slug, PD_RESERVED, true)) {
        $errors[] = 'That slug is reserved and cannot be used.';
    } elseif (!$is_edit && is_file(__DIR__ . '/../config/public/' . $slug . '.json')) {
        $errors[] = 'A page with that slug already exists.';
    }

    $slots = [];
    foreach (($_POST['slots'] ?? []) as $slot_id_raw => $slot_data) {
        if (!is_array($slot_data)) {
            continue;
        }
        $sid  = preg_replace('/[^a-z0-9-]/', '', (string)$slot_id_raw);
        if ($sid === '') {
            continue;
        }
        $type = (string)($slot_data['type'] ?? '');
        if ($type === 'html') {
            $html = (string)($slot_data['html'] ?? '');
            $html = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $html) ?? '';
            $html = preg_replace('/\s+on\w+="[^"]*"/i', '', $html) ?? '';
            $html = preg_replace("/\s+on\w+='[^']*'/i", '', $html) ?? '';
            $slots[$sid] = ['type' => 'html', 'html' => $html];
        } elseif ($type === 'component') {
            $name = preg_replace('/[^a-z0-9_-]/', '', (string)($slot_data['name'] ?? ''));
            if ($name !== '') {
                $slots[$sid] = ['type' => 'component', 'name' => $name];
            }
        } elseif ($type === 'embed') {
            $url = trim((string)($slot_data['url'] ?? ''));
            if ($url !== '') {
                $slots[$sid] = [
                    'type'    => 'embed',
                    'subtype' => (string)($slot_data['subtype'] ?? 'youtube'),
                    'url'     => $url,
                    'title'   => trim((string)($slot_data['title'] ?? '')),
                ];
            } else {
                $errors[] = 'Embed slots require a URL.';
            }
        }
    }

    if (empty($errors)) {
        $pub_dir = __DIR__ . '/../config/public/';
        if (!is_dir($pub_dir)) {
            mkdir($pub_dir, 0755, true);
        }
        $save_slug = $is_edit ? $edit_slug : $slug;
        $payload   = [
            'slug'        => $save_slug,
            'title'       => $title,
            'description' => $description,
            'layout_id'   => $layout_id,
            'slots'       => $slots,
        ];
        file_put_contents(
            $pub_dir . $save_slug . '.json',
            json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
        $_SESSION['flash'] = ['type' => 'ok', 'msg' => 'Page saved.'];
        header('Location: /admin/pages.php');
        exit;
    }

    $page_data = [
        'slug'        => $is_edit ? $edit_slug : $slug,
        'title'       => $title,
        'description' => $description,
        'layout_id'   => $layout_id,
        'slots'       => $slots,
    ];
}

require __DIR__ . '/_layout.php';
?>

<style>
.slot-panel { border: 1px solid var(--irm-border); border-radius: var(--irm-radius); margin-bottom: 1rem; overflow: hidden; }
.slot-panel-header { background: var(--irm-muted); padding: .6rem 1rem; border-bottom: 1px solid var(--irm-border); }
.slot-type-fields { display: none; }
.slot-type-fields.active { display: block; }
</style>

<div class="container-fluid px-4 py-3">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h4 class="mb-0"><?= $is_edit ? 'Edit Page' : 'New Page' ?></h4>
    <a href="/admin/pages.php" class="btn btn-outline-secondary btn-sm">← Pages</a>
  </div>

  <?php foreach ($errors as $err): ?>
    <div class="alert alert-danger"><?= h($err) ?></div>
  <?php endforeach; ?>

  <div class="row g-3">
    <!-- Left: form -->
    <div class="col-lg-7">
      <form method="post"
            action="/admin/page_designer.php<?= $is_edit ? '?slug=' . urlencode($edit_slug) : '' ?>"
            id="pdForm">
        <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf'] ?? '') ?>">

        <div class="card border-0 shadow-sm mb-3">
          <div class="card-body">
            <div class="mb-3">
              <label class="form-label fw-semibold">Slug <small class="text-muted">(URL path)</small></label>
              <?php if ($is_edit): ?>
                <input type="hidden" name="slug" value="<?= h($page_data['slug']) ?>">
                <input type="text" class="form-control bg-body-secondary" value="<?= h($page_data['slug']) ?>" readonly>
              <?php else: ?>
                <input type="text" name="slug" class="form-control" value="<?= h($page_data['slug']) ?>"
                       pattern="[a-z0-9-]+" placeholder="e.g. about-us" required>
                <div class="form-text">Lowercase letters, numbers, hyphens only. Cannot be changed after creation.</div>
              <?php endif; ?>
            </div>
            <div class="mb-3">
              <label class="form-label fw-semibold">Page Title</label>
              <input type="text" name="title" class="form-control"
                     value="<?= h($page_data['title']) ?>" placeholder="Page title">
            </div>
            <div class="mb-3">
              <label class="form-label fw-semibold">Meta Description <small class="text-muted">(SEO)</small></label>
              <textarea name="description" class="form-control" rows="2"
                        placeholder="Brief description for search engines"><?= h($page_data['description']) ?></textarea>
            </div>
            <div class="mb-0">
              <label class="form-label fw-semibold">Page Layout</label>
              <select id="layoutSelect" name="layout_id" class="form-select"
                      onchange="onLayoutChange(this)">
                <option value="">— No layout —</option>
                <?php foreach ($_layout_pages as $_lp): ?>
                <option value="<?= h($_lp['id']) ?>"><?= h($_lp['label']) ?></option>
                <?php endforeach; ?>
              </select>
              <?php if (empty($_layout_pages)): ?>
              <div class="form-text text-warning">No layout pack found. Set <code>public.layout</code> in config.json.</div>
              <?php else: ?>
              <div class="form-text">Slots update when you change the layout. Matching slot IDs retain their content.</div>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <!-- Slot panels rendered by JS -->
        <div id="slotContainer"></div>

        <button type="submit" class="btn btn-primary">Save Page</button>
      </form>
    </div>

    <!-- Right: live preview -->
    <div class="col-lg-5">
      <div class="card border-0 shadow-sm" style="position:sticky;top:70px">
        <div class="card-header d-flex align-items-center justify-content-between py-2">
          <span class="fw-semibold small">Preview</span>
          <span id="previewStatus" class="text-muted small"></span>
        </div>
        <div class="card-body p-0">
          <iframe id="previewFrame"
                  style="width:100%;min-height:520px;border:0;border-radius:0 0 var(--irm-radius) var(--irm-radius)"
                  srcdoc="&lt;p style='padding:1rem;color:#888'&gt;Select a layout to see preview…&lt;/p&gt;">
          </iframe>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
window.irmLayoutPages = <?= json_encode($_layout_pages,   JSON_HEX_TAG | JSON_HEX_AMP) ?>;
window.irmPageData    = <?= json_encode($page_data,        JSON_HEX_TAG | JSON_HEX_AMP) ?>;
window.irmComponents  = <?= json_encode($component_names,  JSON_HEX_TAG) ?>;

const EMBED_HINTS = {
  youtube: 'YouTube: use the embed URL (youtube.com/embed/VIDEO_ID)',
  vimeo:   'Vimeo: use the embed URL (player.vimeo.com/video/ID)',
  pdf:     'PDF: paste a direct URL to the .pdf file',
  mp4:     'MP4: paste a direct URL to the .mp4 file',
  website: 'Website: paste the full URL of the page to embed',
};

let debounceTimer = null;

// Escape HTML for safe injection into JS-built innerHTML
function esc(str) {
  return String(str ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
}

function buildSlotPanels(layoutId) {
  const container = document.getElementById('slotContainer');
  container.innerHTML = '';
  const page = (window.irmLayoutPages || []).find(p => p.id === layoutId);
  if (!page) return;
  const savedSlots = (window.irmPageData && window.irmPageData.slots) || {};
  page.slots.forEach(slot => {
    container.appendChild(createSlotPanel(slot, savedSlots[slot.id] || null));
  });
  schedulePreview();
}

function createSlotPanel(slot, saved) {
  const id   = slot.id;
  const uid  = 'slot-' + id.replace(/[^a-z0-9]/g, '-');
  const sType = saved ? (saved.type || 'html') : 'html';

  // Component options
  let compOpts = '<option value="">— choose component —</option>';
  (window.irmComponents || []).forEach(cn => {
    const sel = (sType === 'component' && saved && saved.name === cn) ? ' selected' : '';
    compOpts += `<option value="${esc(cn)}"${sel}>${esc(cn)}</option>`;
  });

  const savedHtml  = sType === 'html'  ? esc(saved && saved.html  || '') : '';
  const savedUrl   = sType === 'embed' ? esc(saved && saved.url   || '') : '';
  const savedSub   = sType === 'embed' ? (saved && saved.subtype || 'youtube') : 'youtube';
  const savedTitle = sType === 'embed' ? esc(saved && saved.title || '') : '';

  const subtypeOpts = ['youtube','vimeo','pdf','mp4','website'].map(s => {
    const lbl = s === 'website' ? 'Website / iFrame' : (s.charAt(0).toUpperCase() + s.slice(1));
    return `<option value="${s}"${savedSub===s?' selected':''}>${lbl}</option>`;
  }).join('');

  function typeShow(t) { return sType === t ? '' : ' style="display:none"'; }
  function typeActive(t) { return sType === t ? ' active' : ''; }
  function typeChecked(t) { return sType === t ? ' checked' : ''; }

  const panel = document.createElement('div');
  panel.className = 'slot-panel';
  panel.dataset.slotId = id;
  panel.innerHTML = `
    <div class="slot-panel-header">
      <div class="fw-semibold small">${esc(slot.label)}</div>
      ${slot.hint ? `<div class="form-text text-muted mt-0" style="font-size:.78rem">${esc(slot.hint)}</div>` : ''}
    </div>
    <div class="p-3">
      <div class="btn-group btn-group-sm mb-3" role="group">
        <input type="radio" class="btn-check" name="slots[${id}][type]"
               id="${uid}-html" value="html"${typeChecked('html')} onchange="onSlotTypeChange(this)">
        <label class="btn btn-outline-secondary" for="${uid}-html">HTML</label>
        <input type="radio" class="btn-check" name="slots[${id}][type]"
               id="${uid}-component" value="component"${typeChecked('component')} onchange="onSlotTypeChange(this)">
        <label class="btn btn-outline-secondary" for="${uid}-component">Component</label>
        <input type="radio" class="btn-check" name="slots[${id}][type]"
               id="${uid}-embed" value="embed"${typeChecked('embed')} onchange="onSlotTypeChange(this)">
        <label class="btn btn-outline-secondary" for="${uid}-embed">Embed</label>
      </div>

      <div class="slot-type-fields${typeActive('html')}"
           data-for-type="html"${typeShow('html')}>
        <textarea class="form-control form-control-sm font-monospace" rows="5"
                  name="slots[${id}][html]"
                  placeholder="Plain HTML — &lt;h2&gt;, &lt;p&gt;, &lt;a&gt;, &lt;strong&gt; etc.">${savedHtml}</textarea>
      </div>

      <div class="slot-type-fields${typeActive('component')}"
           data-for-type="component"${typeShow('component')}>
        <select class="form-select form-select-sm" name="slots[${id}][name]"
                data-role="component-name">
          ${compOpts}
        </select>
      </div>

      <div class="slot-type-fields${typeActive('embed')}"
           data-for-type="embed"${typeShow('embed')}>
        <select class="form-select form-select-sm mb-2" name="slots[${id}][subtype]"
                onchange="updateEmbedHint(this)">
          ${subtypeOpts}
        </select>
        <input type="url" class="form-control form-control-sm mb-1"
               name="slots[${id}][url]" placeholder="https://…" value="${savedUrl}">
        <div class="form-text embed-hint">${esc(EMBED_HINTS[savedSub] || '')}</div>
        <input type="text" class="form-control form-control-sm mt-1"
               name="slots[${id}][title]" placeholder="Accessible title (optional)"
               value="${savedTitle}">
      </div>
    </div>
  `;
  return panel;
}

function onSlotTypeChange(radio) {
  const panel = radio.closest('[data-slot-id]');
  panel.querySelectorAll('.slot-type-fields').forEach(f => {
    const show = f.dataset.forType === radio.value;
    f.classList.toggle('active', show);
    f.style.display = show ? '' : 'none';
  });
  schedulePreview();
}

function onLayoutChange(sel) {
  buildSlotPanels(sel.value);
}

function updateEmbedHint(sel) {
  const panel = sel.closest('[data-slot-id]');
  const hint  = panel.querySelector('.embed-hint');
  if (hint) hint.textContent = EMBED_HINTS[sel.value] || '';
}

function collectLayout() {
  const layoutId = document.getElementById('layoutSelect').value;
  const slots    = {};
  document.querySelectorAll('#slotContainer [data-slot-id]').forEach(panel => {
    const sid     = panel.dataset.slotId;
    const typeRad = panel.querySelector('input[name$="[type]"]:checked');
    const type    = typeRad ? typeRad.value : 'html';
    if (type === 'html') {
      const ta = panel.querySelector('textarea');
      slots[sid] = { type, html: ta ? ta.value : '' };
    } else if (type === 'component') {
      const sel = panel.querySelector('[data-role="component-name"]');
      slots[sid] = { type, name: sel ? sel.value : '' };
    } else if (type === 'embed') {
      const st = panel.querySelector('select[name$="[subtype]"]');
      const ur = panel.querySelector('input[name$="[url]"]');
      const ti = panel.querySelector('input[name$="[title]"]');
      slots[sid] = {
        type,
        subtype: st ? st.value : 'youtube',
        url:     ur ? ur.value : '',
        title:   ti ? ti.value : '',
      };
    }
  });
  return { layout_id: layoutId, slots };
}

function schedulePreview() {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(fetchPreview, 600);
}

async function fetchPreview() {
  const statusEl = document.getElementById('previewStatus');
  statusEl.textContent = 'Updating…';
  try {
    const resp = await fetch('/admin/page_preview.php', {
      method:  'POST',
      headers: { 'Content-Type': 'application/json', 'X-Fetch': '1' },
      body:    JSON.stringify({ layout: collectLayout() }),
    });
    const data = await resp.json();
    if (data.ok) {
      document.getElementById('previewFrame').srcdoc = data.html;
      statusEl.textContent = '';
    } else {
      statusEl.textContent = data.msg || 'Preview error';
    }
  } catch {
    statusEl.textContent = 'Preview unavailable';
  }
}

function initDesigner() {
  const sel = document.getElementById('layoutSelect');
  if (window.irmPageData && window.irmPageData.layout_id) {
    sel.value = window.irmPageData.layout_id;
  }
  buildSlotPanels(sel.value);
}

document.addEventListener('DOMContentLoaded', function () {
  initDesigner();
  document.getElementById('pdForm').addEventListener('input',  schedulePreview);
  document.getElementById('pdForm').addEventListener('change', schedulePreview);
});
</script>

<?php require __DIR__ . '/_layout_end.php'; ?>
