<?php
declare(strict_types=1);
// In scope: $render_slot (callable — fn(string $slot_id): void)
?>
<section class="layout-intro">
  <div class="container">
    <?php ($render_slot)('intro'); ?>
  </div>
</section>

<section class="layout-content">
  <div class="container">
    <div class="row g-4">
      <div class="col-md-8 layout-main">
        <?php ($render_slot)('main'); ?>
      </div>
      <div class="col-md-4 layout-sidebar">
        <?php ($render_slot)('sidebar'); ?>
      </div>
    </div>
  </div>
</section>
