<?php
/* ------------------------------------------------------------
   The hero — home page only.

   It stands on the page's own ground, like the heading of every subpage
   since the header bar started bringing a ground of its own. It used to be
   a dark stage: the photo again behind it, blurred to a wash, a scrim over
   that to hold the contrast, and white text on top. That was the last of
   the dark bands under the bar, and the only place on the site where the
   page turned dark for one screen and light for the rest of the way down.

   Everything that made it a hero is still here — the full window height,
   the headline at --text-h1, the photo beside it with the brick glow, the
   cue to keep scrolling. Only the ground below it is the page's.

   Photo and alt text live in content/site.json under "hero".
   ------------------------------------------------------------ */

// With a modification stamp: images/group/ carries fixed filenames, and
// the .htaccess lets images sit in the browser for a year. Without the
// stamp nobody would get to see a replaced hero photo.
$photo = asset_versioned($site['hero']['photo']);
?>
  <!-- ================= HERO ================= -->
  <section class="section hero" id="top">

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
          <a class="btn btn--ghost" href="<?= esc(page_link('buchen')) ?>">Uns buchen</a>
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
