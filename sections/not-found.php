<?php
/* ------------------------------------------------------------
   The 404 page.

   Not an entry in the navigation but the answer to a wrong address —
   .htaccess points here with ErrorDocument. The status stays 404, and that
   matters: it is what makes search engines drop the address from the index
   instead of listing it as a page.

   So what stands here is not consolation but a way onwards: the three
   pages people come for in the first place.
   ------------------------------------------------------------ */
?>
  <!-- ================= 404 ================= -->
  <section class="section" id="nicht-gefunden">
    <div class="wrap wrap--prose">

      <div class="section-head" data-reveal>
        <h2>Wo es weitergeht</h2>
        <p class="lead">
          Vermutlich sucht ihr eines von diesen dreien.
        </p>

        <div class="btn-row">
          <a class="btn btn--primary" href="<?= esc(page_link('termine')) ?>">Termine</a>
          <a class="btn btn--ghost" href="<?= esc(page_link('archiv')) ?>">Vergangene Shows</a>
          <a class="btn btn--ghost" href="<?= esc(page_link('buchen')) ?>">Sturmfrei buchen</a>
        </div>
      </div>

      <div class="section-head" data-reveal>
        <h2>Oder schreibt uns</h2>
        <p class="lead">
          Wenn ihr einem Link hierher gefolgt seid, würden wir das gern wissen —
          dann reparieren wir ihn.
          <a href="mailto:<?= esc($site['email']) ?>"><?= esc($site['email']) ?></a>
        </p>
      </div>

    </div>
  </section>
