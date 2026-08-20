<?php
/* What is needed on site — the list lives in content/booking.json. */
?>
  <!-- ================= WHAT WE NEED ================= -->
  <section class="section" id="voraussetzungen">
    <div class="wrap">

      <div class="section-head" data-reveal>
        <p class="eyebrow">Vor Ort</p>
        <h2>Was wir brauchen</h2>
        <p class="lead">
          Wenig. Impro braucht keine Kulisse — aber diese sechs Dinge sollten stimmen,
          damit der Abend läuft.
        </p>
      </div>

      <dl class="need-grid" data-reveal>

<?php foreach ($booking['needs'] as $i => $need): ?>
<?= $i ? "\n" : '' ?>          <div class="need">
            <dt class="need__item"><?= esc($need['item']) ?></dt>
            <dd class="need__detail"><?= esc($need['detail']) ?></dd>
          </div>
<?php endforeach; ?>

      </dl>

      <p class="meta need-grid__note" data-reveal>
        Passt etwas davon nicht? Fragt trotzdem. Wir haben schon in Räumen gespielt,
        in denen laut Liste nichts gehen dürfte.
      </p>

    </div>
  </section>
