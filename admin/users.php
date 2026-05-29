<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db_users.php';
require_once __DIR__ . '/../config.php';

require_auth('sa', 'admin');

$user = current_user();

$users = users_list();

require __DIR__ . '/_layout.php';
?>

<div class="d-flex align-items-center justify-content-between mb-4">
  <h2 class="mb-0">Users</h2>
  <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addUserModal">
    + Add User
  </button>
</div>

<!-- Password reveal — shown once after a reset, dismissed clears plaintext -->
<div id="pwdReveal" class="alert alert-success alert-dismissible d-none mb-3" role="alert">
  <strong>Password reset.</strong> Copy this password now &mdash; it will not be shown again.
  <div class="mt-2 d-flex align-items-center gap-2 flex-wrap">
    <code id="pwdText" class="fs-6 px-2 py-1 rounded"
          style="background:var(--irm-muted);letter-spacing:.07em"></code>
    <button type="button" class="btn btn-sm btn-outline-success" id="pwdCopy">Copy</button>
  </div>
  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"
          onclick="document.getElementById('pwdText').textContent=''"></button>
</div>

<!-- Users table -->
<div class="card">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead>
          <tr>
            <th class="ps-3" style="width:3rem">#</th>
            <th style="width:5rem">Active</th>
            <th>Name</th>
            <th>Email</th>
            <th style="width:11rem">Role</th>
            <th style="width:4rem" class="text-center">SSO</th>
            <th style="width:3rem"></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($users as $i => $u): ?>
          <?php
            $is_sa     = ($u['email'] === 'admin' && $u['role'] === 'sa');
            $is_me     = (int) $u['id'] === (int) $user['id'];
            $is_higher = role_rank($u['role']) > role_rank($user['role']);
            $is_peer   = !$is_sa && !$is_me && !$is_higher && $user['role'] !== 'sa' && (role_rank($u['role']) === role_rank($user['role']));
            $locked    = $is_sa || $is_me || $is_higher;
            $tr_class  = ($is_sa || $is_higher) ? 'irm-sa-row' : ($is_me ? 'irm-me-row' : ($is_peer ? 'irm-peer-row' : ''));
          ?>
          <tr data-id="<?= (int) $u['id'] ?>"<?= $is_sa ? ' data-sa="1"' : '' ?><?= $tr_class ? ' class="' . $tr_class . '"' : '' ?>>

            <!-- Serial -->
            <td class="ps-3 text-muted small"><?= $i + 1 ?></td>

            <!-- Active toggle -->
            <td class="col-active">
              <div class="form-check form-switch mb-0">
                <input type="checkbox"
                       class="form-check-input<?= $locked ? '' : ' js-toggle-active' ?>"
                       role="switch"
                       data-id="<?= (int) $u['id'] ?>"
                       <?= (int) $u['is_active'] ? 'checked' : '' ?>
                       <?= $locked ? 'disabled' : '' ?>
                       aria-label="Active">
              </div>
            </td>

            <!-- Name -->
            <td class="user-name fw-medium">
              <?= h($u['name']) ?>
              <?php if ($is_me): ?>
                <span class="badge text-bg-secondary ms-1" style="font-size:.65rem;opacity:.7">You</span>
              <?php endif; ?>
            </td>

            <!-- Email -->
            <td class="text-muted small"><?= $is_sa ? '&mdash;' : h($u['email'] ?? '') ?></td>

            <!-- Role -->
            <td>
              <?php if ($locked || $is_peer): ?>
                <span class="badge badge-role-<?= h($u['role']) ?>"><?= h(strtoupper($u['role'])) ?></span>
              <?php else: ?>
                <select class="form-select form-select-sm js-role-select"
                        data-id="<?= (int) $u['id'] ?>"
                        data-prev="<?= h($u['role']) ?>"
                        aria-label="Role">
                  <?php foreach (['sa' => 'Super Admin', 'admin' => 'Admin', 'faculty' => 'Faculty', 'user' => 'User'] as $rv => $rl): ?>
                    <?php if ($rv === 'sa' && $user['role'] !== 'sa'): continue; endif; ?>
                    <option value="<?= h($rv) ?>" <?= $u['role'] === $rv ? 'selected' : '' ?>>
                      <?= h($rl) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              <?php endif; ?>
            </td>

            <!-- SSO -->
            <td class="text-center">
              <div class="form-check mb-0 d-flex justify-content-center">
                <input type="checkbox"
                       class="form-check-input<?= ($locked || $is_peer) ? '' : ' js-toggle-sso' ?>"
                       data-id="<?= (int) $u['id'] ?>"
                       <?= (int) $u['sso'] ? 'checked' : '' ?>
                       <?= ($locked || $is_peer) ? 'disabled' : '' ?>
                       aria-label="SSO">
              </div>
            </td>

            <!-- Actions 3-dot menu -->
            <td>
              <?php if (!$locked && !$is_peer): ?>
              <div class="dropdown">
                <button class="btn btn-sm btn-link text-body text-decoration-none p-0 lh-1"
                        type="button"
                        data-bs-toggle="dropdown"
                        aria-expanded="false"
                        aria-label="User actions">&#8942;</button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                  <li>
                    <button class="dropdown-item js-edit-name"
                            data-id="<?= (int) $u['id'] ?>"
                            data-name="<?= h($u['name']) ?>">Edit Name</button>
                  </li>
                  <?php if (!(int) $u['sso']): ?>
                  <li>
                    <button class="dropdown-item js-reset-pwd"
                            data-id="<?= (int) $u['id'] ?>">Reset Password</button>
                  </li>
                  <?php endif; ?>
                  <li><hr class="dropdown-divider"></li>
                  <li>
                    <button class="dropdown-item text-danger js-delete"
                            data-id="<?= (int) $u['id'] ?>"
                            data-name="<?= h($u['name']) ?>">Delete</button>
                  </li>
                </ul>
              </div>
              <?php endif; ?>
            </td>

          </tr>
          <?php endforeach; ?>
          <?php if (empty($users)): ?>
          <tr><td colspan="7" class="text-center text-muted py-4">No users found.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1"
     aria-labelledby="addUserModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="addUserForm" method="post" novalidate>
        <div class="modal-header">
          <h5 class="modal-title" id="addUserModalLabel">Add User</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"
                  aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div id="addError" class="alert alert-danger py-2 d-none" role="alert"></div>
          <div class="form-check mb-3">
            <input type="checkbox" id="addSso" name="sso"
                   class="form-check-input" value="1">
            <label class="form-check-label" for="addSso">
              SSO User <span class="text-muted small">(no password required)</span>
            </label>
          </div>
          <div class="mb-3">
            <label class="form-label" for="addName">Name</label>
            <input type="text" id="addName" name="name" class="form-control"
                   autocomplete="off">
            <div class="invalid-feedback">Name is required.</div>
          </div>
          <div class="mb-3">
            <label class="form-label" for="addEmail">Email</label>
            <input type="email" id="addEmail" name="email" class="form-control"
                   autocomplete="off">
            <div class="invalid-feedback">A valid email address is required.</div>
          </div>
          <div class="mb-0" id="pwdFieldWrap">
            <label class="form-label" for="addPassword">Password</label>
            <input type="password" id="addPassword" name="password"
                   class="form-control" autocomplete="new-password">
            <div class="invalid-feedback">
              Min 8 chars &middot; 1 uppercase &middot; 1 number &middot; 1 special character
            </div>
            <div class="pwd-hint mt-1">
              Min 8 chars &middot; 1 uppercase &middot; 1 number &middot; 1 special character
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label" for="addRole">Role</label>
            <select id="addRole" name="role" class="form-select">
              <?php foreach (['user' => 'User', 'faculty' => 'Faculty', 'admin' => 'Admin', 'sa' => 'Super Admin'] as $rv => $rl): ?>
                <?php if (role_rank($rv) <= role_rank($user['role'])): ?>
                  <option value="<?= h($rv) ?>"<?= $rv === 'user' ? ' selected' : '' ?>><?= h($rl) ?></option>
                <?php endif; ?>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary"
                  data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Add User</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Edit Name Modal -->
<div class="modal fade" id="editNameModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-sm">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Edit Name</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"
                aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="editNameId">
        <label class="visually-hidden" for="editNameInput">Name</label>
        <input type="text" id="editNameInput" class="form-control"
               placeholder="Full name" autocomplete="off">
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary"
                data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" id="editNameSave">Save</button>
      </div>
    </div>
  </div>
</div>

<style>
.irm-sa-row { background-color: var(--irm-muted) !important; }
.irm-sa-row td { opacity: .75; }
.irm-me-row td { opacity: .65; }
.irm-peer-row { background-color: var(--irm-muted) !important; }
.irm-peer-row td:not(.col-active) { opacity: .75; }
</style>

<script>
window.addEventListener('load', function () {
  'use strict';

  const CSRF = <?= json_encode($_SESSION['csrf'] ?? '') ?>;

  async function ajaxPost(data) {
    const fd = new FormData();
    fd.append('csrf', CSRF);
    for (const [k, v] of Object.entries(data)) fd.append(k, String(v));
    const res = await fetch('/admin/users_ajax.php', { method: 'POST', body: fd });
    return res.json();
  }

  // Active toggle
  document.querySelectorAll('.js-toggle-active').forEach(cb => {
    cb.addEventListener('change', async function () {
      const prev = !this.checked;
      try {
        const res = await ajaxPost({ action: 'toggle_active', id: this.dataset.id });
        if (!res.ok) { this.checked = prev; alert(res.msg || 'Failed to update.'); }
      } catch { this.checked = prev; alert('Network error.'); }
    });
  });

  // Role dropdown
  document.querySelectorAll('.js-role-select').forEach(sel => {
    sel.addEventListener('change', async function () {
      const prev = this.dataset.prev;
      try {
        const res = await ajaxPost({ action: 'update_role', id: this.dataset.id, role: this.value });
        if (!res.ok) { this.value = prev; alert(res.msg || 'Failed to update.'); }
        else { this.dataset.prev = this.value; }
      } catch { this.value = prev; alert('Network error.'); }
    });
  });

  // SSO toggle
  document.querySelectorAll('.js-toggle-sso').forEach(cb => {
    cb.addEventListener('change', async function () {
      const prev = !this.checked;
      try {
        const res = await ajaxPost({ action: 'toggle_sso', id: this.dataset.id });
        if (!res.ok) { this.checked = prev; alert(res.msg || 'Failed to update.'); }
      } catch { this.checked = prev; alert('Network error.'); }
    });
  });

  // Edit Name
  document.querySelectorAll('.js-edit-name').forEach(btn => {
    btn.addEventListener('click', function () {
      document.getElementById('editNameId').value    = this.dataset.id;
      document.getElementById('editNameInput').value = this.dataset.name;
      bootstrap.Modal.getOrCreateInstance(document.getElementById('editNameModal')).show();
    });
  });

  document.getElementById('editNameSave').addEventListener('click', async function () {
    const id   = document.getElementById('editNameId').value;
    const name = document.getElementById('editNameInput').value.trim();
    if (!name) { alert('Name cannot be empty.'); return; }
    try {
      const res = await ajaxPost({ action: 'update_name', id, name });
      if (res.ok) {
        const row = document.querySelector(`tr[data-id="${id}"]`);
        if (row) row.querySelector('.user-name').textContent = name;
        document.querySelectorAll(`.js-edit-name[data-id="${id}"]`)
          .forEach(b => { b.dataset.name = name; });
        bootstrap.Modal.getInstance(document.getElementById('editNameModal'))?.hide();
      } else {
        alert(res.msg || 'Failed to save name.');
      }
    } catch { alert('Network error.'); }
  });

  // Reset Password
  document.querySelectorAll('.js-reset-pwd').forEach(btn => {
    btn.addEventListener('click', async function () {
      if (!confirm("Reset this user's password? A new password will be shown once.")) return;
      try {
        const res = await ajaxPost({ action: 'reset_password', id: this.dataset.id });
        if (res.ok) {
          const reveal = document.getElementById('pwdReveal');
          document.getElementById('pwdText').textContent = res.password;
          document.getElementById('pwdCopy').textContent = 'Copy';
          reveal.classList.remove('d-none');
          reveal.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        } else {
          alert(res.msg || 'Failed to reset password.');
        }
      } catch { alert('Network error.'); }
    });
  });

  document.getElementById('pwdCopy').addEventListener('click', function () {
    const pwd = document.getElementById('pwdText').textContent;
    navigator.clipboard.writeText(pwd)
      .then(() => {
        this.textContent = 'Copied!';
        setTimeout(() => { this.textContent = 'Copy'; }, 2000);
      })
      .catch(() => { alert('Copy failed — please copy manually.'); });
  });

  // Delete
  document.querySelectorAll('.js-delete').forEach(btn => {
    btn.addEventListener('click', async function () {
      if (!confirm(`Delete "${this.dataset.name}"? This cannot be undone.`)) return;
      const id = this.dataset.id;
      try {
        const res = await ajaxPost({ action: 'delete', id });
        if (res.ok) {
          document.querySelector(`tr[data-id="${id}"]`)?.remove();
        } else {
          alert(res.msg || 'Failed to delete user.');
        }
      } catch { alert('Network error.'); }
    });
  });

  // Add User modal — client-side validation + AJAX submit
  const addUserForm = document.getElementById('addUserForm');
  const addErrorEl  = document.getElementById('addError');
  const PWD_PATTERN = /^(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/;

  function addClearErrors() {
    addErrorEl.classList.add('d-none');
    addErrorEl.textContent = '';
    addUserForm.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
  }

  addUserForm.addEventListener('submit', async function (e) {
    e.preventDefault();
    addClearErrors();

    const name  = document.getElementById('addName').value.trim();
    const email = document.getElementById('addEmail').value.trim();
    const sso   = document.getElementById('addSso').checked ? 1 : 0;
    const pwd   = document.getElementById('addPassword').value;

    let valid = true;
    if (!name) {
      document.getElementById('addName').classList.add('is-invalid');
      valid = false;
    }
    if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
      document.getElementById('addEmail').classList.add('is-invalid');
      valid = false;
    }
    const pwdInp = document.getElementById('addPassword');
    if (!sso && !PWD_PATTERN.test(pwd)) {
      pwdInp.classList.add('is-invalid');
      valid = false;
    }
    if (!valid) return;

    const fd = new FormData(this);
    fd.set('action', 'add_user');
    fd.set('csrf', CSRF);
    try {
      const res  = await fetch('/admin/users_ajax.php', { method: 'POST', body: fd });
      const data = await res.json();
      if (data.ok) {
        bootstrap.Modal.getInstance(document.getElementById('addUserModal')).hide();
        window.location.reload();
      } else {
        addErrorEl.textContent = data.msg || 'Failed to add user.';
        addErrorEl.classList.remove('d-none');
      }
    } catch {
      addErrorEl.textContent = 'Network error. Please try again.';
      addErrorEl.classList.remove('d-none');
    }
  });

  // Reset modal on close
  document.getElementById('addUserModal').addEventListener('hidden.bs.modal', function () {
    addClearErrors();
    addUserForm.reset();
    document.getElementById('addPassword').disabled = false;
  });

  // SSO toggle — disable/enable password field
  document.getElementById('addSso').addEventListener('change', function () {
    const inp = document.getElementById('addPassword');
    if (this.checked) {
      inp.disabled = true;
      inp.value    = '';
      inp.classList.remove('is-invalid');
    } else {
      inp.disabled = false;
    }
  });

});
</script>

<?php require __DIR__ . '/_layout_end.php'; ?>
