<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

require_auth();

$user = current_user();

require __DIR__ . '/_layout.php';
?>

<div class="d-flex align-items-center gap-2 mb-4">
  <h2 class="mb-0">Dashboard</h2>
  <span class="badge badge-role-<?= h($user['role']) ?>"><?= h(strtoupper($user['role'])) ?></span>
</div>

<div class="card" style="max-width:480px">
  <div class="card-body">
    <p class="mb-1">Welcome back, <strong><?= h($user['name']) ?></strong>.</p>
    <p class="text-muted small mb-0">Use the sidebar to navigate the admin panel.</p>
  </div>
</div>

<?php require __DIR__ . '/_layout_end.php'; ?>
