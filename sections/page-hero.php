<?php
/* ------------------------------------------------------------
   The heading of every subpage: eyebrow and title, nothing else.

   It used to be a dark band with a lead paragraph under the title — the
   header bar was transparent over the hero back then and needed dark pixels
   beneath it. The bar brings its own ground now, so this is a plain heading
   on the page's own light ground, and it stays as flat as it can: the lead
   only restated the title in longer words and held what people came for a
   screenful down. Styles in public/css/04-layout.css, beside .section-head.

   $eyebrow, $title   the two lines
   $wide              the heading of a page whose content runs at the page
                      measure — /termine/ and /archiv/ open with a band and
                      a full-width slider, not with reading text. It puts
                      the title in the same column as the thing it names,
                      one size up, and closes it with a rule. Without it
                      the heading stays in the reading column, which is
                      where pages made of text want it.
   $actions           optional section with buttons below it
                      (name of a file from sections/)
   ------------------------------------------------------------ */

$wide = $wide ?? false;
$actions = $actions ?? null;

/* Resolved here rather than in the markup: the two depend on each other —
   the modifier carries the styles, the wrap carries the width they were
   measured against. */
$class = 'page-hero' . ($wide ? ' page-hero--wide' : '');
$wrap = 'wrap' . ($wide ? '' : ' wrap--prose');
?>
  <!-- ================= PAGE HERO ================= -->
  <section class="<?= $class ?>">
    <div class="<?= $wrap ?>">
      <p class="eyebrow"><?= esc($eyebrow) ?></p>
      <h1><?= esc($title) ?></h1>
<?php if ($actions): ?>
<?= indent_block(section_html($actions), '      ') ?>

<?php endif; ?>
    </div>
  </section>
