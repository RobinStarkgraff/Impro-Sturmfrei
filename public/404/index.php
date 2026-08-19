<?php
/* Diese Seite: 404 — die Abschnitte stehen in lib/pages.php.

   Anders als die übrigen Seiten wird sie nicht angesteuert, sondern von
   Apache eingesetzt, wenn eine Adresse ins Leere zeigt (ErrorDocument in
   der .htaccess). Der Statuscode kommt von dort; wer die Datei direkt
   aufruft, bekommt ihn hier gesetzt. */
require dirname(__DIR__, 2) . '/lib/boot.php';

if (http_response_code() === 200) http_response_code(404);

render_page('404');
