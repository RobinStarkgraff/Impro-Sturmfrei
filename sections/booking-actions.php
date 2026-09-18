<?php
/* The button in the page heading of /buchen/ — the mail, and nothing
   beside it.

   There were two. The second jumped to what an evening costs, because
   that was the one question lying far enough down the page for a jump to
   be worth the space. The page ends at the two format cards now, and each
   of them carries its own enquiry button (sections/booking-formats.php):
   there is nothing under the heading a jump could reach that the reader
   is not already looking at. */
?>
<div class="btn-row">
  <a class="btn btn--primary" href="<?= esc(booking_mailto()) ?>">Anfrage schicken</a>
</div>
