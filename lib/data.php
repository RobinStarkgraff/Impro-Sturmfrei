<?php declare(strict_types=1);
/* ------------------------------------------------------------
   Die Daten aus content/.

   Vier Dateien, vier Funktionen. Gelesen wird je Aufruf einmal —
   `static` hält das Ergebnis, damit zehn Abschnitte nicht zehnmal
   dieselbe Datei aufmachen.

   $comment-Schlüssel sind Kommentare in den Datendateien, keine
   Daten, und werden hier entfernt. Auf jeder Ebene: ein $comment in
   "links" würde sonst als Follow-Karte, als Footer-Link und als
   sameAs-Eintrag im JSON-LD landen.
   ------------------------------------------------------------ */

/** Eine Datei aus content/, ohne die Kommentarschlüssel. */
function read_json(string $name): array
{
    $path = SITE_ROOT . "/content/$name.json";
    $data = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

    return strip_comments($data);
}

/** Entfernt jeden Schlüssel, der mit $comment beginnt — rekursiv. */
function strip_comments(array $data): array
{
    $clean = [];

    foreach ($data as $key => $value) {
        if (is_string($key) && str_starts_with($key, '$comment')) continue;
        $clean[$key] = is_array($value) ? strip_comments($value) : $value;
    }

    return $clean;
}

function site(): array    { static $d; return $d ??= read_json('site'); }
function shows(): array   { static $d; return $d ??= read_json('shows'); }
function booking(): array { static $d; return $d ??= read_json('booking'); }
function legal(): array   { static $d; return $d ??= read_json('legal'); }

/* ------------------------------------------------------------
   Datum
   ------------------------------------------------------------ */

/** Ist das ein Datum in der Form 2026-01-09 — und gibt es den Tag? */
function is_iso_date(string $value): bool
{
    if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $m)) return false;

    return checkdate((int) $m[2], (int) $m[3], (int) $m[1]);
}

/** Heute, als 2026-08-19. Eine Stelle, damit `make check` dieselbe Grenze zieht. */
function today(): string
{
    return date('Y-m-d');
}

/**
 * 2026-01-09 → 09.01.2026
 *
 * Steht dort etwas anderes als ein Datum, bleibt es stehen, wie es ist:
 * eine kaputte Zeile ist besser als eine Seite, die daran abbricht.
 * `make check` meldet solche Einträge ohnehin als Fehler.
 */
function date_de(string $iso): string
{
    if (!is_iso_date($iso)) return $iso;

    [$year, $month, $day] = explode('-', $iso);

    return "$day.$month.$year";
}

/* ------------------------------------------------------------
   Die Shows
   ------------------------------------------------------------ */

/**
 * Die nächste Show — oder null, wenn keine feststeht.
 *
 * Der Datumsvergleich ist der Punkt dieser Funktion. In content/shows.json
 * bleibt ein Termin stehen, bis ihn jemand von Hand nach "past" schiebt;
 * bis dahin würde die Startseite am Morgen nach der Show weiterhin
 * „Nächste Show" über ein vergangenes Datum schreiben, mit einem
 * Ticket-Knopf auf eine geschlossene Veranstaltung und einem JSON-LD, das
 * sie als geplant meldet.
 *
 * Gezählt wird bis zum Ende des Tages: wer am Showtag auf die Seite
 * schaut, soll den Termin noch sehen.
 *
 * `make check` erinnert daran, den Eintrag danach umzuräumen.
 */
function upcoming_show(): ?array
{
    $next = shows()['upcoming'] ?? null;

    if (!$next || empty($next['date'])) return null;

    return $next['date'] >= today() ? $next : null;
}

/** Fotos über alle vergangenen Shows. */
function photo_count(): int
{
    return array_sum(array_map(fn(array $show) => count($show['photos']), shows()['past']));
}

/** „09.01.2026 · 20:00 Uhr" — oder nur das Datum, wenn keine Zeit steht. */
function when_line(array $show): string
{
    return empty($show['time'])
        ? date_de($show['date'])
        : date_de($show['date']) . ' · ' . $show['time'] . ' Uhr';
}
