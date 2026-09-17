<?php
/* ------------------------------------------------------------
   A quick look at the next date — the full list is on /termine/. All that
   is needed here: is one coming up or not, and what it costs. House, city
   and price share one line, the same one as on /termine/ — dot_line() in
   lib/html.php.

   The card is the same one /termine/ opens with, and it is a ticket: the
   title image on the left, what the evening is in the middle, and the
   stub on the right with the date, the hour and the way in. Hence the
   page measure rather than the reading one (it is a poster, not a
   paragraph), and hence __body around the text so the parts are elements
   rather than a stack of lines. Wide enough for the title to stand on one
   line is the whole point — see css/08-next-show.css.

   Two dates on the site, two cards, one shape: a visitor who has seen the
   home page recognises the card at the top of /termine/ as the same thing
   rather than reading it again from the beginning.

   The one difference is the second button. "Alle Termine" stands in the
   stub as well, under the ticket: the stub is the panel that says what to
   do about the evening, and after buying a way in, looking at the other
   dates is the next thing anybody does here. Ghost against the ticket's
   primary, so the two do not compete over one column — and where there
   are no tickets to sell it is the only button, and takes the primary
   coat itself.

   While no date is ahead of today, content/shows.json holds played
   evenings only — and this section shows the placeholder.
   ------------------------------------------------------------ */

/* upcoming_show() rather than $shows["shows"]: a date that has passed is no
   longer a next date. See lib/data.php. */
$next = upcoming_show();

/* show_title() and not $next["title"]: an evening with no title of its own
   is "Sturmfrei im Kulturschloss Wandsbek", the name the JSON-LD gives it
   too. The date used to be the headline in that case — it cannot be now,
   because the stub beside it is already the date. */
/* The title image of that date, if it has one — the left panel of the
   card. It stands in until the evening has photos of its own. */
$cover = $next ? show_cover($next) : null;

/* The date in the pieces the stub is made of, and the link that closes it.
   See lib/data.php — the same two on /termine/, in sections/dates.php. */
$stamp = $next ? date_stamp($next) : null;
$tickets = $next ? ticket_url($next) : null;
?>
  <!-- ================= NEXT SHOW (teaser) ================= -->
  <section class="section section--tight" id="naechste-show">
    <div class="wrap">

      <div class="next-show" data-reveal>

<?php if ($cover): ?>
        <div class="next-show__poster">

          <img class="next-show__cover"
               src="<?= esc(asset(cover_path($cover))) ?>"
               alt="<?= esc($cover['alt'] ?: 'Titelbild der Show am ' . date_de($next['date'])) ?>"
               loading="lazy">

        </div>
<?php endif; ?>

        <div class="next-show__body">

          <span class="pill">
            <span class="pill__dot" aria-hidden="true"></span>
            <?= esc($next ? soon_label($next) : 'Nächste Show') ?>

          </span>

<?php if ($next): ?>
          <h2><?= esc(show_title($next)) ?></h2>

          <p class="lead"><?= dot_line([$next['venue'], $site['city'], price_line($next)]) ?></p>
<?php else: ?>
          <h2>Bald kommt wieder was!</h2>

          <p class="lead">
            Der nächste Termin steht noch nicht — aber er kommt. Wo er zuerst auftaucht,
            steht auf der Termin-Seite.
          </p>

          <div class="btn-row">
            <a class="btn btn--primary" href="<?= esc(page_link('termine')) ?>">Zu den Terminen</a>
<?php if ($channel = link_for('announcements')): ?>
            <a class="btn btn--ghost" <?= ext($channel['url']) ?>>Auf <?= esc($channel['name']) ?> folgen</a>
<?php endif; ?>
          </div>
<?php endif; ?>

        </div>
<?php if ($stamp): ?>

        <div class="stub">

          <span class="stub__weekday"><?= esc($stamp['weekday']) ?></span>
          <span class="stub__day"><?= esc($stamp['day']) ?></span>
          <span class="stub__month"><?= esc($stamp['month']) ?></span>
<?php if ($stamp['time']): ?>
          <span class="stub__time"><?= esc($stamp['time']) ?></span>
<?php endif; ?>
<?php if ($tickets): ?>

          <a class="btn btn--primary" <?= ext($tickets) ?>>Tickets</a>
<?php endif; ?>

          <a class="btn btn--<?= $tickets ? 'ghost' : 'primary' ?>" href="<?= esc(page_link('termine')) ?>">Alle Termine</a>

        </div>
<?php endif; ?>

      </div>

    </div>
  </section>
