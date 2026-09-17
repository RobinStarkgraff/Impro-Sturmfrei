<?php declare(strict_types=1);
/* ------------------------------------------------------------
   The pages

   A page: folder name as the key, what goes in the title, and which
   sections it holds in which order. Everything else — header bar, footer,
   <head>, JSON-LD — follows from that.

   "sections" names files from sections/ (without .php). A section that
   needs values comes as a pair: ['page-hero', [ ... ]] — the second half
   arrives as variables inside the file.

   "schema" says which events belong in the JSON-LD: only those actually
   visible on the page — "next" for the nearest date alone, "upcoming" for
   every date still to come, "past" for the archive.

   Every entry here needs a two-line file public/<slug>/index.php (the home
   page: public/index.php). Without it the page cannot be reached;
   conversely, without the entry the file does not know what it is —
   `make check` reports both.

   Titles and descriptions are what visitors read, so they stay German.
   ------------------------------------------------------------ */

function pages(): array
{
    // Like site(), shows(), booking() and legal(): built once per request.
    // render_page(), slugs(), sitemap.php and tools/check.php all ask more
    // than once, and every rebuild counts the shows and photos again.
    static $pages;
    if ($pages !== null) return $pages;

    $site = site();
    $brand = $site['brand'];
    // Counted, not read: which evenings are past follows from their date.
    $played = count(past_shows());
    // The channel holding the "announcements" role in content/site.json —
    // a description naming a platform should name the one in use.
    $channel = link_for('announcements')['name'] ?? null;

    return $pages = [
        'index' => [
            'navLabel' => 'Start',
            'title' => $site['meta']['title'],
            'description' => $site['meta']['description'],
            'ogDescription' => $site['meta']['ogDescription'],
            // "next", not "upcoming": the teaser shows the nearest date only.
            'schema' => ['next'],
            'sections' => ['hero', 'next-show-teaser', 'about', 'follow'],
        ],

        'termine' => [
            'navLabel' => 'Termine',
            'title' => "Termine – {$brand['alternateName']}",
            'description' =>
                'Die nächsten Impro-Shows von Sturmfrei in Hamburg: wann wir spielen, wo es ' .
                'die Tickets gibt und auf welchen Kanälen neue Abende zuerst auftauchen.',
            'schema' => ['upcoming'],
            // "follow" sits under the dates and not on /kontakt/: somebody
            // reading a list of dates is the one looking for where the next
            // one will be announced.
            'sections' => [
                ['page-hero', ['eyebrow' => 'Wann wir spielen', 'title' => 'Termine', 'wide' => true]],
                'dates',
                'follow',
            ],
        ],

        'buchen' => [
            'navLabel' => 'Buchen',
            'title' => 'Sturmfrei buchen – Impro für euren Anlass',
            'description' =>
                'Improvisationstheater für Firmenfeier, Geburtstag oder Vereinsfest: Formate, ' .
                'Voraussetzungen und der direkte Weg zur Anfrage.',
            'ogDescription' =>
                'Wir kommen zu euch: Impro für Firmenfeier, Geburtstag oder Vereinsfest. Formate, ' .
                'was wir vor Ort brauchen, und eine Anfrage in einem Klick.',
            'sections' => [
                ['page-hero', [
                    'eyebrow' => 'Wir kommen zu euch',
                    'title' => 'Sturmfrei buchen',
                    'actions' => 'booking-actions',
                ]],
                'booking-formats',
                'booking-needs',
                'booking-price',
                'booking-faq',
                'booking-enquiry',
            ],
        ],

        'archiv' => [
            'navLabel' => 'Archiv',
            'title' => "Archiv – vergangene Shows von {$brand['name']}",
            'description' =>
                "Rückblick auf die Impro-Shows von {$brand['name']}: $played Abende, " .
                photo_count() . ' Fotos aus dem Kulturschloss Wandsbek und anderswo.',
            'schema' => ['past'],
            'lightbox' => true,
            'sections' => [
                ['page-hero', ['eyebrow' => 'Rückblick', 'title' => 'Archiv', 'wide' => true]],
                'archive',
            ],
        ],

        'kontakt' => [
            'navLabel' => 'Kontakt',
            'title' => "Kontakt – {$brand['alternateName']}",
            'description' =>
                "Sturmfrei aus {$site['city']} erreichen: E-Mail" . ($channel ? ", $channel" : '') .
                ' und der Weg zur Anfrage für einen eigenen Anlass.',
            'sections' => [
                ['page-hero', ['eyebrow' => 'Sagt Hallo', 'title' => 'Kontakt']],
                'contact',
            ],
        ],

        'impressum' => [
            'navLabel' => 'Impressum',
            'title' => "Impressum – {$brand['alternateName']}",
            'description' => "Anbieterkennzeichnung nach § 5 DDG für {$brand['alternateName']}.",
            'noindex' => true,
            'sections' => [
                ['page-hero', ['eyebrow' => 'Pflichtangaben', 'title' => 'Impressum']],
                'impressum',
            ],
        ],

        // Not a page of the navigation but the answer to a wrong address:
        // .htaccess points here with ErrorDocument. Hence it appears in no
        // list and not in sitemap.xml.
        '404' => [
            'navLabel' => 'Nicht gefunden',
            'title' => "Seite nicht gefunden – {$brand['alternateName']}",
            'description' => 'Diese Adresse gibt es auf dieser Seite nicht.',
            'noindex' => true,
            'sections' => [
                ['page-hero', ['eyebrow' => 'Fehler 404', 'title' => 'Hier ist nichts']],
                'not-found',
            ],
        ],

        'datenschutz' => [
            'navLabel' => 'Datenschutz',
            'title' => "Datenschutzerklärung – {$brand['alternateName']}",
            'description' =>
                'Was diese Seite an Daten verarbeitet — und was nicht: keine Cookies, keine ' .
                'Zugriffsmessung, keine fremden Schriften.',
            'noindex' => true,
            'sections' => [
                ['page-hero', ['eyebrow' => 'Pflichtangaben', 'title' => 'Datenschutz']],
                'privacy',
            ],
        ],
    ];
}

/**
 * The folder names of all pages — for navigation, sitemap and `make check`.
 *
 * strval, and that is not cosmetic: PHP turns an array key that looks like
 * a number into a number. "404" comes back as int 404, and every strict
 * comparison against it (in_array(..., true), string parameters) goes
 * wrong. Straightened out once here instead of at every call site.
 */
function slugs(): array
{
    return array_map('strval', array_keys(pages()));
}
