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
   The channels off the site
   ------------------------------------------------------------ */

/**
 * The channels from content/site.json, in the order they stand there.
 *
 * Every entry carries the same keys, whether the file spells them out or
 * not — "hint", "icon", "brand" and "handle" are optional, and a section
 * asking for one of them should not have to ask whether it exists first.
 * "key" comes along because `make check` names the channel it means.
 */
function links(): array
{
    static $links;
    if ($links !== null) return $links;

    $links = [];

    foreach (site()['links'] as $key => $entry) {
        $links[$key] = $entry + [
            'key' => $key,
            'handle' => null,
            'hint' => null,
            'icon' => null,
            'brand' => null,
            'roles' => [],
        ];
    }

    return $links;
}

/**
 * The channel that holds a role — "tickets", "announcements".
 *
 * What the pages ask for, so that none of them has to know that the shop
 * happens to be Eventbrite: the role sits in content/site.json and can
 * move to another channel there. Null if nobody holds it; `make check`
 * reports that, and the sections leave out what they cannot link.
 */
function link_for(string $role): ?array
{
    foreach (links() as $entry) {
        if (in_array($role, $entry['roles'], true)) return $entry;
    }

    return null;
}

/**
 * Where a date sends people for tickets.
 *
 * Its own link if it has one — an evening is sold where it is sold. Only
 * otherwise the shop, which is the same for all of them and therefore
 * stands in content/site.json and not once per show.
 */
function ticket_url(array $show): ?string
{
    return show_upcoming($show)['ticketUrl'] ?? link_for('tickets')['url'] ?? null;
}

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
 * The shows still to come — the nearest one first.
 *
 * The date comparison is what this and past_shows() are for. In
 * content/shows.json every evening stands in one list; which of the two it
 * belongs to is decided here, on every request, and nowhere else. So there
 * is nothing to move by hand — and the site cannot keep writing "Nächste
 * Show" above a date gone by, with a ticket button pointing at a closed
 * event and a JSON-LD announcing it as scheduled.
 *
 * Counted to the end of the day: whoever looks at the site on the day of
 * the show should still see the date.
 */
function upcoming_shows(): array
{
    $upcoming = array_filter(shows_listed(), fn(array $show) => $show['date'] >= today());

    usort($upcoming, fn(array $a, array $b) => $a['date'] <=> $b['date']);

    return $upcoming;
}

/**
 * The shows already played — the most recent one first.
 *
 * That order is the archive's: whoever opens it is looking for the last
 * evening, not the first. In the file the order stays chronological — that
 * is the natural one while typing entries in — and it is of no consequence,
 * because both this and upcoming_shows() sort.
 */
function past_shows(): array
{
    $past = array_filter(shows_listed(), fn(array $show) => $show['date'] < today());

    usort($past, fn(array $a, array $b) => $b['date'] <=> $a['date']);

    return $past;
}

/**
 * The shows from the file that have a usable date.
 *
 * Everything downstream compares dates, and a block without one (or with a
 * typo in it) cannot be placed in time at all: it would count as past on
 * one page and crash the sort on the next. It therefore appears nowhere,
 * and tools/check.php reports it as an error.
 */
function shows_listed(): array
{
    return array_filter(
        shows()['shows'] ?? [],
        fn(array $show) => !empty($show['date']) && is_iso_date((string) $show['date'])
    );
}

/** The next show — or null if no date is fixed. */
function upcoming_show(): ?array
{
    return upcoming_shows()[0] ?? null;
}

/**
 * The half of a block that only counts while the date is still ahead: the
 * hour, the ticket link, the price, and the title image standing in for
 * photos that do not exist yet.
 *
 * Once the evening has been played nothing reads these any more. They may
 * stay in the file — a closed ticket link is a piece of history, not a
 * mistake — and tools/check.php stops asking for them.
 */
function show_upcoming(array $show): array
{
    return $show['upcoming'] ?? [];
}

/**
 * The other half: what is written down about an evening that is over.
 *
 * Which is now only the alt texts of its pictures, and those are optional —
 * the pictures themselves are the folder's, and who played is not named per
 * evening any more. So the half is usually absent altogether, on a date
 * still to come and on a played one alike, and every reader goes through
 * here instead of reaching into the block itself.
 */
function show_past(array $show): array
{
    return $show['past'] ?? [];
}

/**
 * The title image of a coming date, or null.
 *
 * Part of the "upcoming" half, and only of that: a played evening has its
 * own photos and needs nothing standing in for it. A date still ahead has
 * none yet, which is exactly why it borrows one.
 *
 * The file lies in public/images/titles/, not in the show's own folder —
 * several dates share one image, and a copy per date would be the same
 * picture three times over. What the block adds is its alt text: for a
 * coming date this is the only picture there is.
 */
function show_cover(array $show): ?array
{
    $cover = show_upcoming($show)['cover'] ?? null;

    return empty($cover['file']) ? null : $cover;
}

/** Where the title images live — one folder, shared by every date. */
const TITLES_DIR = 'images/titles';

/** The path of a title image, seen from the site root. */
function cover_path(array $cover): string
{
    return TITLES_DIR . '/' . $cover['file'];
}

/** Photos across all past shows. */
function photo_count(): int
{
    return array_sum(array_map(fn(array $show) => count(show_photos($show)), past_shows()));
}

/** Where the show photos live — one folder per date. */
const SHOWS_DIR = 'images/shows';

/** The photo folder of one evening, seen from the site root. */
function show_photo_dir(array $show): string
{
    return SHOWS_DIR . '/' . $show['date'];
}

/** What counts as a photo in such a folder. */
const PHOTO_PATTERN = '/\.(jpe?g|png|webp)$/i';

/**
 * The photos of a show — whatever lies in public/images/shows/<date>/.
 *
 * The folder is the list. Copying its contents into content/shows.json
 * only created a second place to keep in step: nine files and eight
 * entries meant one photo the site never showed, and nothing said so.
 * Dropping a picture into the folder is now all there is to it — the
 * slider, the "9 Fotos" line and the count on the home page follow.
 *
 * In the order a person would number them: strnatcasecmp, so 10.jpg comes
 * after 9.jpg and not after 1.jpg.
 *
 * An evening played the night before has an empty folder, or none at all —
 * and until somebody has sorted through the pictures, that is the normal
 * state, not a mistake. Hence one place that answers with an empty list
 * instead of five that have to think of it.
 *
 * These are the pictures of the evening itself. The title image of a date
 * still to come is not one of them: it stands in for photos that do not
 * exist yet, and it goes away as soon as they do.
 *
 * Read once per date: the archive, the JSON-LD and tools/check.php all ask,
 * and a folder does not change in the middle of a request.
 */
function show_photos(array $show): array
{
    static $found = [];
    $date = (string) ($show['date'] ?? '');

    if (isset($found[$date])) return $found[$date];

    $dir = SITE_ROOT . '/public/' . show_photo_dir($show);
    $files = is_dir($dir)
        ? array_values(array_filter(scandir($dir) ?: [], fn(string $name) => (bool) preg_match(PHOTO_PATTERN, $name)))
        : [];

    usort($files, 'strnatcasecmp');

    // The one thing the folder cannot say: what is in the picture. An entry
    // under "past.photos" is nothing but the alt text for one file, and only
    // needed for files that have one — see photo_alts().
    $alts = photo_alts($show);

    return $found[$date] = array_map(
        fn(string $file) => ['file' => $file, 'alt' => (string) ($alts[$file] ?? '')],
        $files
    );
}

/* ------------------------------------------------------------
   The group photos

   Same principle as a show's folder above: the folder is the list. These
   two functions stand under it deliberately, because they borrow its
   PHOTO_PATTERN and its natural sort.
   ------------------------------------------------------------ */

/** Where the group photos live — the hero takes one, the crossfade the rest. */
const GROUP_DIR = 'images/group';

/**
 * Every group photo, in the order a person would number them.
 *
 * The list stood in content/site.json before, five paths written out by
 * hand, and the folder held six: one photo the site never showed, and
 * nothing said so — the same way a show's pictures used to be kept twice.
 * Drop a picture into images/group/ and it is in the rotation.
 *
 * strnatcasecmp, so 10.jpg comes after 9.jpg and not after 1.jpg.
 *
 * Read once: the section asks, and tools/check.php renders the page it
 * stands on. A folder does not change in the middle of a request.
 */
function group_photos(): array
{
    static $found = null;

    if ($found !== null) return $found;

    $dir = SITE_ROOT . '/public/' . GROUP_DIR;
    $files = is_dir($dir)
        ? array_values(array_filter(scandir($dir) ?: [], fn(string $name) => (bool) preg_match(PHOTO_PATTERN, $name)))
        : [];

    usort($files, 'strnatcasecmp');

    return $found = array_map(fn(string $file) => GROUP_DIR . '/' . $file, $files);
}

/**
 * The same photos, in the order the crossfade plays them: the title image
 * last, everything after it first.
 *
 * The title image is the one the hero shows at the top of the home page
 * (content/site.json, "hero.photo"), and the section further down opens
 * with the first entry of this list — so starting at the title image would
 * be the same picture twice on one screen. It is not dropped for that:
 * once the sequence has moved on, nobody is comparing it with the hero any
 * more, so it comes round last and the rotation holds every photo in the
 * folder.
 *
 * Rotated around the hero's own photo rather than around position 0: those
 * are the same file today (images/group/1.jpg sorts first), and a photo
 * added as 0.jpg one day should not quietly put the hero's picture back at
 * the front. A hero photo from outside this folder rotates nothing — there
 * is then no picture to stand clear of.
 */
function crossfade_photos(): array
{
    $photos = group_photos();
    $title = array_search(site()['hero']['photo'], $photos, true);

    if ($title === false) return $photos;

    return [...array_slice($photos, $title + 1), ...array_slice($photos, 0, $title + 1)];
}

/**
 * The alt texts written for this evening, as file => sentence.
 *
 * The only reason "past.photos" still exists: a screen reader can do
 * nothing with a filename, and a folder holds no sentences. Entries are
 * optional and in any order — a file without one falls back to the running
 * number, and tools/check.php counts how many of those are left.
 */
function photo_alts(array $show): array
{
    $listed = show_past($show)['photos'] ?? [];

    if (!is_array($listed)) return [];

    $alts = [];

    foreach ($listed as $photo) {
        if (empty($photo['file'])) continue;
        $alts[(string) $photo['file']] = (string) ($photo['alt'] ?? '');
    }

    return $alts;
}

/**
 * The title of a show — or, as long as none is chosen, the house.
 *
 * A date without a title is the normal state of an evening that has just
 * been booked: the hall is fixed, the poster is not. "Sturmfrei im
 * Kulturschloss Wandsbek" is then a heading that says something true,
 * and it is what goes into the JSON-LD as the event name — better than
 * an empty <h2> or an invented title that has to be corrected later.
 */
function show_title(array $show): string
{
    return empty($show['title'])
        ? site()['brand']['name'] . ' im ' . $show['venue']
        : $show['title'];
}

/**
 * "Eintritt frei", "12 €", "12,50 €" — or null if no price is given.
 *
 * Zero is a price, not a missing value: an evening that costs nothing says
 * so, and it is one of the better reasons to come. Hence isset() and not
 * empty() — the latter would treat free admission as unknown and print
 * nothing at all.
 *
 * The decimals only appear when there are any: "12,50 €" is a price,
 * "12,00 €" is a form. The space before the sign is a non-breaking one, so
 * that no line ends with the number and starts with the euro.
 */
function price_line(array $show): ?string
{
    $upcoming = show_upcoming($show);

    if (!isset($upcoming['price'])) return null;

    $price = (float) $upcoming['price'];

    if ($price <= 0) return 'Eintritt frei';

    $amount = fmod($price, 1.0) === 0.0
        ? number_format($price, 0, ',', '.')
        : number_format($price, 2, ',', '.');

    return $amount . "\u{00A0}€";
}

/**
 * "09.01.2026 · 20:00 Uhr" — or just the date if no hour is given.
 *
 * The hour sits in the "upcoming" half: it is what somebody planning an
 * evening needs. An archive entry names the day and leaves it at that.
 */
function when_line(array $show): string
{
    $time = show_upcoming($show)['time'] ?? null;

    return $time
        ? date_de($show['date']) . ' · ' . $time . ' Uhr'
        : date_de($show['date']);
}

/**
 * The date in the pieces a ticket stub carries:
 *
 *   ['weekday' => 'Freitag', 'day' => '18',
 *    'month' => 'September 2026', 'time' => '19:30 Uhr']
 *
 * Four lines instead of one, because that is how a date reads when it is
 * the thing being sold rather than a label on a list: the weekday answers
 * "does that work for me", the numeral is what the eye finds from across
 * the page, and the month stands under it small. "time" is null on a date
 * with no hour yet.
 *
 * The year rides on the month line. On the card at the top it is nearly
 * always redundant — that date is days away — but the rows below it run a
 * year out, and a stub reading "FR 8 JANUAR" in December names two
 * different evenings. One rule for all of them beats a stub that drops a
 * line depending on where it stands.
 *
 * Two lists rather than a formatter: date() answers in English, strftime()
 * is gone as of PHP 8.1, and IntlDateFormatter would be a whole extension
 * for nineteen words. null on anything that is not a date — the stub then
 * stays away entirely, which is the honest answer, and tools/check.php
 * reports the entry.
 */
const WEEKDAYS_DE = ['Sonntag', 'Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag'];

const MONTHS_DE = [
    1 => 'Januar', 'Februar', 'März', 'April', 'Mai', 'Juni',
    'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember',
];

function date_stamp(array $show): ?array
{
    if (!is_iso_date((string) $show['date'])) return null;

    $stamp = strtotime($show['date']);
    $time = show_upcoming($show)['time'] ?? null;

    return [
        'weekday' => WEEKDAYS_DE[(int) date('w', $stamp)],
        'day' => (string) (int) date('j', $stamp),
        'month' => MONTHS_DE[(int) date('n', $stamp)] . ' ' . date('Y', $stamp),
        // Non-breaking: the stub is a narrow column, and "19:30" above
        // "Uhr" would be two lines of one fact.
        'time' => $time ? $time . "\u{00A0}Uhr" : null,
    ];
}

/**
 * Days from today to the date: 0 is today, 1 tomorrow, negative is past.
 *
 * DateTimeImmutable and not (a - b) / 86400: two days in a year are 23 and
 * 25 hours long, and the division silently loses or gains one around the
 * end of March and October.
 */
function days_until(array $show): ?int
{
    if (!is_iso_date((string) $show['date'])) return null;

    $today = new DateTimeImmutable(today());
    $date = new DateTimeImmutable($show['date']);

    return (int) $today->diff($date)->format('%r%a');
}

/**
 * What the pill over the next date says: "Heute Abend", "Morgen",
 * "In 5 Tagen" — otherwise "Nächste Show".
 *
 * The line above the card used to say "Nächste Show" whatever the date,
 * which the heading of the page ("Termine") and the list below it already
 * say between them. Once the evening is close enough to plan for, the same
 * space can carry the one thing the card does not otherwise state: how far
 * off it is.
 *
 * A fortnight is where that stops being a fact and becomes arithmetic —
 * "In 43 Tagen" is a number nobody converts back into a date — so beyond
 * it the label goes back to naming what the card is.
 */
function soon_label(array $show): string
{
    $days = days_until($show);

    return match (true) {
        $days === null => 'Nächste Show',
        $days <= 0     => 'Heute Abend',
        $days === 1    => 'Morgen',
        $days <= 13    => "In $days Tagen",
        default        => 'Nächste Show',
    };
}
