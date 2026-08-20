<?php declare(strict_types=1);
/* ------------------------------------------------------------
   The data from content/.

   Four files, four functions. Read once per request — `static` holds
   on to the result so that ten sections don't open the same file ten
   times.

   $comment keys are comments in the data files, not data, and are
   stripped here. At every level: a $comment inside "links" would
   otherwise end up as a follow card, as a footer link and as a sameAs
   entry in the JSON-LD.
   ------------------------------------------------------------ */

/** One file from content/, without the comment keys. */
function read_json(string $name): array
{
    $path = SITE_ROOT . "/content/$name.json";
    $data = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

    return strip_comments($data);
}

/** Strips every key starting with $comment — recursively. */
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
   Dates
   ------------------------------------------------------------ */

/** Is this a date in the form 2026-01-09 — and does that day exist? */
function is_iso_date(string $value): bool
{
    if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $m)) return false;

    return checkdate((int) $m[2], (int) $m[3], (int) $m[1]);
}

/** Today, as 2026-08-19. One place, so `make check` draws the same line. */
function today(): string
{
    return date('Y-m-d');
}

/**
 * 2026-01-09 → 09.01.2026
 *
 * If it holds something other than a date, it is left as it is: a broken
 * line is better than a page that dies on it. `make check` reports such
 * entries as errors anyway.
 */
function date_de(string $iso): string
{
    if (!is_iso_date($iso)) return $iso;

    [$year, $month, $day] = explode('-', $iso);

    return "$day.$month.$year";
}

/* ------------------------------------------------------------
   The shows
   ------------------------------------------------------------ */

/**
 * The next show — or null if none is fixed.
 *
 * The date comparison is the whole point of this function. In
 * content/shows.json a date stays put until somebody moves it to "past"
 * by hand; until then the home page would keep writing "Nächste Show"
 * above a date gone by on the morning after the show, with a ticket
 * button pointing at a closed event and a JSON-LD announcing it as
 * scheduled.
 *
 * Counted to the end of the day: whoever looks at the site on the day of
 * the show should still see the date.
 *
 * `make check` is the reminder to move the entry afterwards.
 */
function upcoming_show(): ?array
{
    $next = shows()['upcoming'] ?? null;

    if (!$next || empty($next['date'])) return null;

    return $next['date'] >= today() ? $next : null;
}

/** Photos across all past shows. */
function photo_count(): int
{
    return array_sum(array_map(fn(array $show) => count($show['photos']), shows()['past']));
}

/** "09.01.2026 · 20:00 Uhr" — or just the date if no time is given. */
function when_line(array $show): string
{
    return empty($show['time'])
        ? date_de($show['date'])
        : date_de($show['date']) . ' · ' . $show['time'] . ' Uhr';
}
