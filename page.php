<?php

declare(strict_types=1);

/**
 * page.php — public content pages, looked up by slug.
 */
require_once __DIR__ . '/includes/functions.php';

$slug = trim($_GET['slug'] ?? '');
$stmt = db()->prepare("SELECT title, body_html FROM pages WHERE slug = :s AND is_public = 1 LIMIT 1");
$stmt->execute([':s' => $slug]);
$page = $stmt->fetch();

if (!$page) {
    http_response_code(404);
    $page_title = 'Not found';
    $active_slug = $slug;
    require_once __DIR__ . '/includes/header.php';
    echo '<section class="container" style="padding:40px 0;"><h2 class="welcome-heading">Page not found</h2><p>The requested page does not exist or is not public.</p></section></div>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$page_title  = $page['title'];
$active_slug = $slug;
require_once __DIR__ . '/includes/header.php';
?>

<section class="container" style="padding:30px 0 50px; background: var(--cream);">
  <h2 class="welcome-heading" style="font-size:24px;"><?= h($page['title']) ?></h2>
  <div class="body-text" style="max-width:780px;"><?= $page['body_html'] ?></div>
</section>
</div><!-- /.band-cream wrapper from header -->

<?php require_once __DIR__ . '/includes/footer.php'; ?>
