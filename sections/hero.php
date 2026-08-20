<?php
/* ------------------------------------------------------------
   The hero — home page only.

   Photo and alt text live in content/site.json under "hero".
   ------------------------------------------------------------ */

// With a modification stamp: images/group/ carries fixed filenames, and
// the .htaccess lets images sit in the browser for a year. Without the
// stamp nobody would get to see a replaced hero photo.
$photo = asset_versioned($site['hero']['photo']);
?>
  <!-- ================= HERO ================= -->
  <section class="section hero" id="top">

    <!-- The same photo, heavily blurred: it delivers the stage colours as
         atmosphere, and the resolution does not matter. -->
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
