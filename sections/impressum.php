<?php
/* ------------------------------------------------------------
   The Impressum (§ 5 DDG).

   Every detail comes from content/legal.json. If one is missing, the page
   visibly says so in its place — and `make check` reports it as an error.
   A page with a made-up address would be worse.

   The labels handed to or_missing() and legal_gaps() appear on the page,
   so they stay German.
   ------------------------------------------------------------ */

['entity' => $entity, 'responsible' => $responsible, 'street' => $street,
 'postalCode' => $postalCode, 'city' => $city, 'phone' => $phone,
 'vatId' => $vatId] = $legal['impressum'];
?>
  <!-- ================= IMPRESSUM ================= -->
  <section class="section" id="impressum">
    <div class="wrap wrap--prose prose">

<?php if ($open = legal_gaps([
    'Name' => $responsible,
    'Straße' => $street,
    'PLZ' => $postalCode,
    'Ort' => $city,
])): ?>
<?= section_html('legal-gap', ['open' => $open, 'pad' => '      ']) ?>


<?php endif; ?>
      <h2>Angaben gemäß § 5 DDG</h2>

      <p>
<?php if ($entity): ?>
        <?= esc($entity) ?><br>
<?php endif; ?>
        <?= or_missing($responsible, 'Name der vertretungsberechtigten Person') ?><br>
        <?= or_missing($street, 'Straße und Hausnummer') ?><br>
        <?= $postalCode || $city
              ? or_missing($postalCode, 'PLZ') . ' ' . or_missing($city, 'Ort')
              : missing('PLZ') . ' ' . missing('Ort') ?>

      </p>

      <h2>Kontakt</h2>

      <p>
        E-Mail: <a href="mailto:<?= esc($site['email']) ?>"><?= esc($site['email']) ?></a><?= $phone ? "<br>\n        Telefon: " . esc($phone) : '' ?>

      </p>

      <h2>Verantwortlich für den Inhalt</h2>

      <p>
        <?= or_missing($responsible, 'Name der verantwortlichen Person') ?>, Anschrift wie oben.
      </p>
<?php if ($vatId): ?>

      <h2>Umsatzsteuer-Identifikationsnummer</h2>

      <p><?= esc($vatId) ?></p>
<?php endif; ?>

      <h2>Bildrechte</h2>

      <p>
        Alle Fotos auf dieser Seite zeigen Shows von <?= esc($site['brand']['name']) ?> und werden mit
        Einverständnis der abgebildeten Personen verwendet. Eine Weiterverwendung ohne
        Rückfrage ist nicht gestattet.
      </p>

      <h2>Haftung für Links</h2>

      <p>
        Diese Seite verlinkt auf Instagram, Eventbrite und MeetUp. Für die Inhalte dieser
        Seiten sind allein deren Anbieter verantwortlich. Zum Zeitpunkt der Verlinkung waren
        dort keine rechtswidrigen Inhalte erkennbar; eine laufende Kontrolle fremder Seiten
        ist ohne konkreten Anlass nicht zumutbar. Wird uns eine Rechtsverletzung bekannt,
        entfernen wir den Link.
      </p>

      <h2>Streitschlichtung</h2>

      <p>
        Wir sind nicht verpflichtet und nicht bereit, an einem Streitbeilegungsverfahren vor
        einer Verbraucherschlichtungsstelle teilzunehmen.
      </p>

    </div>
  </section>
