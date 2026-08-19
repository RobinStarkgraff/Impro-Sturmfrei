<?php
/* ------------------------------------------------------------
   Der Hero — nur auf der Startseite.

   Foto und alt-Text stehen in content/site.json unter "hero".
   ------------------------------------------------------------ */

// Mit Änderungsstempel: images/group/ trägt feste Dateinamen, und die
// .htaccess lässt Bilder ein Jahr im Browser liegen. Ohne Stempel bekäme
// niemand ein ausgetauschtes Hero-Foto zu sehen.
$photo = asset_versioned($site['hero']['photo']);
?>
  <!-- ================= HERO ================= -->
  <section class="section hero" id="top">

    <!-- Dasselbe Foto, stark weichgezeichnet: liefert die Bühnenfarben als
         Atmosphäre, ohne dass die Auflösung eine Rolle spielt. -->
    <img class="hero__backdrop" src="<?= esc($photo) ?>" alt="" aria-hidden="true"/>

    <div class="wrap hero__inner">

      <div class="hero__content">

        <p class="eyebrow"><?= esc($site['brand']['tagline']) ?></p>

        <h1>
          Kein Drehbuch.<br>
          Kein Plan B.<br>
          <span class="accent">Nur dieser Abend.</span>
        </h1>

        <p class="hero__text">
          Kommt zu den Sturmfrei-Impro-Shows und erlebt einen Abend mit klarer Aussicht auf beste
          Unterhaltung. Alles entsteht auf der Bühne und im Moment. Ihr gebt den Impuls — wir machen
          daraus unvergessliche Geschichten.
        </p>

        <div class="btn-row">
          <a class="btn btn--primary" href="<?= esc(page_link('termine')) ?>">Termine ansehen</a>
          <a class="btn btn--on-dark" href="<?= esc(page_link('buchen')) ?>">Uns buchen</a>
        </div>

        <a class="scroll-cue" href="#naechste-show">
          Vorhang auf
          <span class="scroll-cue__arrow" aria-hidden="true">↓</span>
        </a>

      </div>

      <figure class="hero__figure">
        <img class="hero__photo"
             src="<?= esc($photo) ?>"
             alt="<?= esc($site['hero']['alt']) ?>"
             fetchpriority="high"/>
      </figure>

    </div>

  </section>
