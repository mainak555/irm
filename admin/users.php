<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';

require_auth('sa', 'admin');

$page = 'users';
require __DIR__ . '/_layout.php';
?>
<h2>Users</h2>
<p class="text-muted">User management is coming soon.</p>
<?php require __DIR__ . '/_layout_end.php'; ?>
