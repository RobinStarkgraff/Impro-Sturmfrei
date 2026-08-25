<?php
/* ------------------------------------------------------------
   The dates.

   At the top the next show (or the note that none is fixed), under it
   every further date, below that the channels where new dates turn up
   first.
   ------------------------------------------------------------ */

/* upcoming_shows() rather than $shows["shows"]: that list holds the played
   evenings too, and which is which follows from the date. See lib/data.php. */
$upcoming = upcoming_shows();
$next = $upcoming[0] ?? null;

/* The nearest date has the card above to itself; the rest are the list.
   Empty as long as only one date is fixed — which is the normal case. */
$later = array_slice($upcoming, 1);

/* The values that only apply to a date still ahead — ticket link and the
   note — sit in the "upcoming" half of the block. See lib/data.php. */
$next_only = $next ? show_upcoming($next) : [];

/* Every row carries the title image too, not only the card above it: a
   list of dates in which the first has a picture and the rest do not reads
   like two lists. Smaller there — in a row it is a mark, not a poster. */

/* The price rides on the house line: "Eintritt frei" is three words, and
   below the venue it would be a line of its own holding almost nothing.
   The note keeps the line under it — it is the one of the two that can run
   long. Both are optional. price_line() in lib/data.php turns 0 into
   "Eintritt frei", dot_line() in lib/html.php joins what is left and keeps
   each fact in one piece when the line wraps. */

/* A show without a title of its own: then the date is the headline, and
   the accent line above it would only say the same thing twice. The house
   below it stays either way. show_title() in lib/data.php answers the same
   question for the JSON-LD, where an event needs a name. */
/* The title image of this date — one file from images/titles/, shared by
   the dates that have no photos of their own yet. See lib/data.php. */
$cover = $next ? show_cover($next) : null;
?>
  <!-- ================= DATES ================= -->
  <section class="section" id="termine">

    <!-- The card is a band and takes the page measure: title image on one
         side, everything that is read on the other. What follows it is a
         list and reading text, and stays in the reading column — the gap
         between the two is on .next-show-band. -->
    <div class="wrap next-show-band">

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
<?php if ($next_note = $next_only['note'] ?? null): ?>

          <p class="meta"><?= esc($next_note) ?></p>
<?php endif; ?>

          <div class="btn-row">
            <a class="btn btn--primary" <?= ext($next_only['ticketUrl'] ?? $site['links']['eventbrite']['url']) ?>>Tickets sichern</a>
            <a class="btn btn--ghost" <?= ext($site['links']['instagram']['url']) ?>>Auf Instagram folgen</a>
          </div>
<?php else: ?>
          <h2>Bald kommt wieder was!</h2>

          <p class="lead">
            Der nächste Termin steht noch nicht fest. Sobald er steht, taucht er hier auf —
            und gleichzeitig auf den drei Kanälen darunter.
          </p>

          <div class="btn-row">
            <a class="btn btn--primary" <?= ext($site['links']['instagram']['url']) ?>>Auf Instagram folgen</a>
            <a class="btn btn--ghost" <?= ext($site['links']['eventbrite']['url']) ?>>Eventbrite ansehen</a>
          </div>
<?php endif; ?>

        </div>

      </div>

    </div>

    <div class="wrap wrap--prose">

<?php if ($later): ?>
      <div class="section-head" data-reveal>
        <h2>Weitere Termine</h2>
      </div>

      <ul class="date-list" data-reveal>
<?php foreach ($later as $show): ?>
        <li class="date-list__item">
<?php if ($row_cover = show_cover($show)): ?>
          <img class="date-list__cover"
               src="<?= esc(asset(cover_path($row_cover))) ?>"
               alt="<?= esc($row_cover['alt'] ?: 'Titelbild der Show am ' . date_de($show['date'])) ?>"
               loading="lazy">
<?php endif; ?>

          <div class="date-list__text">
<?php if (empty($show['title'])): ?>
            <h3 class="date-list__title date-list__title--first"><?= esc(when_line($show)) ?></h3>
<?php else: ?>
            <p class="date-line"><?= esc(when_line($show)) ?></p>

            <h3 class="date-list__title"><?= esc($show['title']) ?></h3>
<?php endif; ?>

            <p class="meta"><?= dot_line([
              $show['venue'], $site['city'], price_line($show), show_upcoming($show)['note'] ?? null,
            ]) ?></p>
          </div>

          <a class="btn btn--ghost" <?= ext(show_upcoming($show)['ticketUrl'] ?? $site['links']['eventbrite']['url']) ?>>Tickets sichern</a>
        </li>
<?php endforeach; ?>
      </ul>
<?php endif; ?>

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
