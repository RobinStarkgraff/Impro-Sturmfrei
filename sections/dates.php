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

/* The rows carry no title image. They did, at six rem, and the three of
   them were the same picture: the lighthouse lockup is most of every file
   in images/titles/, and what tells one from the next — the guest's name
   in the panel on the right — is the part that stops being legible at that
   size. So each row spent 120 px of a reading column on a mark that said
   the same thing three times. The stamp stands there now, which is what a
   row in a list of dates is actually about.

   The pictures themselves stay in content/shows.json and lose nothing: a
   date that is not the next one yet will be, and the card at the top shows
   it then. */

/* The price rides on the house line: "Eintritt frei" is three words, and
   below the venue it would be a line of its own holding almost nothing. It
   is optional — price_line() in lib/data.php turns 0 into "Eintritt frei",
   dot_line() in lib/html.php joins what is left and keeps each fact in one
   piece when the line wraps.

   The date is not among them any more: it stands in the stub on the right,
   in the four pieces date_stamp() cuts it into. So the card and every row
   below it name the evening once, on the left, and say when it is once, on
   the right — where before the date appeared as a line above the title and
   again inside the facts. */

/* show_title() and not $show["title"]: an evening with no title of its own
   is "Sturmfrei im Kulturschloss Wandsbek", which is the name the JSON-LD
   gives it too. The date used to serve as the headline in that case; it
   cannot any more, because the stub beside it is already the date. */

/* The title image of this date — one file from images/titles/, shared by
   the dates that have no photos of their own yet. See lib/data.php. */
$cover = $next ? show_cover($next) : null;

/* On the card the stub carries the way in as well, under the date it
   belongs to: it is the one thing to do about that evening, so it is the
   primary button. A row keeps the two apart — the stamp opens the row, the
   ghost button closes it — because with the button inside it the stamp
   stood half again as tall as the text beside it, and every row sat in its
   own empty space. Ghost buttons and not primary ones in any case: three
   of those under each other would make the list compete with the card.

   The link comes through ticket_url(): the evening's own if it has one,
   otherwise the shop that holds the "tickets" role in content/site.json.
   See lib/data.php. */
$tickets = $next ? ticket_url($next) : null;
$stamp = $next ? date_stamp($next) : null;
?>
  <!-- ================= DATES ================= -->
  <section class="section" id="termine">

    <!-- The card is a band and takes the page measure: three panels
         running to its edges — the title image, what the evening is, and
         the stub with the date and the tickets. What follows it is a list
         and reading text, and stays in the reading column; the gap between
         the two is on .next-show-band. -->
    <div class="wrap next-show-band">

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
            Der nächste Termin steht noch nicht fest. Sobald er steht, taucht er hier auf.
          </p>
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

        </div>
<?php endif; ?>

      </div>

    </div>

<?php if ($later): ?>

    <!-- The page measure, the same one the band above stands in — not the
         reading one. These rows are not reading text: they are slabs with
         a stamp at one end and a way in at the other, and in the reading
         column they stood as a narrow strip under a card half again as
         wide, sharing neither edge with it. At the page measure the card
         and the dates below it are one column of one width. -->
    <div class="wrap">

      <div class="section-head" data-reveal>
        <h2>Weitere Termine</h2>
      </div>

      <ul class="date-list" data-reveal>
<?php foreach ($later as $show): ?>
        <li class="date-list__item">
<?php if ($row_stamp = date_stamp($show)): ?>
          <div class="stub">

            <span class="stub__weekday"><?= esc($row_stamp['weekday']) ?></span>
            <span class="stub__day"><?= esc($row_stamp['day']) ?></span>
            <span class="stub__month"><?= esc($row_stamp['month']) ?></span>
<?php if ($row_stamp['time']): ?>
            <span class="stub__time"><?= esc($row_stamp['time']) ?></span>
<?php endif; ?>

          </div>
<?php endif; ?>

          <div class="date-list__text">
            <h3 class="date-list__title"><?= esc(show_title($show)) ?></h3>

            <p class="meta"><?= dot_line([$show['venue'], $site['city'], price_line($show)]) ?></p>
          </div>

<?php if ($row_tickets = ticket_url($show)): ?>
          <div class="date-list__action">
            <a class="btn btn--ghost" <?= ext($row_tickets) ?>>Tickets</a>
          </div>
<?php endif; ?>
        </li>
<?php endforeach; ?>
      </ul>

    </div>
<?php endif; ?>
  </section>
