<?php
/* ------------------------------------------------------------
   A quick look at the next date — the full list is on /termine/. All that
   is needed here: is one coming up or not.

   "upcoming" in content/shows.json is null while nothing is fixed.
   ------------------------------------------------------------ */

/* upcoming_show() rather than $shows["upcoming"]: a date that has passed
   is no longer a next date. See lib/data.php. */
$next = upcoming_show();
?>
  <!-- ================= NEXT SHOW (teaser) ================= -->
  <section class="section section--tight" id="naechste-show">
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

        <div class="btn-row btn-row--center">
          <a class="btn btn--primary" <?= ext($next['ticketUrl'] ?? $site['links']['eventbrite']['url']) ?>>Tickets sichern</a>
          <a class="btn btn--ghost" href="<?= esc(page_link('termine')) ?>">Alle Termine</a>
        </div>
<?php else: ?>
        <h2>Bald kommt wieder was!</h2>

        <p class="lead">
          Der nächste Termin steht noch nicht — aber er kommt. Wo er zuerst auftaucht,
          steht auf der Termin-Seite.
        </p>

        <div class="btn-row btn-row--center">
          <a class="btn btn--primary" href="<?= esc(page_link('termine')) ?>">Zu den Terminen</a>
          <a class="btn btn--ghost" <?= ext($site['links']['instagram']['url']) ?>>Auf Instagram folgen</a>
        </div>
<?php endif; ?>

      </div>

    </div>
  </section>
