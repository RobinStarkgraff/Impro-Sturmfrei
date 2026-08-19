<?php
/* ------------------------------------------------------------
   Die 404-Seite.

   Kein Eintrag der Navigation, sondern die Antwort auf eine falsche
   Adresse — .htaccess zeigt mit ErrorDocument hierher. Der Status bleibt
   dabei 404; das ist wichtig, damit Suchmaschinen die Adresse aus dem
   Index nehmen statt sie als Seite zu führen.

   Was hier steht, ist deshalb kein Trost, sondern ein Weg weiter: die
   drei Seiten, wegen derer Leute überhaupt kommen.
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
