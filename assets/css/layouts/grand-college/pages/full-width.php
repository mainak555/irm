<?php
declare(strict_types=1);
// In scope: $render_slot (callable — fn(string $slot_id): void)
?>
<section class="layout-content">
  <div class="container">
    <?php ($render_slot)('main'); ?>
  </div>
</section>
