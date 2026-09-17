<?php
/* Where things carry on beyond the site — used on home and under the dates
   on /termine/. Not on /kontakt/: there the channel with a direct message
   at the other end is one of the contact ways, and the other two are ways
   to hear about a date, which is a different question.

   The cards themselves are sections/follow-cards.php — two pages show
   them, hence a file of their own.

   --tight on both the section and its head, because both would otherwise
   be padded twice over. Above the section stands another .section with
   --section-y of its own below it, and under it the footer brings the same
   again as its margin: --section-y is the gap BETWEEN two sections, not
   one per section. And below the head follow three rows, not a landscape
   of cards — see .section-head--tight in css/04-layout.css. */

/* The channel new dates land on first is the one holding the
   "announcements" role in content/site.json, not Instagram by name. */
$channel = link_for('announcements');
?>
  <!-- ================= FOLLOW ================= -->
  <section class="section section--tight" id="follow">
    <div class="wrap">

      <div class="section-head section-head--center section-head--tight" data-reveal>
        <p class="eyebrow">Bleibt dran</p>
        <h2>Folgt uns</h2>
        <p class="lead">
          Neue Termine stehen zuerst hier<?= $channel ? ' — und dann sofort auf ' . esc($channel['name']) : '' ?>.
        </p>
      </div>

      <div class="follow-grid" data-reveal>

<?= section_html('follow-cards', ['pad' => '        ']) ?>


      </div>

    </div>
  </section>
