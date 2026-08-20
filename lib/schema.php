<?php declare(strict_types=1);
/* ------------------------------------------------------------
   JSON-LD

   So that Google understands the group as an entity and the shows as
   events (the prerequisite for event rich results). The group appears on
   every page (it is the same entity everywhere), the events only where
   they are actually visible: the next show on the home and dates pages,
   the past ones in the archive.
   ------------------------------------------------------------ */

/**
 * The group's address.
 *
 * By default only town and country. The full address is in the Impressum
 * because the law demands it there — emitting it as structured data on
 * top of that is a separate decision: Google could then show it as the
 * group's address in maps and the knowledge panel, and it is a private
 * address, not a venue.
 *
 * If that is what you want, set "publishAddressInSchema": true in
 * content/legal.json.
 */
function group_address(): array
{
    $impressum = legal()['impressum'];

    $address = [
        '@type' => 'PostalAddress',
        'addressLocality' => $impressum['city'] ?? site()['city'],
        'addressCountry' => 'DE',
    ];

    if (empty($impressum['publishAddressInSchema'])) return $address;

    if ($impressum['street']) $address['streetAddress'] = $impressum['street'];
    if ($impressum['postalCode']) $address['postalCode'] = $impressum['postalCode'];

    return $address;
}

/**
 * A venue's address.
 *
 * Town and country only: content/shows.json knows the name of the house,
 * not its street. Putting the group's address here would be convenient
 * and wrong — it would claim that the Kulturschloss Wandsbek stands in
 * Schlettstadter Straße.
 */
function venue_address(): array
{
    return [
        '@type' => 'PostalAddress',
        'addressLocality' => site()['city'],
        'addressCountry' => 'DE',
    ];
}

/**
 * Timestamp with a zone offset: 2026-01-09T20:00+01:00.
 *
 * Without an offset, "20:00" is a local time without a locality as far as
 * Google is concerned — it guesses, and in the summer half of the year it
 * guesses an hour off. Which offset applies depends on the date (winter
 * time +01:00, summer time +02:00); the time zone knows that, we don't.
 */
function berlin_stamp(string $date, ?string $time): string
{
    if (!$time) return $date;

    $when = date_create_immutable("{$date}T{$time}", new DateTimeZone('Europe/Berlin'));

    // Broken date or broken time: better without an offset than nothing at
    // all. tools/check.php reports such entries as errors anyway.
    return $when ? $when->format('c') : "{$date}T{$time}";
}

/**
 * How long a show runs when nothing else is stated.
 *
 * Google wants an endDate for every event. An evening show with an
 * interval runs about two hours — that is a guess, but it is the guess the
 * programme would print too. "durationMinutes" per show in
 * content/shows.json overrides it.
 */
const SHOW_MINUTES = 120;

function theater_event(array $show): array
{
    $performer = ['@type' => 'TheaterGroup', 'name' => site()['brand']['name']];
    $time = $show['time'] ?? null;

    $event = [
        '@type' => 'TheaterEvent',
        'name' => $show['title'],
        'description' => $show['title'] . ' — Improvisationstheater von '
            . site()['brand']['name'] . ' im ' . $show['venue'] . ', ' . site()['city'] . '.',
        'startDate' => berlin_stamp($show['date'], $time),
        // Past evenings are not "scheduled". EventScheduled means: takes
        // place as announced — for a show from the January before last that
        // is no longer a statement anybody can use.
        'eventStatus' => $show['date'] >= today()
            ? 'https://schema.org/EventScheduled'
            : 'https://schema.org/EventCompleted',
        'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
        'performer' => $performer,
        'organizer' => $performer,
        'location' => [
            '@type' => 'Place',
            'name' => $show['venue'],
            'address' => venue_address(),
        ],
    ];

    if ($time) {
        $minutes = (int) ($show['durationMinutes'] ?? SHOW_MINUTES);
        $end = date_create_immutable("{$show['date']}T{$time}", new DateTimeZone('Europe/Berlin'));

        if ($end) $event['endDate'] = $end->modify("+$minutes minutes")->format('c');
    }

    if (og_image()) $event['image'] = og_image();

    if (!empty($show['ticketUrl'])) {
        $event['offers'] = [
            '@type' => 'Offer',
            'url' => $show['ticketUrl'],
            'availability' => 'https://schema.org/InStock',
        ];

        // Without a price the offer is incomplete as far as Google is
        // concerned. If content/shows.json carries a "price", it goes in
        // along with the currency; otherwise it stays a plain pointer at
        // the ticket shop — an invented number would be worse.
        if (isset($show['price'])) {
            $event['offers']['price'] = (string) $show['price'];
            $event['offers']['priceCurrency'] = 'EUR';
        }
    }

    return $event;
}

function theater_group(): array
{
    $site = site();

    $group = [
        '@type' => 'TheaterGroup',
        'name' => $site['brand']['name'],
        'alternateName' => $site['brand']['alternateName'],
        'description' => $site['meta']['schemaDescription'],
        'email' => $site['email'],
        'address' => group_address(),
        'sameAs' => array_values(array_map(fn(array $entry) => $entry['url'], $site['links'])),
    ];

    if ($home = canonical('index')) $group['url'] = $home;
    if (og_image()) $group['image'] = og_image();
    // Same as the address a little further up: the number is in the
    // Impressum because the law demands it there. Emitting it as structured
    // data on top of that is a second decision — Google then puts it in the
    // knowledge panel, and it is a private mobile number. If that is what
    // you want, set "publishPhoneInSchema": true in content/legal.json.
    $impressum = legal()['impressum'];

    if (!empty($impressum['publishPhoneInSchema']) && $impressum['phone']) {
        $group['telephone'] = $impressum['phone'];
    }

    return $group;
}

/** Breadcrumbs — tells Google where the subpage hangs within the site. */
function breadcrumbs(string $slug, array $page): ?array
{
    if ($slug === 'index' || !canonical($slug)) return null;

    return [
        '@type' => 'BreadcrumbList',
        'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Start', 'item' => canonical('index')],
            ['@type' => 'ListItem', 'position' => 2, 'name' => $page['navLabel'] ?? $page['title'], 'item' => canonical($slug)],
        ],
    ];
}

function structured_data(string $slug, array $page): array
{
    $graph = [theater_group()];
    $schema = $page['schema'] ?? [];

    // upcoming_show() rather than shows()['upcoming']: a date that has
    // passed no longer belongs in the JSON-LD as a scheduled event.
    if (in_array('upcoming', $schema, true) && ($next = upcoming_show())) {
        $graph[] = theater_event($next);
    }

    if (in_array('past', $schema, true)) {
        foreach (shows()['past'] as $show) $graph[] = theater_event($show);
    }

    if ($crumbs = breadcrumbs($slug, $page)) $graph[] = $crumbs;

    return ['@context' => 'https://schema.org', '@graph' => $graph];
}

/**
 * The finished <script> content, indented by two spaces.
 *
 * json_encode indents by four and cannot be talked out of it; the rest of
 * the page indents by two. So halve it once, and no step falls out of line
 * in the page source.
 */
function json_ld(string $slug, array $page): string
{
    $json = json_encode(
        structured_data($slug, $page),
        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
    );

    $json = preg_replace_callback(
        '/^ +/m',
        fn(array $m) => str_repeat(' ', intdiv(strlen($m[0]), 2)),
        $json
    );

    return indent_block($json, '  ');
}
