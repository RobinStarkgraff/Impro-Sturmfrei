<?php
/* ------------------------------------------------------------
   The notice shown while mandatory details are missing from
   content/legal.json.

   Used on /impressum/ and /datenschutz/ — hence a file of its own. Which
   details are still open is answered by legal_gaps() in lib/html.php; all
   that lives here is what the notice looks like.

   $open  the labels of the missing details
   $pad   indentation, to keep the page source readable
   ------------------------------------------------------------ */

$pad = $pad ?? '';

ob_start();
?>
<p class="notice" data-reveal>
  <strong>Diese Seite ist noch nicht vollständig.</strong>
  Es fehlen: <?= esc(implode(', ', $open)) ?>. Die Angaben stehen in
  <code>content/legal.json</code> und müssen von der Gruppe kommen —
  eine erfundene Anschrift wäre schlimmer als diese Lücke.
</p>
<?php
echo indent_block(rtrim((string) ob_get_clean(), "\n"), $pad);
