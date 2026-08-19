<?php
/* ------------------------------------------------------------
   Das Ensemble.

   Die Bilderfolge steht in content/site.json unter "about.crossfade" und
   wird von js/crossfade.js aus data-crossfade gelesen.
   ------------------------------------------------------------ */

// Mit Änderungsstempel, aus demselben Grund wie im Hero: feste
// Dateinamen in images/group/ und ein Jahr Ablage im Browser.
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

        <!-- Zwei gestapelte Ebenen: die Überblendung zeigt so nie eine leere Box.
             Die zweite hat bewusst gar kein src-Attribut. Ein leeres löst der
             Browser gegen die Adresse der Seite auf und lädt sie ein zweites
             Mal als Bild — js/crossfade.js setzt es beim ersten Wechsel. -->
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
