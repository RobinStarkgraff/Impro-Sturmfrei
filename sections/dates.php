<?php
/* ------------------------------------------------------------
   The dates.

   At the top the next show (or the note that none is fixed), below it the
   channels where new dates turn up first.
   ------------------------------------------------------------ */

/* upcoming_show() rather than $shows["upcoming"]: a date that has passed
   is no longer a next date. See lib/data.php. */
$next = upcoming_show();
?>
  <!-- ================= DATES ================= -->
  <section class="section" id="termine">
    <div class="wrap wrap--prose">

      <div class="next-show" data-reveal>

        <span class="pill">
          <span class="pill__dot" aria-hidden="true"></span>
          Nächste Show
        </span>

<?php if ($next): ?>
        <p class="date-line"><?= esc(when_line($next)) ?></p>

        <h2><?= esc($next['title']) ?></h2>

        <p class="lead"><?= esc($next['venue']) ?> · <?= esc($site['city']) ?></p>
<?php if (!empty($next['note'])): ?>

        <p class="meta"><?= esc($next['note']) ?></p>
<?php endif; ?>

        <div class="btn-row btn-row--center">
          <a class="btn btn--primary" <?= ext($next['ticketUrl'] ?? $site['links']['eventbrite']['url']) ?>>Tickets sichern</a>
          <a class="btn btn--ghost" <?= ext($site['links']['instagram']['url']) ?>>Auf Instagram folgen</a>
        </div>
<?php else: ?>
        <h2>Bald kommt wieder was!</h2>

        <p class="lead">
          Der nächste Termin steht noch nicht fest. Sobald er steht, taucht er hier auf —
          und gleichzeitig auf den drei Kanälen darunter.
        </p>

        <div class="btn-row btn-row--center">
          <a class="btn btn--primary" <?= ext($site['links']['instagram']['url']) ?>>Auf Instagram folgen</a>
          <a class="btn btn--ghost" <?= ext($site['links']['eventbrite']['url']) ?>>Eventbrite ansehen</a>
        </div>
<?php endif; ?>

      </div>

      <div class="section-head" data-reveal>
        <h2>Wo die Termine zuerst stehen</h2>
        <p class="lead">
          Wir spielen unregelmäßig — es lohnt sich also, einen der drei Kanäle im Blick zu
          behalten. Auf Eventbrite gibt es die Tickets, auf MeetUp die Termine samt Zusagen,
          auf Instagram alles andere.
        </p>
      </div>

      <div class="follow-grid follow-grid--stack" data-reveal>

<?= section_html('follow-cards', ['pad' => '        ']) ?>


      </div>

      <div class="section-head" data-reveal>
        <h2>Ihr habt einen Anlass?</h2>
        <p class="lead">
          Neben den öffentlichen Shows spielen wir auch dort, wo ihr feiert — Firmenfeier,
          Geburtstag, Vereinsfest. Was das heißt, steht auf
          <a href="<?= esc(page_link('buchen')) ?>">Buchen</a>.
        </p>

        <div class="btn-row">
          <a class="btn btn--primary" href="<?= esc(page_link('buchen')) ?>">Sturmfrei buchen</a>
          <a class="btn btn--ghost" href="<?= esc(page_link('archiv')) ?>">Vergangene Shows</a>
        </div>
      </div>

    </div>
  </section>
