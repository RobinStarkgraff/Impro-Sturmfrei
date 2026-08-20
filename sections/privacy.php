<?php
/* ------------------------------------------------------------
   The privacy policy.

   The text describes what this site actually does: no cookies, no
   analytics, no third-party fonts, no form. Three sentences hang off
   content/legal.json and are only claimed if something is actually stored
   there — inventing a detail would be worse than the gap.
   ------------------------------------------------------------ */

['host' => $host, 'serverLocation' => $serverLocation, 'hostOutsideEU' => $hostOutsideEU,
 'logRetentionDays' => $logRetentionDays, 'processingAgreement' => $processingAgreement] = $legal['privacy'];

['responsible' => $responsible, 'street' => $street,
 'postalCode' => $postalCode, 'city' => $city] = $legal['impressum'];

$retention = $logRetentionDays
    ? 'Diese Protokolle werden nach ' . esc((string) $logRetentionDays) . ' Tagen gelöscht.'
    : 'Wie lange der Anbieter diese Protokolle aufbewahrt, richtet sich nach dessen eigenen Angaben.';

$where = $serverLocation ? '; die Server stehen in ' . esc($serverLocation) : '';
?>
  <!-- ================= PRIVACY ================= -->
  <section class="section" id="datenschutz">
    <div class="wrap wrap--prose prose">

<?php if ($open = legal_gaps([
    'verantwortliche Person' => $responsible,
    'Anschrift' => $street,
    'Hosting-Anbieter' => $host,
])): ?>
<?= section_html('legal-gap', ['open' => $open, 'pad' => '      ']) ?>


<?php endif; ?>
      <p class="lead">
        Kurz vorweg: Diese Seite setzt keine Cookies, bindet keine Schriften oder Skripte
        von fremden Servern ein, hat kein Formular und keine Zugriffsmessung. Es gibt
        deshalb wenig zu erklären — aber das Wenige gehört hierhin.
      </p>

      <h2>Verantwortlich</h2>

      <p>
        <?= or_missing($responsible, 'Name der verantwortlichen Person') ?><br>
        <?= or_missing($street, 'Straße und Hausnummer') ?><br>
        <?= or_missing($postalCode, 'PLZ') ?> <?= or_missing($city, 'Ort') ?><br>
        E-Mail: <a href="mailto:<?= esc($site['email']) ?>"><?= esc($site['email']) ?></a>
      </p>

      <p class="meta">
        Die vollständigen Angaben stehen im <a href="<?= esc(page_link('impressum')) ?>">Impressum</a>.
      </p>

      <h2>Aufruf der Seite (Server-Logs)</h2>

      <p>
        Diese Seite wird von <?= or_missing($host, 'Hosting-Anbieter') ?> ausgeliefert<?= $where ?>. Wie bei
        jedem Webserver protokolliert der Anbieter dabei technische Daten: IP-Adresse,
        Zeitpunkt, abgerufene Datei, übertragene Menge, Browser- und Betriebssystemkennung
        sowie die zuvor besuchte Seite, falls euer Browser sie mitsendet.
      </p>

      <p>
        Diese Daten sind nötig, damit die Seite überhaupt bei euch ankommt, und sie helfen
        gegen Angriffe und Störungen. Rechtsgrundlage ist Art. 6 Abs. 1 lit. f DSGVO —
        unser berechtigtes Interesse an einem funktionierenden, sicheren Auftritt.
        <?= $retention ?> Wir selbst werten diese Protokolle nicht aus und führen sie mit nichts
        zusammen.
      </p>
<?php if ($hostOutsideEU): ?>
      <!-- Only a transfer to a third country needs a legal basis. -->
      <p>
        Der Anbieter sitzt außerhalb der Europäischen Union. Die Übermittlung erfolgt auf
        Grundlage der Standardvertragsklauseln der EU-Kommission.
      </p>
<?php endif; ?>
<?php if ($processingAgreement): ?>
      <!-- Only claimed once the agreement has actually been signed. -->
      <p>
        Mit dem Anbieter besteht ein Vertrag über die Auftragsverarbeitung nach
        Art. 28 DSGVO.
      </p>
<?php endif; ?>

      <h2>Keine Cookies, keine Zugriffsmessung</h2>

      <p>
        Die Seite speichert nichts auf euren Geräten: keine Cookies, kein Local Storage,
        keine Zählpixel. Es läuft keine Analysesoftware — wir wissen nicht, wer hier war
        und wie lange.
      </p>

      <h2>Schriften und Bilder</h2>

      <p>
        Die Schriften Anton und Inter liegen auf demselben Server wie die Seite und werden
        nicht von Google Fonts geladen. Es entsteht also keine Verbindung zu Google und
        keine Übermittlung eurer IP-Adresse dorthin. Auch alle Fotos kommen von diesem
        Server; es sind keine Inhalte fremder Anbieter eingebettet.
      </p>

      <h2>E-Mail-Kontakt</h2>

      <p>
        Schreibt ihr uns, verarbeiten wir eure Adresse und den Inhalt der Nachricht, um sie
        zu beantworten — Rechtsgrundlage ist Art. 6 Abs. 1 lit. b bzw. lit. f DSGVO. Wir
        behalten die Nachricht, solange wir sie für die Sache brauchen, und geben sie nicht
        weiter. Der E-Mail-Versand selbst läuft über euren und unseren Anbieter; diese Seite
        ist daran nicht beteiligt.
      </p>

      <h2>Links zu Instagram, Eventbrite und MeetUp</h2>

      <p>
        Auf dieser Seite stehen ausschließlich normale Links dorthin — keine eingebetteten
        Inhalte, keine Buttons, die im Hintergrund Daten senden. Solange ihr nicht klickt,
        erfährt keiner der drei Anbieter etwas von euch. Klickt ihr, gelten deren
        Datenschutzbestimmungen; auf diese Verarbeitung haben wir keinen Einfluss.
      </p>

      <h2>Fotos von Shows</h2>

      <p>
        Im <a href="<?= esc(page_link('archiv')) ?>">Archiv</a> sind Menschen zu sehen. Diese
        Aufnahmen entstehen bei unseren Shows und werden mit Einverständnis der Abgebildeten
        veröffentlicht. Wer sich auf einem Foto sieht und es nicht dort haben möchte,
        schreibt uns — wir nehmen es heraus, ohne Begründung und ohne Rückfrage.
      </p>

      <h2>Eure Rechte</h2>

      <p>
        Ihr habt jederzeit das Recht auf Auskunft über die zu euch gespeicherten Daten
        (Art. 15 DSGVO), auf Berichtigung (Art. 16), auf Löschung (Art. 17), auf
        Einschränkung der Verarbeitung (Art. 18), auf Datenübertragbarkeit (Art. 20) und
        auf Widerspruch gegen eine Verarbeitung, die auf einem berechtigten Interesse
        beruht (Art. 21). Eine Mail an
        <a href="mailto:<?= esc($site['email']) ?>"><?= esc($site['email']) ?></a> genügt.
      </p>

      <p>
        Außerdem könnt ihr euch bei einer Datenschutz-Aufsichtsbehörde beschweren
        (Art. 77 DSGVO) — für <?= esc($site['city']) ?> ist das der Hamburgische Beauftragte für
        Datenschutz und Informationsfreiheit.
      </p>

      <h2>Änderungen</h2>

      <p>
        Ändert sich etwas an der Seite, ändert sich dieser Text mit. Es gilt immer die
        Fassung, die hier steht.
      </p>

    </div>
  </section>
