<?php
/* ------------------------------------------------------------
   The formats from content/booking.json.

   Two cards, and the decision they put is the only one there is to make
   here: do you want to watch, or do you want to play? There were three of
   them, and two of those were the same thing at two lengths — a short
   appearance and a whole evening, both of them us on a stage and the room
   watching. Side by side with "Impro zum Mitmachen" that read as a menu of
   three equal dishes, and the one real difference between them was the
   quietest thing on the row. Length is a line on a card now
   ("30–90 Minuten"), not a format of its own.

   Each card ends with a button of its own, and that is what makes the two
   a choice rather than a list: it opens the same enquiry as everywhere
   else on the page, with the format already written into it (see
   booking_mailto() in lib/html.php). Somebody who has just decided should
   not have to say so twice.
   ------------------------------------------------------------ */

$formats = $booking['formats'];

/* "Zwei Formate" — counted, not typed. The list stands in
   content/booking.json, and a number written into the heading is the one
   place that would not follow when an entry is added or dropped there;
   the file says as much beside the entries.

   As a word up to twelve, as a numeral beyond it — which is where German
   changes over too, so the fallback is not a cop-out. Capitalised at the
   start of a heading. */
$numbers = [1 => 'Ein', 'Zwei', 'Drei', 'Vier', 'Fünf', 'Sechs', 'Sieben', 'Acht', 'Neun', 'Zehn', 'Elf', 'Zwölf'];
$count = count($formats);
$heading = ($numbers[$count] ?? $count) . ' ' . ($count === 1 ? 'Format' : 'Formate');
?>
  <!-- ================= FORMATS ================= -->
  <section class="section" id="formate">
    <div class="wrap">

      <div class="section-head" data-reveal>
        <p class="eyebrow">Was möglich ist</p>
        <h2><?= esc($heading) ?></h2>
        <p class="lead">
          Zuschauen oder selbst spielen — das ist die Wahl. Alles andere daran lässt
          sich verschieben: die Formate sind ein Startpunkt für das Gespräch, keine
          Speisekarte.
        </p>
      </div>

      <div class="format-grid">

<?php foreach ($formats as $i => $format): ?>
<?= $i ? "\n" : '' ?>        <article class="format" data-reveal>

          <p class="pill format__duration"><?= esc($format['duration']) ?></p>
          <h3><?= esc($format['name']) ?></h3>
          <p class="lead"><?= esc($format['summary']) ?></p>
          <p><?= esc($format['detail']) ?></p>

          <div class="format__foot">
<?php /* The occasions, under the rule and over the button: a reader who has
         just understood the format is asking "is that a thing for us?",
         and a handful of examples answers that faster than a sentence
         would. They had a label ("Typisch dafür") and stood as chips
         above the rule; the words are their own label, and the rule
         already says that what follows it is no longer the description.

         One line, dots between them — dot_line() in lib/html.php, the
         same line the facts of a date are written on. It returns escaped
         markup and binds each occasion's own spaces, so the line wraps
         between two of them and never leaves a "·" opening a line.

         A format without occasions leaves the foot with nothing but its
         button, which is what it held before this block existed. */
if ($occasions = $format['occasions'] ?? []): ?>
            <p class="meta format__occasions"><?= dot_line($occasions) ?></p>
<?php endif; ?>
            <a class="btn btn--ghost format__action" href="<?= esc(booking_mailto($format['name'])) ?>"><?= esc($format['name']) ?> anfragen</a>
          </div>

        </article>
<?php endforeach; ?>

      </div>

    </div>
  </section>
