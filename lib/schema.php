<?php declare(strict_types=1);
/* ------------------------------------------------------------
   JSON-LD

   Damit Google die Gruppe als Entität und die Shows als Events versteht
   (Voraussetzung für Event-Rich-Results). Die Gruppe steht auf jeder
   Seite (sie ist überall dieselbe Entität), die Events nur dort, wo sie
   auch sichtbar sind: die nächste Show auf Start und Termine, die
   vergangenen im Archiv.
   ------------------------------------------------------------ */

/**
 * Die Anschrift der Gruppe.
 *
 * Standardmäßig nur Ort und Land. Die vollständige Anschrift steht im
 * Impressum, weil das Gesetz sie dort verlangt — sie zusätzlich als
 * strukturierte Daten auszugeben, ist eine andere Entscheidung: Google
 * könnte sie dann als Anschrift der Gruppe in Karten und Wissenspanel
 * anzeigen, und es ist eine Privatanschrift, keine Spielstätte.
 *
 * Wer das will, setzt in content/legal.json "publishAddressInSchema": true.
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
 * Die Anschrift einer Spielstätte.
 *
 * Nur Ort und Land: content/shows.json kennt den Namen des Hauses, nicht
 * dessen Straße. Hier die Anschrift der Gruppe einzusetzen wäre bequem und
 * falsch — sie würde behaupten, das Kulturschloss Wandsbek liege in der
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
 * Zeitstempel mit Zonenversatz: 2026-01-09T20:00+01:00.
 *
 * Ohne Versatz ist "20:00" für Google eine Ortszeit ohne Ort — es rät dann,
 * und im Sommerhalbjahr rät es eine Stunde daneben. Welcher Versatz gilt,
 * hängt am Datum (Winterzeit +01:00, Sommerzeit +02:00); das weiß die
 * Zeitzone, nicht wir.
 */
function berlin_stamp(string $date, ?string $time): string
{
    if (!$time) return $date;

    $when = date_create_immutable("{$date}T{$time}", new DateTimeZone('Europe/Berlin'));

    // Kaputtes Datum oder kaputte Zeit: lieber ohne Versatz als gar nichts.
    // tools/check.php meldet solche Einträge ohnehin als Fehler.
    return $when ? $when->format('c') : "{$date}T{$time}";
}

/**
 * Wie lange eine Show dauert, wenn nichts anderes dasteht.
 *
 * Google möchte zu jedem Event ein endDate. Eine Abendshow mit Pause liegt
 * bei rund zwei Stunden — das ist geschätzt, aber es ist die Schätzung, die
 * auch im Programm stünde. "durationMinutes" je Show in content/shows.json
 * sticht sie.
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
        // Vergangene Abende sind nicht "geplant". EventScheduled heißt: findet
        // wie angekündigt statt — bei einer Show von vorletztem Januar ist das
        // keine Aussage mehr, die jemand brauchen kann.
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

        // Ohne Preis ist das Angebot für Google unvollständig. Steht in
        // content/shows.json ein "price", kommt er samt Währung dazu;
        // sonst bleibt es beim reinen Verweis auf den Ticketshop — eine
        // erfundene Zahl wäre schlimmer.
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
    // Wie bei der Anschrift eine Ecke höher: die Nummer steht im Impressum,
    // weil das Gesetz sie dort verlangt. Sie zusätzlich als strukturierte
    // Daten auszugeben ist eine zweite Entscheidung — Google trägt sie dann
    // ins Wissenspanel, und es ist eine private Mobilnummer. Wer das will,
    // setzt in content/legal.json "publishPhoneInSchema": true.
    $impressum = legal()['impressum'];

    if (!empty($impressum['publishPhoneInSchema']) && $impressum['phone']) {
        $group['telephone'] = $impressum['phone'];
    }

    return $group;
}

/** Brotkrumen — sagt Google, wo die Unterseite in der Seite hängt. */
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

    // upcoming_show() statt shows()['upcoming']: ein Termin, der vorbei ist,
    // gehört nicht mehr als geplantes Event ins JSON-LD.
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
 * Der fertige <script>-Inhalt, zwei Leerzeichen eingerückt.
 *
 * json_encode rückt mit vier Leerzeichen ein und lässt sich darin nicht
 * umstimmen; der Rest der Seite rückt mit zwei ein. Also einmal halbieren,
 * damit im Quelltext der Seite keine Stufe aus der Reihe fällt.
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
