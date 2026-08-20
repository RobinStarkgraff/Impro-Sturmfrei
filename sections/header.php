<?php
/* ------------------------------------------------------------
   The header bar.

   A section of its own and not a veil over the hero: it brings its own
   dark ground, is fixed to the top, and its height lives as --header-h in
   public/css/01-tokens.css.

   Home deliberately does not appear in the header bar: the wordmark on the
   left is the way back, an extra item would be the same link twice.
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
