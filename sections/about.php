<?php
/* ------------------------------------------------------------
   The ensemble.

   The image sequence is the contents of public/images/group/ — see
   crossfade_photos() in lib/data.php, which reads the folder, sorts it the
   way a person numbers files and puts the hero's own photo last. It stood
   in content/site.json as a list of paths before, and the folder held one
   picture that list had never been told about.

   From here it travels into data-crossfade, and js/crossfade.js reads it
   back out of the markup: the order exists once, and nothing has to be
   kept in step with it.
   ------------------------------------------------------------ */

// With a modification stamp, for the same reason as in the hero: fixed
// filenames in images/group/ and a year of storage in the browser.
$layers = array_map('asset_versioned', crossfade_photos());
?>
  <!-- ================= ABOUT ================= -->
  <section class="section" id="about">
    <div class="wrap">

      <div class="section-head" data-reveal>
        <p class="eyebrow">Das Ensemble</p>
        <h2>Wer sind wir?</h2>
      </div>

      <div class="about-layout<?= $layers ? '' : ' about-layout--textonly' ?>">

<?php /* No photo at all only happens with an empty images/group/, and then
         the block goes rather than standing as a box with a source that is
         not there — an <img src=""> is resolved by the browser against the
         page's own address and loads the page a second time as an image.
         The folder holds the photos of this group, so this is a state
         somebody is in the middle of fixing, not a page shape to design
         for: the text takes the width and the section still reads.

         Two stacked layers otherwise: that way the crossfade never shows
         an empty box. The second deliberately has no src attribute at all,
         for the reason above — js/crossfade.js sets it on the first swap. */
if ($layers): ?>
        <div class="about-media"
             data-reveal
             data-crossfade="<?= esc(json_encode($layers, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) ?>">
          <img class="about-media__layer is-active"
               src="<?= esc($layers[0]) ?>"
               alt="<?= esc($site['about']['photoAlt']) ?>"
               loading="lazy"/>
          <img class="about-media__layer" alt="" aria-hidden="true"/>
        </div>
<?php endif; ?>

        <div data-reveal>
          <p class="about-statement">
            Frei im Kopf.<br>
            Frei im Spiel.<br>
            <span class="accent">Sturmfrei auf der Bühne!</span>
          </p>

          <p class="lead">
            Voller Impro-Leidenschaft, Spielfreude und mit Herz bringen wir jede Bühne in Bewegung.
          </p>

          <div class="btn-row">
            <a class="btn btn--primary" href="<?= esc(page_link('buchen')) ?>">Sturmfrei buchen</a>
            <a class="btn btn--ghost" href="<?= esc(page_link('impressum')) ?>">Kontakt</a>
          </div>
        </div>

      </div>

    </div>
  </section>
