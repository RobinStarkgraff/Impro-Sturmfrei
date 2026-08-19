<?php
/* ------------------------------------------------------------
   Der Footer.

   Zeigt alle Navigationspunkte (nicht nur die der Kopfleiste), die
   Kanäle aus content/site.json und unten die Pflichtseiten.

   Die Linklisten sind <nav> mit Namen: so tauchen sie in der
   Landmark-Übersicht von Screenreadern auf.
   ------------------------------------------------------------ */
?>
<!-- ================= FOOTER ================= -->
<footer class="site-footer">
  <div class="wrap">

    <div class="footer-grid">

      <div class="footer-col">
        <p class="footer-brand"><?= esc($site['brand']['name']) ?></p>
        <p class="meta">
          Improvisationstheater aus <?= esc($site['city']) ?>.<br>
          Kein Drehbuch, kein Plan B.
        </p>
      </div>

      <nav class="footer-col" aria-labelledby="footer-site-heading">
        <p class="footer-col__title" id="footer-site-heading">Seite</p>
        <ul>
<?= section_html('nav-items', ['pad' => '          ']) ?>

        </ul>
      </nav>

      <nav class="footer-col" aria-labelledby="footer-social-heading">
        <p class="footer-col__title" id="footer-social-heading">Folgt uns</p>
        <ul>
<?php foreach ($site['links'] as $entry): ?>
          <li><a <?= ext($entry['url']) ?>><?= esc($entry['name']) ?></a></li>
<?php endforeach; ?>
        </ul>
      </nav>

      <div class="footer-col">
        <p class="footer-col__title">Kontakt</p>
        <ul>
          <li><a href="mailto:<?= esc($site['email']) ?>"><?= esc($site['email']) ?></a></li>
          <li><a href="<?= esc(page_link('buchen')) ?>">Sturmfrei buchen</a></li>
        </ul>
      </div>

    </div>

    <div class="footer-bottom">
      <p class="footer-bottom__note">
        <?php /* Das Gründungsjahr steht in content/site.json, das laufende kommt
                 vom Server: sonst steht im Januar auf jeder Seite die
                 Jahreszahl von gestern, und niemand meldet es. */ ?>
        © <?= esc(max((int) $site['copyrightYear'], (int) date('Y'))) ?> <?= esc($site['brand']['name']) ?>. Alles improvisiert, nichts garantiert.
      </p>
      <nav aria-label="Rechtliches">
        <ul class="footer-legal">
<?= section_html('nav-items', ['pad' => '          ', 'items' => $site['legalNav']]) ?>

        </ul>
      </nav>
    </div>

  </div>
</footer>
