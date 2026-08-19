<?php
/* ------------------------------------------------------------
   Dunkles Band am Kopf jeder Unterseite.

   Nicht nur Gestaltung: die Kopfleiste ist über dem Hero durchsichtig und
   setzt ihren Text auf Weiß. Ohne dunkle Pixel darunter stünde weiß auf
   creme — deshalb hat jede Seite ohne Hero dieses Band.

   $eyebrow, $title   die beiden Zeilen
   $lead              optionaler Vorspann
   $actions           optionaler Abschnitt mit Knöpfen darunter
                      (Name einer Datei aus sections/)
   ------------------------------------------------------------ */

$lead = $lead ?? null;
$actions = $actions ?? null;
?>
  <!-- ================= SEITENKOPF ================= -->
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
