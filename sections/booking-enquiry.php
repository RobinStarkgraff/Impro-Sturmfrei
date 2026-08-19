<?php
/* Der einzige Schritt: die Anfrage per Mail. */
?>
  <!-- ================= ANFRAGE ================= -->
  <section class="section" id="anfrage">
    <div class="wrap wrap--prose">

      <div class="cta" data-reveal>

        <p class="eyebrow">Der einzige Schritt</p>
        <h2>Anfrage schicken</h2>

        <p class="lead">
          Ein Klick öffnet eine Mail, in der die Fragen schon stehen — Anlass, Datum, Ort,
          Zuschauerzahl. Ausfüllen, absenden, fertig. Wir antworten in der Regel innerhalb
          von zwei Tagen.
        </p>

        <div class="btn-row btn-row--center">
          <a class="btn btn--primary" href="<?= esc(booking_mailto()) ?>">Anfrage per E-Mail</a>
          <a class="btn btn--ghost" href="<?= esc(page_link('kontakt')) ?>">Andere Wege</a>
        </div>

        <p class="meta cta__note">
          Lieber ohne Mailprogramm? Alle Kontaktwege stehen auf
          <a href="<?= esc(page_link('kontakt')) ?>">Kontakt</a> — auch die Direktnachricht
          auf Instagram.
        </p>

      </div>

    </div>
  </section>
