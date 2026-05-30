<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config.php';

require_auth('sa', 'admin');

$user = current_user();

$carousel_dir = __DIR__ . '/../assets/img/carousel/';
$slides_json  = __DIR__ . '/../config/slides.json';
$max_bytes    = (int) (env('UPLOAD_MAX_BYTES', (string) (5 * 1024 * 1024)) ?? (5 * 1024 * 1024));

function carousel_load_captions(string $path): array
{
    if (!is_readable($path)) {
        return [];
    }
    $data = json_decode((string) file_get_contents($path), true);
    return is_array($data) ? $data : [];
}

function carousel_save_captions(string $path, array $captions): void
{
    file_put_contents(
        $path,
        json_encode($captions, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        LOCK_EX
    );
}

// ── POST handlers ─────────────────────────────────────────────────────────────

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        http_response_code(400);
        exit('Invalid CSRF token.');
    }

    $action = $_POST['action'] ?? '';

    // ── Upload ────────────────────────────────────────────────────────────────
    if ($action === 'upload') {
        $is_fetch = !empty($_SERVER['HTTP_X_FETCH']);
        $max_mb   = round($max_bytes / (1024 * 1024), 1);

        function upload_reply(bool $ok, string $msg, bool $is_fetch, string $filename = ''): never
        {
            if ($is_fetch) {
                header('Content-Type: application/json');
                $payload = ['ok' => $ok, 'msg' => $msg];
                if ($filename !== '') {
                    $payload['filename'] = $filename;
                }
                echo json_encode($payload);
                exit;
            }
            $_SESSION['flash'] = ['type' => $ok ? 'ok' : 'err', 'msg' => $msg];
            header('Location: /admin/carousel.php');
            exit;
        }

        $file = $_FILES['image'] ?? null;

        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            $err_msg = match ($file['error'] ?? -1) {
                UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'File too large for server. Max: ' . ini_get('upload_max_filesize') . '.',
                UPLOAD_ERR_NO_FILE                        => 'No file selected.',
                default                                   => 'Upload failed. Please try again.',
            };
            upload_reply(false, $err_msg, $is_fetch);
        }

        if ($file['size'] > $max_bytes) {
            upload_reply(false, 'File exceeds the ' . $max_mb . ' MB limit.', $is_fetch);
        }

        $img_info          = @getimagesize($file['tmp_name']);
        $allowed_img_types = [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_WEBP];
        if ($img_info === false || !in_array($img_info[2], $allowed_img_types, true)) {
            upload_reply(false, 'File type not allowed. Use jpg, png, gif, or webp.', $is_fetch);
        }

        $ext      = strtolower((string) pathinfo($file['name'], PATHINFO_EXTENSION));
        $stem     = (string) pathinfo($file['name'], PATHINFO_FILENAME);
        $stem     = (string) preg_replace('/[^a-zA-Z0-9_-]/', '_', $stem);
        $stem     = trim((string) preg_replace('/_+/', '_', $stem), '_');
        $filename = ($stem ?: 'slide') . '.' . $ext;

        if (!move_uploaded_file($file['tmp_name'], $carousel_dir . $filename)) {
            upload_reply(false, 'Could not save the uploaded file.', $is_fetch);
        }

        upload_reply(true, 'Image uploaded: ' . $filename, $is_fetch, $filename);
    }

    // ── Caption save ──────────────────────────────────────────────────────────
    if ($action === 'caption') {
        $filename = basename($_POST['filename'] ?? '');
        $caption  = trim($_POST['caption'] ?? '');

        if ($filename === '' || !file_exists($carousel_dir . $filename)) {
            $_SESSION['flash'] = ['type' => 'err', 'msg' => 'Image not found.'];
            header('Location: /admin/carousel.php');
            exit;
        }

        $captions = carousel_load_captions($slides_json);
        if ($caption === '') {
            unset($captions[$filename]);
        } else {
            $captions[$filename] = $caption;
        }
        carousel_save_captions($slides_json, $captions);

        $_SESSION['flash'] = ['type' => 'ok', 'msg' => 'Caption saved.'];
        header('Location: /admin/carousel.php');
        exit;
    }

    // ── Delete ────────────────────────────────────────────────────────────────
    if ($action === 'delete') {
        $filename = basename($_POST['filename'] ?? '');
        $filepath = $carousel_dir . $filename;

        if ($filename === '' || !file_exists($filepath)) {
            $_SESSION['flash'] = ['type' => 'err', 'msg' => 'Image not found.'];
            header('Location: /admin/carousel.php');
            exit;
        }

        unlink($filepath);

        $captions = carousel_load_captions($slides_json);
        if (isset($captions[$filename])) {
            unset($captions[$filename]);
            carousel_save_captions($slides_json, $captions);
        }

        $_SESSION['flash'] = ['type' => 'ok', 'msg' => 'Slide deleted.'];
        header('Location: /admin/carousel.php');
        exit;
    }
}

// ── GET: load slide list ──────────────────────────────────────────────────────

$raw_paths = glob($carousel_dir . '*.{jpg,jpeg,png,gif,webp}', GLOB_BRACE);
if (!is_array($raw_paths)) {
    $raw_paths = [];
}
natsort($raw_paths);
$images   = array_values($raw_paths);
$captions = carousel_load_captions($slides_json);

require __DIR__ . '/_layout.php';
?>

<style>
.irm-cm          { display:flex; flex-direction:column; height:calc(100vh - 130px); }
.irm-dz          { border:2px dashed var(--irm-border); border-radius:var(--irm-radius);
                   background:var(--irm-muted); color:var(--irm-muted-fg);
                   text-align:center; padding:16px; cursor:pointer; flex-shrink:0;
                   transition:border-color .15s, background .15s; position:relative; }
.irm-dz.dz-over  { border-color:var(--irm-primary); background:color-mix(in srgb, var(--irm-primary) 6%, transparent); }
.irm-dz input    { position:absolute; inset:0; opacity:0; cursor:pointer; width:100%; }
.irm-ws          { display:flex; flex:1; min-height:0; border:1px solid var(--irm-border);
                   border-radius:var(--irm-radius); overflow:hidden; margin-top:10px; }
.irm-strip       { width:20%; flex-shrink:0; overflow-y:auto; border-right:1px solid var(--irm-border);
                   background:var(--irm-muted); }
.irm-thumb       { padding:6px; cursor:pointer; border-bottom:1px solid var(--irm-border);
                   border-left:3px solid transparent; opacity:.7; transition:opacity .1s, border-color .1s; }
.irm-thumb:hover { opacity:1; background:var(--irm-card); }
.irm-thumb.sel   { opacity:1; background:var(--irm-card); border-left-color:var(--irm-primary); }
.irm-thumb img   { width:100%; height:72px; object-fit:cover; border-radius:3px; display:block; }
.irm-thumb-lbl   { font-size:10px; color:var(--irm-muted-fg); margin-top:3px;
                   white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.irm-detail      { flex:1; display:flex; flex-direction:column; min-width:0; overflow:hidden; }
.irm-prev        { flex:1; display:flex; align-items:center; justify-content:center;
                   background:var(--irm-muted); overflow:hidden; }
.irm-prev img    { max-width:100%; max-height:100%; object-fit:contain; display:block; }
.irm-foot        { padding:12px 16px; border-top:1px solid var(--irm-border);
                   background:var(--irm-card); flex-shrink:0; }
.irm-empty       { flex:1; display:flex; flex-direction:column; align-items:center;
                   justify-content:center; color:var(--irm-muted-fg); gap:10px; }
@keyframes dzpulse { from { border-color:var(--irm-border); } to { border-color:var(--irm-primary); } }
.dz-uploading    { animation:dzpulse .7s ease-in-out infinite alternate; }
.irm-dz-bar-track { position:absolute; top:0; left:0; right:0; height:3px;
                    background:var(--irm-muted); overflow:hidden;
                    border-radius:var(--irm-radius) var(--irm-radius) 0 0; }
.irm-dz-bar-fill  { height:100%; background:var(--irm-primary); transition:width .15s ease; }
</style>

<div class="px-4 py-3 d-flex flex-column" style="height:calc(100vh - 57px);overflow:hidden">

  <div class="d-flex align-items-baseline gap-2 mb-2 flex-shrink-0">
    <h4 class="mb-0">Carousel</h4>
    <span class="text-muted small"><?= count($images) ?> slide<?= count($images) !== 1 ? 's' : '' ?></span>
  </div>

  <div class="irm-cm">

    <!-- Drop zone -->
    <div class="irm-dz" id="dropZone">
      <div id="dzBar" class="irm-dz-bar-track" hidden>
        <div id="dzBarFill" class="irm-dz-bar-fill" style="width:0%"></div>
      </div>
      <input type="file" id="dzInput" accept=".jpg,.jpeg,.png,.gif,.webp" multiple>
      <span id="dzLabel">
        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"
             style="vertical-align:middle;margin-right:5px;margin-bottom:2px">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
        </svg>
        Drop images here or <strong>click to browse</strong>
        <span class="text-muted" style="font-size:.8em"> — jpg, png, gif, webp · max <?= round($max_bytes / (1024 * 1024), 1) ?> MB</span>
      </span>
    </div>

    <!-- Workspace -->
    <div class="irm-ws">

      <!-- Filmstrip -->
      <div class="irm-strip" id="filmstrip">
        <?php if (empty($images)): ?>
          <div class="p-3 text-muted small text-center" style="font-size:11px">No slides yet</div>
        <?php else: ?>
          <?php foreach ($images as $i => $path):
            $fn  = basename($path);
            $src = '/assets/img/carousel/' . rawurlencode($fn);
            $cap = $captions[$fn] ?? '';
          ?>
          <div class="irm-thumb <?= $i === 0 ? 'sel' : '' ?>"
               data-src="<?= h($src) ?>"
               data-filename="<?= h($fn) ?>"
               data-caption="<?= h($cap) ?>">
            <img src="<?= h($src) ?>" alt="<?= h($fn) ?>" loading="lazy">
            <div class="irm-thumb-lbl"><?= h($fn) ?></div>
          </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <!-- Detail panel -->
      <div class="irm-detail">
        <?php if (empty($images)): ?>
          <div class="irm-empty">
            <svg width="48" height="48" fill="none" stroke="currentColor" viewBox="0 0 24 24" opacity=".25">
              <rect x="3" y="3" width="18" height="18" rx="2" stroke-width="1.5"/>
              <path d="M3 9l4-4 4 4 4-5 4 5" stroke-width="1.5"/>
              <circle cx="8.5" cy="13.5" r="1.5" stroke-width="1.5"/>
            </svg>
            <span class="small">Upload slides using the drop zone above</span>
          </div>
        <?php else: ?>

          <!-- Preview -->
          <div class="irm-prev">
            <img id="detailImg" src="" alt="">
          </div>

          <!-- Footer -->
          <div class="irm-foot">
            <div class="d-flex align-items-center gap-2" style="width:100%;min-width:0">

              <code id="detailName" class="text-muted small flex-shrink-0"
                    style="max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"
                    title=""></code>

              <form method="post" class="d-flex gap-2 align-items-center flex-grow-1" style="min-width:0">
                <input type="hidden" name="csrf"     value="<?= h($_SESSION['csrf'] ?? '') ?>">
                <input type="hidden" name="action"   value="caption">
                <input type="hidden" name="filename" id="capFile">
                <input type="text"   name="caption"  id="capInput"
                       class="form-control form-control-sm flex-grow-1"
                       placeholder="Caption (optional)" style="min-width:0">
                <button type="submit" class="btn btn-primary btn-sm text-nowrap flex-shrink-0">Save</button>
              </form>

              <form method="post" class="flex-shrink-0"
                    onsubmit="return confirm('Delete ' + document.getElementById('delFile').value + '?')">
                <input type="hidden" name="csrf"     value="<?= h($_SESSION['csrf'] ?? '') ?>">
                <input type="hidden" name="action"   value="delete">
                <input type="hidden" name="filename" id="delFile">
                <button type="submit" class="btn btn-outline-danger btn-sm">Delete</button>
              </form>

            </div>
          </div>
        <?php endif; ?>
      </div>

    </div>
  </div>
</div>

<script>
(function () {
  const csrf    = <?= json_encode($_SESSION['csrf'] ?? '') ?>;
  const maxBytes = <?= $max_bytes ?>;

  // ── Filmstrip selection ───────────────────────────────────────────────────
  const thumbs  = document.querySelectorAll('.irm-thumb');
  const dImg    = document.getElementById('detailImg');
  const dName   = document.getElementById('detailName');
  const capFile = document.getElementById('capFile');
  const capInp  = document.getElementById('capInput');
  const delFile = document.getElementById('delFile');

  function select(thumb) {
    thumbs.forEach(t => t.classList.remove('sel'));
    thumb.classList.add('sel');
    thumb.scrollIntoView({ block: 'nearest' });
    const { src, filename, caption } = thumb.dataset;
    if (dImg)    { dImg.src = src; dImg.alt = filename; }
    if (dName)   { dName.textContent = filename; dName.title = filename; }
    if (capFile) capFile.value = filename;
    if (capInp)  capInp.value  = caption;
    if (delFile) delFile.value = filename;
  }

  thumbs.forEach(t => t.addEventListener('click', () => select(t)));
  if (thumbs.length) select(thumbs[0]);

  // ── Drag-and-drop upload ──────────────────────────────────────────────────
  const dz       = document.getElementById('dropZone');
  const dzInput  = document.getElementById('dzInput');
  const dzLabel  = document.getElementById('dzLabel');
  const dzBar    = document.getElementById('dzBar');
  const dzBarFill = document.getElementById('dzBarFill');
  if (!dz) return;

  dz.addEventListener('dragover',  e => { e.preventDefault(); dz.classList.add('dz-over'); });
  dz.addEventListener('dragleave', e => { if (!dz.contains(e.relatedTarget)) dz.classList.remove('dz-over'); });
  dz.addEventListener('drop',      e => { e.preventDefault(); dz.classList.remove('dz-over'); upload(e.dataTransfer.files); });
  dzInput.addEventListener('change', e => { upload(e.target.files); e.target.value = ''; });

  function upload(files) {
    const fileList = [...files];
    if (!fileList.length) return;

    const maxMb  = (maxBytes / (1024 * 1024)).toFixed(1);
    const results = { ok: [], err: [] };
    const toUpload = [];

    fileList.forEach(f => {
      if (f.size > maxBytes) {
        results.err.push(f.name + ': exceeds ' + maxMb + ' MB limit');
      } else {
        toUpload.push(f);
      }
    });

    const total = toUpload.length;

    dz.classList.add('dz-uploading');
    dzBar.hidden = false;
    dzBarFill.style.width = '0%';
    dzLabel.textContent = total ? ('Uploading 0 / ' + total + '…') : 'Validating…';

    if (!total) { finalize(results); return; }

    let done = 0;

    toUpload.forEach(file => {
      const fd = new FormData();
      fd.append('csrf',   csrf);
      fd.append('action', 'upload');
      fd.append('image',  file);
      fetch('/admin/carousel.php', {
        method:  'POST',
        headers: { 'X-Fetch': '1' },
        body:    fd,
      })
        .then(r => r.json())
        .then(data => {
          if (data.ok) results.ok.push(data.filename);
          else         results.err.push(file.name + ': ' + (data.msg || 'Upload failed'));
        })
        .catch(() => results.err.push(file.name + ': network error'))
        .finally(() => {
          done++;
          dzBarFill.style.width = Math.round(done / total * 100) + '%';
          dzLabel.textContent   = 'Uploading ' + done + ' / ' + total + '…';
          if (done === total) finalize(results);
        });
    });
  }

  function finalize(results) {
    dz.classList.remove('dz-uploading');
    dzBar.hidden = true;

    const okN  = results.ok.length;
    const errN = results.err.length;
    let type, msg;

    if (errN === 0) {
      type = 'ok';
      msg  = okN === 1 ? 'Image uploaded: ' + results.ok[0] : okN + ' images uploaded.';
    } else if (okN === 0) {
      type = 'err';
      msg  = results.err.join(' · ');
    } else {
      type = 'err';
      msg  = okN + ' uploaded · ' + errN + ' failed: ' + results.err.join(', ');
    }

    sessionStorage.setItem('irmFlash', JSON.stringify({ type, msg }));
    location.reload();
  }
})();
</script>

<?php require __DIR__ . '/_layout_end.php'; ?>
