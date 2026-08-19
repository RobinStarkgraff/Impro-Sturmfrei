<?php
/* ------------------------------------------------------------
   Die Kopfleiste.

   Ein eigener Abschnitt und kein Schleier über dem Hero: sie bringt ihren
   dunklen Grund selbst mit, liegt fest oben, und ihre Höhe steht als
   --header-h in public/css/01-tokens.css.

   Start steht bewusst nicht in der Kopfleiste: dort ist die Wortmarke
   links der Weg zurück, ein zusätzlicher Punkt wäre derselbe Link zweimal.
   ------------------------------------------------------------ */
?>
<!-- ================= HEADER ================= -->
<header class="site-header" id="site-header">
  <div class="wrap site-header__inner">

    <a class="brand" href="<?= esc(page_link('index')) ?>">
      <img class="brand__mark"
           src="<?= esc(asset_versioned(asset_or('images/logo/logo-mark.jpg', $site['brand']['logo']))) ?>"
           alt="" width="52" height="52"/>
      <?= esc($site['brand']['name']) ?>

    </a>

    <button class="nav-toggle"
            id="nav-toggle"
            type="button"
            aria-expanded="false"
            aria-controls="site-nav">
      <span class="nav-toggle__bars"></span>
      <span class="visually-hidden">Menü</span>
    </button>

    <nav class="site-nav" id="site-nav" aria-label="Hauptnavigation">
      <ul class="nav-list">
<?= section_html('nav-items', ['pad' => '        ', 'headerOnly' => true]) ?>

      </ul>
    </nav>

  </div>
</header>
