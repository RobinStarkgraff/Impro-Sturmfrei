<?php
/* Die häufigen Fragen aus content/booking.json. */
?>
  <!-- ================= FAQ ================= -->
  <section class="section" id="fragen">
    <div class="wrap wrap--prose">

      <div class="section-head" data-reveal>
        <p class="eyebrow">Kurz beantwortet</p>
        <h2>Häufige Fragen</h2>
      </div>

      <div class="faq" data-reveal>

<?php foreach ($booking['faq'] as $i => $entry): ?>
<?= $i ? "\n" : '' ?>        <details class="faq__item">
          <summary><?= esc($entry['q']) ?></summary>
          <p><?= esc($entry['a']) ?></p>
        </details>
<?php endforeach; ?>

      </div>

    </div>
  </section>
