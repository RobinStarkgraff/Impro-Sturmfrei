<?php
/* ------------------------------------------------------------
   The archive.

   One article with a slider per past show. Article, slider, aria-label
   and alt texts all come from content/shows.json — photos belong in
   public/images/shows/<date>/.

   The sliders deliberately run the full width and do not sit in a card:
   the photos are the content of this page, everything else is caption.

   Newest first: in an archive you are looking for the latest evening, not
   the first. The order in content/shows.json stays chronological — that is
   the natural one while typing entries in.
   ------------------------------------------------------------ */

$past = $shows['past'];
usort($past, fn(array $a, array $b) => strcmp($b['date'], $a['date']));

$articles = [];

foreach ($past as $show) {
    $when = date_de($show['date']);

    ob_start();
    ?>
    <article class="show" data-reveal>

      <div class="wrap show__head">
        <div>
          <p class="date-line"><?= esc($when) ?></p>
          <h3><?= esc($show['title']) ?></h3>
        </div>
        <p class="meta">
          <?= esc($show['venue']) ?> · <?= esc($site['city']) ?><br>
          Spieler: <?= esc(implode(', ', $show['cast'])) ?><br>
          <?= count($show['photos']) ?> Fotos
        </p>
      </div>

      <!-- Full width and no card: the slider is the scroll container itself,
           and its padding aligns the first tile with the text above. -->
      <div class="slider-wrap slider-wrap--bleed">
        <div class="slider" data-slider tabindex="0" role="group"
             aria-label="Fotos der Show vom <?= esc($when) ?>">
<?php foreach ($show['photos'] as $i => $photo):
    $src = asset("images/shows/{$show['date']}/{$photo['file']}");
    /* Without a text of its own the running number remains as a stopgap. A
       real sentence belongs in content/shows.json; `make check` counts the
       ones still open. */
    $alt = $photo['alt'] ?: 'Impro-Szene ' . ($i + 1) . ' – Show vom ' . $when;
    ?>
        <a class="slide" href="<?= esc($src) ?>" aria-label="<?= esc($alt) ?> – Bild vergrößern">
          <img src="<?= esc($src) ?>" alt="<?= esc($alt) ?>" loading="lazy" width="1600" height="2000">
        </a>
<?php endforeach; ?>
        </div>
      </div>

    </article>
<?php
    $articles[] = rtrim((string) ob_get_clean(), "\n");
}
?>
  <!-- ================= ARCHIVE ================= -->
  <section class="section" id="shows">

<?= implode("\n\n", $articles) ?>


  </section>
