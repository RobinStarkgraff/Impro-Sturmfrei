<?php
/* ------------------------------------------------------------
   Die Kontaktwege.

   Telefon nur, wenn in content/legal.json eine Nummer steht — eine leere
   Karte "Telefon" wäre schlechter als keine.
   ------------------------------------------------------------ */

$phone = $legal['impressum']['phone'];
?>
  <!-- ================= KONTAKT ================= -->
  <section class="section" id="kontakt">
    <div class="wrap wrap--prose">

      <div class="contact-ways" data-reveal>

        <div class="contact-way">
          <p class="contact-way__label">E-Mail</p>
          <p class="lead">
            <a href="mailto:<?= esc($site['email']) ?>"><?= esc($site['email']) ?></a>
          </p>
          <p class="meta">Der direkteste Weg. Wir antworten meist innerhalb von zwei Tagen.</p>
        </div>

<?php if ($phone): ?>
        <div class="contact-way">
          <p class="contact-way__label">Telefon</p>
          <p class="lead">
            <a href="tel:<?= esc(preg_replace('/[^+0-9]/', '', $phone)) ?>"><?= esc($phone) ?></a>
          </p>
          <p class="meta">
            Für Fragen, die schneller gehen als eine Mail. Wenn niemand abnimmt,
            stehen wir vermutlich auf einer Bühne — schreibt einfach.
          </p>
        </div>

<?php endif; ?>
        <div class="contact-way">
          <p class="contact-way__label">Instagram</p>
          <p class="lead">
            <a <?= ext($site['links']['instagram']['url']) ?>><?= esc($site['links']['instagram']['handle']) ?></a>
          </p>
          <p class="meta">Direktnachricht geht auch — für kurze Fragen oft der schnellere Weg.</p>
        </div>

        <div class="contact-way">
          <p class="contact-way__label">Anfrage für einen Anlass</p>
          <p class="lead">
            <a href="<?= esc(booking_mailto()) ?>">Anfrage mit vorbereiteten Fragen</a>
          </p>
          <p class="meta">
            Öffnet eine Mail, in der Anlass, Datum und Ort schon als Zeilen stehen.
            Worum es dabei geht, steht auf <a href="<?= esc(page_link('buchen')) ?>">Buchen</a>.
          </p>
        </div>

        <div class="contact-way">
          <p class="contact-way__label">Wo wir sind</p>
          <p class="lead"><?= esc($site['city']) ?></p>
          <p class="meta">
            Wir spielen in Hamburg und im Umland. Weiter weg geht auch — dann kommt
            die Anfahrt dazu.
          </p>
        </div>

      </div>

    </div>
  </section>
