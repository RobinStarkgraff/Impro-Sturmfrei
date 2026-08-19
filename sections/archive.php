<?php
/* ------------------------------------------------------------
   Das Archiv.

   Je vergangene Show ein Artikel mit Slider. Artikel, Slider, aria-label
   und alt-Texte entstehen aus content/shows.json — Fotos gehören nach
   public/images/shows/<datum>/.

   Die Slider gehen bewusst über die ganze Breite und stehen nicht in
   einer Karte: die Fotos sind der Inhalt dieser Seite, alles andere ist
   Beschriftung.

   Neueste zuerst: im Archiv sucht man den letzten Abend, nicht den ersten.
   Die Reihenfolge in content/shows.json bleibt chronologisch — dort ist
   sie beim Eintragen die natürliche.
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

      <!-- Voller Breite und ohne Karte: der Slider ist selbst der Scrollbereich,
           die Innenabstände richten die erste Kachel am Text darüber aus. -->
      <div class="slider-wrap slider-wrap--bleed">
        <div class="slider" data-slider tabindex="0" role="group"
             aria-label="Fotos der Show vom <?= esc($when) ?>">
<?php foreach ($show['photos'] as $i => $photo):
    $src = asset("images/shows/{$show['date']}/{$photo['file']}");
    /* Ohne eigenen Text bleibt die laufende Nummer als Notbehelf. Ein echter
       Satz gehört in content/shows.json; `make check` zählt die offenen. */
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
  <!-- ================= ARCHIV ================= -->
  <section class="section" id="shows">

<?= implode("\n\n", $articles) ?>


  </section>
