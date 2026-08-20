<?php
/* The formats from content/booking.json. */
?>
  <!-- ================= FORMATS ================= -->
  <section class="section" id="formate">
    <div class="wrap">

      <div class="section-head section-head--center" data-reveal>
        <p class="eyebrow">Was möglich ist</p>
        <h2>Drei Formate</h2>
        <p class="lead">
          Alles davon lässt sich verschieben — die Formate sind ein Startpunkt für das
          Gespräch, keine Speisekarte.
        </p>
      </div>

      <div class="format-grid">

<?php foreach ($booking['formats'] as $i => $format): ?>
<?= $i ? "\n" : '' ?>        <article class="format" data-reveal>
          <p class="format__duration"><?= esc($format['duration']) ?></p>
          <h3><?= esc($format['name']) ?></h3>
          <p class="lead format__summary"><?= esc($format['summary']) ?></p>
          <p><?= esc($format['detail']) ?></p>
          <p class="meta format__cast"><?= esc($format['cast']) ?></p>
        </article>
<?php endforeach; ?>

      </div>

    </div>
  </section>
