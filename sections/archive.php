<?php
/* ------------------------------------------------------------
   The archive.

   One article with a slider per show already played. The date and the title
   come from content/shows.json; the photos are simply what lies in
   public/images/shows/<date>/ — as many tiles as there are files, and
   nothing to list anywhere. See show_photos() in lib/data.php.

   Date and title, and nothing else. The head used to carry a second column
   beside them: who played, the house and the city, and how many photos
   followed. Every line of it was either a fact kept up by hand about an
   evening that is over ("Spieler: Enya, Nicklas, Raymond, Robin"), or the
   page describing itself — a photo count above the photos, a place above
   pictures of it. The slider says how many there are by being as long as it
   is; the group is on the home page, the venues of coming dates are on
   /termine/.

   The sliders deliberately run the full width and do not sit in a card:
   the photos are the content of this page, and now the caption is the day
   and its name.

   Which evenings appear here, past_shows() in lib/data.php decides by
   their date — newest first, and there is nothing to move by hand.

   Only the evening's own photos: the title image belongs to a date still
   to come, standing in for pictures that do not exist yet. Here they do.

   An evening played the night before therefore turns up without any: its
   date and title stand there without a slider under them. It keeps its
   article because it did take place, and the page saying so is better than
   the page passing over it until somebody has sorted through the pictures.
   ------------------------------------------------------------ */

$articles = [];

foreach (past_shows() as $show) {
    $when = date_de($show['date']);
    $photos = show_photos($show);

    ob_start();
    ?>
    <article class="show" data-reveal>

      <div class="wrap show__head">
        <p class="date-line"><?= esc($when) ?></p>
        <h3><?= esc(show_title($show)) ?></h3>
      </div>
<?php if ($photos): ?>

      <!-- Full width and no card: the slider is the scroll container itself,
           and its padding aligns the first tile with the text above. -->
      <div class="slider-wrap slider-wrap--bleed">
        <div class="slider" data-slider tabindex="0" role="group"
             aria-label="Fotos der Show vom <?= esc($when) ?>">
<?php foreach ($photos as $i => $photo):
    $src = asset(show_photo_dir($show) . '/' . $photo['file']);
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
<?php endif; ?>

    </article>
<?php
    $articles[] = rtrim((string) ob_get_clean(), "\n");
}
?>
  <!-- ================= ARCHIVE ================= -->
  <section class="section" id="shows">

<?= implode("\n\n", $articles) ?>


  </section>
