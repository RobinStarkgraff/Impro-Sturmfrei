<?php
/* ------------------------------------------------------------
   The ensemble.

   The image sequence lives in content/site.json under "about.crossfade"
   and is read by js/crossfade.js from data-crossfade.
   ------------------------------------------------------------ */

// With a modification stamp, for the same reason as in the hero: fixed
// filenames in images/group/ and a year of storage in the browser.
$layers = array_map('asset_versioned', $site['about']['crossfade']);
?>
  <!-- ================= ABOUT ================= -->
  <section class="section" id="about">
    <div class="wrap">

      <div class="section-head" data-reveal>
        <p class="eyebrow">Das Ensemble</p>
        <h2>Wer sind wir?</h2>
      </div>

      <div class="about-layout">

        <!-- Two stacked layers: that way the crossfade never shows an empty box.
             The second deliberately has no src attribute at all. An empty one is
             resolved by the browser against the page's own address and loads it a
             second time as an image — js/crossfade.js sets it on the first swap. -->
        <div class="about-media"
             data-reveal
             data-crossfade="<?= esc(json_encode($layers, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) ?>">
          <img class="about-media__layer is-active"
               src="<?= esc($layers[0]) ?>"
               alt="<?= esc($site['about']['photoAlt']) ?>"
               loading="lazy"/>
          <img class="about-media__layer" alt="" aria-hidden="true"/>
        </div>

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
            <a class="btn btn--ghost" href="<?= esc(page_link('kontakt')) ?>">Kontakt</a>
          </div>
        </div>

      </div>

    </div>
  </section>
