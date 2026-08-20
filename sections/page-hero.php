<?php
/* ------------------------------------------------------------
   Dark band at the top of every subpage.

   Not just decoration: over the hero the header bar is transparent and
   sets its text to white. Without dark pixels beneath it, that would be
   white on cream — which is why every page without a hero has this band.

   $eyebrow, $title   the two lines
   $lead              optional lead paragraph
   $actions           optional section with buttons below it
                      (name of a file from sections/)
   ------------------------------------------------------------ */

$lead = $lead ?? null;
$actions = $actions ?? null;
?>
  <!-- ================= PAGE HERO ================= -->
  <section class="page-hero">
    <div class="wrap wrap--prose">
      <p class="eyebrow"><?= esc($eyebrow) ?></p>
      <h1><?= esc($title) ?></h1>
<?php if ($lead): ?>
      <p class="lead page-hero__lead"><?= esc($lead) ?></p>
<?php endif; ?>
<?php if ($actions): ?>
<?= indent_block(section_html($actions), '      ') ?>

<?php endif; ?>
    </div>
  </section>
