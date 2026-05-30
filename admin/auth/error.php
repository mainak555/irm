<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config.php';

$msg = $_SESSION['oidc_provision_error'] ?? '';
unset($_SESSION['oidc_provision_error']);

if ($msg === '') {
    $msg = 'Access could not be completed.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Access Error</title>
<script>(function(){var t=window.matchMedia('(prefers-color-scheme: dark)').matches?'dark':'light';document.documentElement.setAttribute('data-bs-theme',t);})();</script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body class="grain-texture auth-page-bg">
<div class="auth-card w-100 px-3">
  <div class="card shadow-sm">
    <div class="card-body p-4">
      <h4 class="card-title mb-1">Access Not Granted</h4>
      <p class="text-muted small mb-3"><?= h($msg) ?></p>
      <p class="text-muted mb-4">Please contact an administrator to request access.</p>
      <a href="/admin/login.php" class="btn btn-primary w-100">Back to Login</a>
    </div>
  </div>
</div>
</body>
</html>
