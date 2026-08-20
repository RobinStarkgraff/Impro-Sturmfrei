<?php
/* This page: 404 — its sections live in lib/pages.php.

   Unlike the other pages this one is not navigated to but inserted by
   Apache when an address points nowhere (ErrorDocument in the .htaccess).
   The status code comes from there; anyone calling the file directly gets
   it set here. */
require dirname(__DIR__, 2) . '/lib/boot.php';

if (http_response_code() === 200) http_response_code(404);

render_page('404');
