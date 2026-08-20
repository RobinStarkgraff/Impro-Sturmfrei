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
   visible on the page.

   Every entry here needs a two-line file public/<slug>/index.php (the home
   page: public/index.php). Without it the page cannot be reached;
   conversely, without the entry the file does not know what it is —
   `make check` reports both.

   Titles, descriptions and lead text are what visitors read, so they stay
   German.
   ------------------------------------------------------------ */

function pages(): array
{
    // Like site(), shows(), booking() and legal(): built once per request.
    // render_page(), slugs(), sitemap.php and tools/check.php all ask more
    // than once, and every rebuild counts the shows and photos again.
    static $pages;
    if ($pages !== null) return $pages;

    $site = site();
    $shows = shows();
    $brand = $site['brand'];

    return $pages = [
        'index' => [
            'navLabel' => 'Start',
            'title' => $site['meta']['title'],
            'description' => $site['meta']['description'],
            'ogDescription' => $site['meta']['ogDescription'],
            'schema' => ['upcoming'],
            'sections' => ['hero', 'next-show-teaser', 'impro', 'about', 'follow'],
        ],

        'termine' => [
            'navLabel' => 'Termine',
            'title' => "Termine – {$brand['alternateName']}",
            'description' =>
                'Die nächsten Impro-Shows von Sturmfrei in Hamburg: Termine, Tickets und die Kanäle, ' .
                'auf denen neue Abende zuerst auftauchen.',
            'schema' => ['upcoming'],
            'sections' => [
                ['page-hero', [
                    'eyebrow' => 'Wann wir spielen',
                    'title' => 'Termine',
                    'lead' =>
                        'Wir spielen unregelmäßig, und meistens in Hamburg. Was ansteht, steht hier — ' .
                        'und wenn nichts ansteht, steht das auch hier.',
                ]],
                'dates',
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
                    'lead' =>
                        'Firmenfeier, Geburtstag, Vereinsfest, Jubiläum: Wir bringen eine Show mit, die es ' .
                        'vorher nicht gab und danach nie wieder gibt — aus dem, was euer Abend hergibt.',
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
                "Rückblick auf die Impro-Shows von {$brand['name']}: " . count($shows['past']) . ' Abende, ' .
                photo_count() . ' Fotos aus dem Kulturschloss Wandsbek und anderswo.',
            'schema' => ['past'],
            'lightbox' => true,
            'sections' => [
                ['page-hero', [
                    'eyebrow' => 'Rückblick',
                    'title' => 'Archiv',
                    'lead' =>
                        count($shows['past']) . ' Shows, ' . photo_count() . ' Fotos. Kein Abend davon lässt sich ' .
                        'wiederholen — deshalb steht er hier.',
                ]],
                'archive',
            ],
        ],

        'kontakt' => [
            'navLabel' => 'Kontakt',
            'title' => "Kontakt – {$brand['alternateName']}",
            'description' =>
                "Sturmfrei aus {$site['city']} erreichen: E-Mail, Instagram und der Weg zur Anfrage " .
                'für einen eigenen Anlass.',
            'sections' => [
                ['page-hero', [
                    'eyebrow' => 'Sagt Hallo',
                    'title' => 'Kontakt',
                    'lead' =>
                        'Fragen, Buchungen oder einfach Hallo sagen. Wir lesen alles und antworten meist ' .
                        'innerhalb von zwei Tagen.',
                ]],
                'contact',
                'follow',
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
                ['page-hero', [
                    'eyebrow' => 'Fehler 404',
                    'title' => 'Hier ist nichts',
                    'lead' =>
                        'Diese Adresse gibt es nicht — vertippt, veraltet oder von uns verschoben. ' .
                        'Unten steht, wo es weitergeht.',
                ]],
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
