<?php declare(strict_types=1);
/* ------------------------------------------------------------
   Werkzeug fürs Markup.

   Alles, was aus Daten in HTML wandert, geht durch esc() — für Text und
   Attributwerte gleichermaßen. Ein Abschnitt, der einen Wert aus
   content/ ohne esc() ausgibt, ist ein Fehler, auch wenn der Wert heute
   harmlos aussieht.
   ------------------------------------------------------------ */

/** Für Text und Attributwerte gleichermaßen. */
function esc(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Attribute für einen Link, der die Seite verlässt. */
function ext(string $url): string
{
    return 'href="' . esc($url) . '" target="_blank" rel="noopener"';
}

/**
 * mailto mit vorbereitetem Betreff und Text.
 *
 * http_build_query codiert das Leerzeichen als "+" — in einem mailto: ist
 * das ein echtes Plus und landet so im Betreff. Deshalb zurück auf %20.
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
   Die Anfrage per Mail

   Der Text, der schon in der Mail steht, ist nicht Höflichkeit, sondern
   Zweck: eine Anfrage ohne Datum, Ort und Anlass kostet zwei Mails
   Rückfragen. Wer die Zeilen überschreibt, hat genau die Angaben
   geliefert, die für eine Zahl gebraucht werden.

   Steht auf /buchen/ zweimal und auf /kontakt/ einmal — deshalb hier.
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
   Fehlende Angaben

   Impressum und Datenschutz brauchen Angaben, die niemand erfinden
   darf. Fehlt eine, steht an ihrer Stelle sichtbar, dass sie fehlt —
   und `make check` meldet es. Eine Seite mit erfundener Anschrift wäre
   schlimmer als eine, die sagt, was noch fehlt.
   ------------------------------------------------------------ */

function missing(string $what): string
{
    return '<mark class="todo">[ ' . esc($what) . ' — noch einzutragen in content/legal.json ]</mark>';
}

/** Wert oder sichtbarer Platzhalter. */
function or_missing(?string $value, string $what): string
{
    return $value ? esc($value) : missing($what);
}

/**
 * Welche der Pflichtangaben noch fehlen — die Beschriftungen der leeren.
 *
 * Nur die Auskunft, nicht das Markup: wie der Hinweis aussieht, steht in
 * sections/legal-gap.php. Ein leeres Ergebnis heißt, dass nichts fehlt —
 * die beiden Seiten fragen es als Bedingung ab.
 */
function legal_gaps(array $fields): array
{
    return array_keys(array_filter($fields, fn($value) => !$value));
}

/** Jede nicht leere Zeile um pad einrücken. */
function indent_block(string $text, string $pad): string
{
    $lines = array_map(
        fn(string $line) => $line === '' ? $line : $pad . $line,
        explode("\n", $text)
    );

    return implode("\n", $lines);
}
