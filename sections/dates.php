<?php
/* ------------------------------------------------------------
   The dates.

   At the top the next show (or the note that none is fixed), under it
   every further date. The dates and nothing else: the channels below them
   on /termine/ are sections/follow.php, the same section as on the home
   page, and the way to a booking is in the header bar of every page.
   ------------------------------------------------------------ */

/* upcoming_shows() rather than $shows["shows"]: that list holds the played
   evenings too, and which is which follows from the date. See lib/data.php. */
$upcoming = upcoming_shows();
$next = $upcoming[0] ?? null;

/* The nearest date has the card above to itself; the rest are the list.
   Empty as long as only one date is fixed — which is the normal case. */
$later = array_slice($upcoming, 1);

/* The values that only apply to a date still ahead — ticket link and the
   note — sit in the "upcoming" half of the block. The link itself comes
   through ticket_url(): its own if it has one, otherwise the shop that
   holds the "tickets" role in content/site.json. See lib/data.php. */
$next_only = $next ? show_upcoming($next) : [];

/* Every row carries the title image too, not only the card above it: a
   list of dates in which the first has a picture and the rest do not reads
   like two lists. Smaller there — in a row it is a mark, not a poster. */

/* The price rides on the house line: "Eintritt frei" is three words, and
   below the venue it would be a line of its own holding almost nothing.
   The note keeps the line under it — it is the one of the two that can run
   long. Both are optional. price_line() in lib/data.php turns 0 into
   "Eintritt frei", dot_line() in lib/html.php joins what is left and keeps
   each fact in one piece when the line wraps.

   Both stand under the title as they read, one line each: the ticket
   button is not among them, it stands under the poster. */

/* A show without a title of its own: then the date is the headline, and
   the accent line above it would only say the same thing twice. The house
   below it stays either way. show_title() in lib/data.php answers the same
   question for the JSON-LD, where an event needs a name. */
/* The title image of this date — one file from images/titles/, shared by
   the dates that have no photos of their own yet. See lib/data.php. */
$cover = $next ? show_cover($next) : null;

/* The ticket button stands under the poster: the picture and the one thing
   to do about the evening are the same half of the card, and the other half
   stays what it is — what the evening is and when.

   Without a title image there is no poster for it to stand under, and it
   goes to the end of the text instead — which on a card that is nothing
   but text is where the stack ends, not a row standing on its own. Hence
   one question asked here rather than twice in the markup below. */
$tickets = $next ? ticket_url($next) : null;
$ticket_under_poster = (bool) ($cover && $tickets);
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
        <div class="next-show__poster">

          <img class="next-show__cover"
               src="<?= esc(asset(cover_path($cover))) ?>"
               alt="<?= esc($cover['alt'] ?: 'Titelbild der Show am ' . date_de($next['date'])) ?>"
               loading="lazy">
<?php if ($ticket_under_poster): ?>

          <a class="btn btn--primary next-show__ticket" <?= ext($tickets) ?>>Tickets sichern</a>
<?php endif; ?>

        </div>
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
<?php if ($tickets && !$ticket_under_poster): ?>

          <div class="btn-row">
            <a class="btn btn--primary" <?= ext($tickets) ?>>Tickets sichern</a>
          </div>
<?php endif; ?>
<?php else: ?>
          <h2>Bald kommt wieder was!</h2>

          <p class="lead">
            Der nächste Termin steht noch nicht fest. Sobald er steht, taucht er hier auf.
          </p>
<?php endif; ?>

        </div>

      </div>

    </div>

<?php if ($later): ?>

    <div class="wrap wrap--prose">

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

<?php if ($row_tickets = ticket_url($show)): ?>
          <a class="btn btn--ghost" <?= ext($row_tickets) ?>>Tickets sichern</a>
<?php endif; ?>
        </li>
<?php endforeach; ?>
      </ul>

    </div>
<?php endif; ?>
  </section>
