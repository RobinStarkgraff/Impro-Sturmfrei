<?php
/* The two buttons in the page heading of /buchen/.

   --ghost, not --on-dark: the heading stands on the page's light ground
   now, and the glass variant is meant for dark pixels behind it. */
?>
<div class="btn-row">
  <a class="btn btn--primary" href="<?= esc(booking_mailto()) ?>">Anfrage schicken</a>
  <a class="btn btn--ghost" href="#formate">Formate ansehen</a>
</div>
