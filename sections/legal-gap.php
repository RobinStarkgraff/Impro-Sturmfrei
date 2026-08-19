<?php
/* ------------------------------------------------------------
   Der Hinweis, solange in content/legal.json Pflichtangaben fehlen.

   Steht auf /impressum/ und auf /datenschutz/ — deshalb eine eigene
   Datei. Welche Angaben offen sind, sagt legal_gaps() in lib/html.php;
   hier steht nur, wie der Hinweis aussieht.

   $open  die Beschriftungen der fehlenden Angaben
   $pad   Einrückung, damit der Quelltext der Seite lesbar bleibt
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
