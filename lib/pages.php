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
            // "follow" sits under the dates: somebody reading a list of
            // dates is the one looking for where the next one will be
            // announced. It was weighed against a /kontakt/ page that no
            // longer exists, and the answer would be the same today.
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
            // "wide", like /termine/ and /archiv/: what follows the heading
            // is two format cards at the page measure, not reading text, and
            // the page's name belongs in the same column as the thing it
            // names — see .page-hero--wide in css/04-layout.css.
            //
            // The cards are the page. What used to stand under them — the
            // checklist of what we need on site, the questions, the
            // paragraph about the price, the box with the mail in it — is
            // gone: the decision this page puts is which of the two formats
            // you want, and each card says so and carries the enquiry that
            // says it for you.
            //
            // Under them the contact block (sections/contact.php), which
            // this is now the only page to carry — there was a /kontakt/
            // page holding it and nothing else, and the address and the
            // number live in the Impressum, which is where the word in the
            // header bar leads. A card's button opens a mail with the
            // questions already in it, which is no use to somebody without
            // a mail client set up, or who would rather call, so the two
            // facts stand at the foot of this page as text, to read and
            // keep.
            'sections' => [
                ['page-hero', [
                    'eyebrow' => 'Wir kommen zu euch',
                    'title' => 'Sturmfrei buchen',
                    'actions' => 'booking-actions',
                    'wide' => true,
                ]],
                'booking-formats',
                ['contact', [
                    'eyebrow' => 'Direkt an uns',
                    'heading' => 'So erreicht ihr uns',
                    'text' => 'Schreibt uns, was ihr vorhabt — Anlass, Datum, Ort und die '
                        . 'erwartete Teilnehmerzahl.',
                ]],
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
