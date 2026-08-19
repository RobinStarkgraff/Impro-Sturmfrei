<?php declare(strict_types=1);
/* ------------------------------------------------------------
   Die Seiten

   Eine Seite: Ordnername als Schlüssel, was im Titel steht, und welche
   Abschnitte in welcher Reihenfolge darin stehen. Alles andere —
   Kopfleiste, Footer, <head>, JSON-LD — entsteht daraus.

   "sections" nennt Dateien aus sections/ (ohne .php). Ein Abschnitt, der
   Angaben braucht, steht als Paar da: ['page-hero', [ ... ]] — die zweite
   Hälfte landet als Variablen in der Datei.

   "schema" sagt, welche Events ins JSON-LD gehören: nur die, die auf der
   Seite auch sichtbar sind.

   Zu jedem Eintrag hier gehört eine Datei public/<slug>/index.php mit zwei
   Zeilen (die Startseite: public/index.php). Fehlt sie, ist die Seite nicht
   aufrufbar; fehlt umgekehrt der Eintrag, weiß die Datei nicht, was sie
   ist — `make check` meldet beides.
   ------------------------------------------------------------ */

function pages(): array
{
    // Wie site(), shows(), booking() und legal(): einmal je Aufruf bauen.
    // render_page(), slugs(), sitemap.php und tools/check.php fragen mehrfach,
    // und jeder Neubau zählt Shows und Fotos noch einmal durch.
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

        // Keine Seite der Navigation, sondern die Antwort auf eine falsche
        // Adresse: .htaccess zeigt mit ErrorDocument hierher. Steht deshalb
        // in keiner Liste und nicht in der sitemap.xml.
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
 * Die Ordnernamen aller Seiten — für Navigation, Sitemap und `make check`.
 *
 * strval, und das ist nicht kosmetisch: PHP macht aus einem Array-Schlüssel,
 * der wie eine Zahl aussieht, eine Zahl. "404" kommt als int 404 zurück, und
 * jeder strenge Vergleich damit (in_array(..., true), string-Parameter) geht
 * schief. Hier einmal geradegezogen statt an jeder Fundstelle einzeln.
 */
function slugs(): array
{
    return array_map('strval', array_keys(pages()));
}
