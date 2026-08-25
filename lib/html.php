<?php declare(strict_types=1);
/* ------------------------------------------------------------
   Markup tooling.

   Everything that travels from data into HTML goes through esc() — for
   text and attribute values alike. A section that prints a value from
   content/ without esc() is a bug, even if the value looks harmless
   today.
   ------------------------------------------------------------ */

/** For text and attribute values alike. */
function esc(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Attributes for a link that leaves the site. */
function ext(string $url): string
{
    return 'href="' . esc($url) . '" target="_blank" rel="noopener"';
}

/**
 * "Kulturschloss Wandsbek · Hamburg · Eintritt frei" — the facts of one
 * date on a single line, the empty ones dropped.
 *
 * The line binds itself: the spaces inside a fact are non-breaking, and so
 * is the one before its separator. Where the line has to wrap it therefore
 * wraps between two facts and takes the whole of the next one down with it
 * — never "Eintritt" above and "frei" below, and never a lone "·" opening
 * a line. A single fact wider than the column still breaks rather than
 * pushing the page sideways; that is the overflow-wrap in
 * public/css/03-typography.css.
 *
 * Returns escaped markup — no esc() at the call site.
 */
function dot_line(array $parts): string
{
    $kept = array_filter(
        array_map(fn($part) => trim((string) $part), $parts),
        fn(string $part) => $part !== ''
    );

    $bound = array_map(
        fn(string $part) => preg_replace('/\s+/u', "\u{00A0}", esc($part)),
        $kept
    );

    return implode("\u{00A0}\u{00B7} ", $bound);
}

/**
 * mailto with a prepared subject and body.
 *
 * http_build_query encodes the space as "+" — inside a mailto: that is a
 * literal plus and ends up in the subject line. Hence back to %20.
 */
function mailto(string $subject = '', string $body = ''): string
{
    $params = [];
    if ($subject !== '') $params['subject'] = $subject;
    if ($body !== '') $params['body'] = $body;

    $query = str_replace('+', '%20', http_build_query($params));

    return 'mailto:' . site()['email'] . ($query !== '' ? "?$query" : '');
}

/* ------------------------------------------------------------
   The enquiry by mail

   The text already sitting in the mail is not politeness but purpose: an
   enquiry without date, place and occasion costs two mails of asking
   back. Whoever types over these lines has supplied exactly the details
   needed to quote a number.

   Used twice on /buchen/ and once on /kontakt/ — hence here. The wording
   itself stays German: it is what a visitor sends.
   ------------------------------------------------------------ */

const BOOKING_SUBJECT = 'Anfrage: Sturmfrei buchen';

const BOOKING_BODY = "Hallo Sturmfrei,\n"
    . "\n"
    . "wir würden euch gern buchen. Hier unsere Angaben:\n"
    . "\n"
    . "Anlass:\n"
    . "Datum (oder Zeitraum):\n"
    . "Uhrzeit:\n"
    . "Ort / Adresse:\n"
    . "Erwartete Zuschauerzahl:\n"
    . "Gewünschtes Format:\n"
    . "Spielfläche vorhanden:\n"
    . "\n"
    . "Sonstiges:\n"
    . "\n"
    . "Viele Grüße";

function booking_mailto(): string
{
    return mailto(BOOKING_SUBJECT, BOOKING_BODY);
}

/* ------------------------------------------------------------
   Missing details

   Impressum and privacy policy need details nobody is allowed to invent.
   If one is missing, the page visibly says so in its place — and `make
   check` reports it. A page with a made-up address would be worse than
   one that says what is still missing.

   The placeholder text stays German: it appears on the page.
   ------------------------------------------------------------ */

function missing(string $what): string
{
    return '<mark class="todo">[ ' . esc($what) . ' — noch einzutragen in content/legal.json ]</mark>';
}

/** The value, or a visible placeholder. */
function or_missing(?string $value, string $what): string
{
    return $value ? esc($value) : missing($what);
}

/**
 * Which of the mandatory details are still missing — the labels of the
 * empty ones.
 *
 * The answer only, not the markup: what the notice looks like lives in
 * sections/legal-gap.php. An empty result means nothing is missing — both
 * pages use it as their condition.
 */
function legal_gaps(array $fields): array
{
    return array_keys(array_filter($fields, fn($value) => !$value));
}

/** Indent every non-empty line by pad. */
function indent_block(string $text, string $pad): string
{
    $lines = array_map(
        fn(string $line) => $line === '' ? $line : $pad . $line,
        explode("\n", $text)
    );

    return implode("\n", $lines);
}
