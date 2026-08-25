<?php
/* ------------------------------------------------------------
   A quick look at the next date — the full list is on /termine/. All that
   is needed here: is one coming up or not, and what it costs. House, city
   and price share one line, the same one as on /termine/ — dot_line() in
   lib/html.php. The note (if there is one) stays on the dates page: here it
   would be a second line for something nobody needs before they have
   decided to come.

   The card is a band, not a column: title image on one side, everything
   that is read on the other. Hence the page measure rather than the
   reading one (it is a poster, not a paragraph), hence __body around the
   text so the two sides are two elements, and hence the buttons at the
   foot of that side instead of centred under the whole card. Wide enough
   for the title to stand on one line is the whole point — see
   css/08-next-show.css.

   While no date is ahead of today, content/shows.json holds played
   evenings only — and this section shows the placeholder.
   ------------------------------------------------------------ */

/* upcoming_show() rather than $shows["shows"]: a date that has passed is no
   longer a next date. See lib/data.php. */
$next = upcoming_show();

/* A show without a title of its own: then the date is the headline, and
   the accent line above it would only say the same thing twice. The house
   below it stays either way. show_title() in lib/data.php answers the same
   question for the JSON-LD, where an event needs a name. */
/* The title image of that date, if it has one — one side of the card,
   opposite everything that is read. It stands in until the evening has
   photos of its own. */
$cover = $next ? show_cover($next) : null;
?>
  <!-- ================= NEXT SHOW (teaser) ================= -->
  <section class="section section--tight" id="naechste-show">
    <div class="wrap">

      <div class="next-show" data-reveal>

<?php if ($cover): ?>
        <img class="next-show__cover"
             src="<?= esc(asset(cover_path($cover))) ?>"
             alt="<?= esc($cover['alt'] ?: 'Titelbild der Show am ' . date_de($next['date'])) ?>"
             loading="lazy">
<?php endif; ?>

        <div class="next-show__body">

          <span class="pill">
            <span class="pill__dot" aria-hidden="true"></span>
            Nächste Show
          </span>

<?php if ($next): ?>
<?php if (empty($next['title'])): ?>
          <h2><?= esc(when_line($next)) ?></h2>
<?php else: ?>
          <p class="date-line"><?= esc(when_line($next)) ?></p>

          <h2><?= esc($next['title']) ?></h2>
<?php endif; ?>

          <p class="lead"><?= dot_line([$next['venue'], $site['city'], price_line($next)]) ?></p>

          <div class="btn-row">
            <a class="btn btn--primary" <?= ext(show_upcoming($next)['ticketUrl'] ?? $site['links']['eventbrite']['url']) ?>>Tickets sichern</a>
            <a class="btn btn--ghost" href="<?= esc(page_link('termine')) ?>">Alle Termine</a>
          </div>
<?php else: ?>
          <h2>Bald kommt wieder was!</h2>

          <p class="lead">
            Der nächste Termin steht noch nicht — aber er kommt. Wo er zuerst auftaucht,
            steht auf der Termin-Seite.
          </p>

          <div class="btn-row">
            <a class="btn btn--primary" href="<?= esc(page_link('termine')) ?>">Zu den Terminen</a>
            <a class="btn btn--ghost" <?= ext($site['links']['instagram']['url']) ?>>Auf Instagram folgen</a>
          </div>
<?php endif; ?>

        </div>

      </div>

    </div>
  </section>
