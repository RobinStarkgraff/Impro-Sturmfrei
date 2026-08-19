<?php declare(strict_types=1);
/* ============================================================
   Prüft die Seiten, ohne etwas zu installieren.

   Kein Ersatz für einen echten HTML-Validator, aber es fängt genau die
   Fehler, die hier schon vorgekommen sind: ein Sprungziel, das es nicht
   gibt, ein Bildpfad mit Tippfehler, ein target="_blank" ohne rel, eine
   Pflichtangabe, die in content/legal.json noch fehlt.

   Seit die Seite mehrere Seiten hat, kommt der häufigste neue Fehler dazu:
   ein Pfad, der von der Startseite aus stimmt und aus einem Unterordner
   ins Leere zeigt. Deshalb wird jeder Verweis relativ zu der Seite
   aufgelöst, in der er steht — genau so, wie der Browser es tut.

   Gesucht wird dabei in public/: das ist die Wurzel der ausgelieferten
   Seite, und nur was dort liegt, ist im Browser erreichbar.

   Gebaut wird hier nichts. Die Seiten werden gerendert wie bei einem
   Aufruf, nur in den Speicher statt zum Browser — geprüft wird also
   genau das, was auch ausgeliefert wird.

   Aufruf:  php tools/check.php   (oder: make check)
   ============================================================ */

require dirname(__DIR__) . '/lib/boot.php';

$problems = [];
$warnings = [];

$fail = function (string $message) use (&$problems): void { $problems[] = $message; };
$warn = function (string $message) use (&$warnings): void { $warnings[] = $message; };

$out = SITE_ROOT . '/public';

/* ------------------------------------------------------------
   Die Seiten rendern

   Je Seite: die Datei, die sie ausliefert (für die Meldung und als
   Ausgangspunkt für relative Pfade), und ihr HTML.
   ------------------------------------------------------------ */

/** public/index.php bzw. public/termine/index.php */
function file_for(string $slug): string
{
    return $slug === 'index' ? 'index.php' : "$slug/index.php";
}

$rendered = [];

foreach (pages() as $slug => $page) {
    // (string): PHP macht aus dem Schlüssel "404" eine Zahl — siehe die
    // Anmerkung bei slugs() in lib/pages.php.
    $slug = (string) $slug;

    ob_start();

    try {
        render_page($slug);
        $rendered[$slug] = ['file' => file_for($slug), 'html' => (string) ob_get_clean()];
    } catch (Throwable $e) {
        ob_end_clean();
        $fail(file_for($slug) . ': ' . $e->getMessage());
    }
}

/* ------------------------------------------------------------
   1. Gibt es zu jeder Seite eine Datei — und zu jeder Datei eine Seite?

   Das ist die Prüfung, die den alten Abgleich "erzeugte Datei gegen
   Quelle" ersetzt: erzeugt wird nichts mehr, aber die zwei Zeilen unter
   public/ und der Eintrag in lib/pages.php müssen zueinander passen.
   ------------------------------------------------------------ */

function check_page_files(callable $fail, string $out): void
{
    foreach (pages() as $slug => $page) {
        $slug = (string) $slug;
        $file = file_for($slug);
        $path = "$out/$file";

        if (!is_file($path)) {
            $fail("public/$file fehlt — die Seite \"$slug\" steht in lib/pages.php, ist aber nicht aufrufbar");
            continue;
        }

        // Ruft die Datei auch die Seite auf, die sie zu sein behauptet?
        // Ein kopierter Ordner mit vergessenem Namen wäre sonst zweimal
        // dieselbe Seite unter zwei Adressen.
        if (!preg_match("/render_page\(\s*'" . preg_quote($slug, '/') . "'\s*\)/", (string) file_get_contents($path))) {
            $fail("public/$file ruft nicht render_page('$slug') auf");
        }
    }

    // Umgekehrt: eine index.php unter public/, zu der es keinen Eintrag gibt.
    foreach (glob("$out/*/index.php") ?: [] as $path) {
        $slug = basename(dirname($path));
        if (!isset(pages()[$slug])) {
            $fail("public/$slug/index.php gibt es, aber keine Seite \"$slug\" in lib/pages.php");
        }
    }
}

/* ------------------------------------------------------------
   2. Dateien und Seiten, auf die die Seiten zeigen

   Aufgelöst wie im Browser: relativ zum Ordner der Seite, in der der
   Verweis steht. Ein Verweis auf einen Ordner (oder auf "../") trifft
   dessen index.php.
   ------------------------------------------------------------ */

/** Wie posix.normalize: "a/b/../c" → "a/c", ohne die Platte zu fragen. */
function normalize_path(string $path): string
{
    $parts = [];

    foreach (explode('/', $path) as $part) {
        if ($part === '' || $part === '.') continue;

        if ($part === '..') {
            if ($parts && end($parts) !== '..') array_pop($parts);
            else $parts[] = '..';

            continue;
        }

        $parts[] = $part;
    }

    return implode('/', $parts);
}

/**
 * Ein Verweis, aufgelöst wie im Browser: relativ zu $from, dem Ordner der
 * Datei, in der er steht. $where ist nur die Angabe für die Meldung.
 */
function check_target(string $target, string $from, string $where, callable $fail, string $out): void
{
    if ($target === '' || str_starts_with($target, '#')) return;
    if (preg_match('/^(https?:|mailto:|tel:|data:)/', $target)) return;

    // Anker und Änderungsstempel gehören nicht zum Dateinamen.
    $clean = preg_replace('/[#?].*$/', '', $target);
    $joined = normalize_path(($from ? "$from/" : '') . $clean);

    // Aus public/ heraus zeigt kein gültiger Verweis: was dort nicht
    // liegt, ist im Browser nicht erreichbar.
    if (str_starts_with($joined, '..')) {
        $fail("$where: \"$target\" zeigt aus public/ heraus");
        return;
    }

    $path = "$out/$joined";
    $shown = $joined;

    if (is_dir($path)) {
        $path .= '/index.php';
        $shown .= '/index.php';
    }

    if (!is_file($path)) {
        $fail("$where: \"$target\" gibt es nicht (gesucht: $shown)");
    }
}

function check_local_targets(array $rendered, callable $fail, string $out): void
{
    foreach ($rendered as $page) {
        $from = dirname($page['file']);
        $from = $from === '.' ? '' : $from;

        preg_match_all('/(?:src|href)="([^"]*)"/', $page['html'], $matches);

        foreach ($matches[1] as $target) {
            check_target($target, $from, $page['file'], $fail, $out);
        }

        // Nicht jeder Pfad steht in src oder href. Die Bilderfolge der
        // Überblendung reist als JSON in einem data-Attribut — ein Tippfehler
        // darin bliebe sonst still: die Seite sieht heil aus, der Wechsel
        // findet nur nie statt.
        preg_match_all('/data-crossfade="([^"]*)"/', $page['html'], $lists);

        foreach ($lists[1] as $raw) {
            $photos = json_decode(html_entity_decode($raw, ENT_QUOTES, 'UTF-8'), true);

            if (!is_array($photos)) {
                $fail("{$page['file']}: data-crossfade ist kein gültiges JSON");
                continue;
            }

            foreach ($photos as $photo) {
                check_target((string) $photo, $from, "{$page['file']} (data-crossfade)", $fail, $out);
            }
        }
    }
}

/**
 * Die url() in den Stylesheets.
 *
 * Der zweite Pfad, den bisher niemand nachgesehen hat — und der einzige,
 * vor dem tools/fetch-fonts.sh ausdrücklich warnt: benennt der
 * google-webfonts-helper eine Datei anders, zeigt @font-face ins Leere und
 * die Seite fällt still auf Arial Narrow zurück. Der Preload im <head>
 * fiele auf (er steht in src/href), die vier @font-face bisher nicht.
 *
 * Aufgelöst wird relativ zur CSS-Datei selbst, denn genau so tut es der
 * Browser: in public/css/00-fonts.css steht deshalb "../fonts/…".
 */
function check_css_urls(callable $fail, string $out): void
{
    foreach (glob("$out/css/*.css") ?: [] as $file) {
        $from = 'css';
        $name = 'css/' . basename($file);

        preg_match_all('/url\(\s*[\'"]?([^\'")]+)[\'"]?\s*\)/', (string) file_get_contents($file), $urls);

        foreach ($urls[1] as $target) {
            check_target(trim($target), $from, $name, $fail, $out);
        }
    }
}

/* Fotos, die daliegen, aber in content/shows.json fehlen — die tauchen
   auf der Seite nirgends auf. Der umgekehrte Fall (eingetragen, aber
   nicht da) fällt in Prüfung 2 auf: dann zeigt das <img> ins Leere. */
function check_photo_folders(callable $fail, callable $warn, string $out): void
{
    foreach (shows()['past'] as $show) {
        $dir = "images/shows/{$show['date']}";

        if (!is_dir("$out/$dir")) {
            $fail("Ordner fehlt: public/$dir");
            continue;
        }

        $listed = array_column($show['photos'], 'file');

        foreach (scandir("$out/$dir") ?: [] as $name) {
            if (!preg_match('/\.jpe?g$/i', $name)) continue;
            if (!in_array($name, $listed, true)) {
                $warn("public/$dir/$name liegt da, steht aber nicht in shows.json");
            }
        }
    }
}

/* ------------------------------------------------------------
   3. Sprungziele — je Seite, denn eine ID auf einer anderen Seite hilft
      einem #anker nicht.
   ------------------------------------------------------------ */

function check_anchors(array $rendered, callable $fail): void
{
    foreach ($rendered as $page) {
        preg_match_all('/\bid="([^"]+)"/', $page['html'], $found);
        $ids = $found[1];

        preg_match_all('/href="#([^"]+)"/', $page['html'], $anchors);

        foreach ($anchors[1] as $id) {
            if (!in_array($id, $ids, true)) {
                $fail("{$page['file']}: Sprungziel #$id gibt es dort nicht");
            }
        }

        preg_match_all('/aria-(?:labelledby|controls)="([^"]+)"/', $page['html'], $aria);

        foreach ($aria[1] as $value) {
            foreach (preg_split('/\s+/', $value) ?: [] as $id) {
                if ($id !== '' && !in_array($id, $ids, true)) {
                    $fail("{$page['file']}: aria-Verweis auf #$id gibt es dort nicht");
                }
            }
        }
    }
}

/* ------------------------------------------------------------
   4. Barrierefreiheit, externe Links, Gliederung
   ------------------------------------------------------------ */

function check_images_and_links(array $rendered, callable $fail): void
{
    $titles = [];

    foreach ($rendered as $page) {
        preg_match_all('/<img\b[^>]*>/', $page['html'], $images);

        foreach ($images[0] as $tag) {
            if (!str_contains($tag, ' alt="')) {
                $fail("{$page['file']}: <img> ohne alt: " . substr($tag, 0, 70) . '…');
            }
        }

        preg_match_all('/<a\b[^>]*>/', $page['html'], $links);

        foreach ($links[0] as $tag) {
            if (str_contains($tag, 'target="_blank"') && !preg_match('/rel="[^"]*noopener/', $tag)) {
                $fail("{$page['file']}: target=\"_blank\" ohne rel=\"noopener\": " . substr($tag, 0, 70) . '…');
            }
        }

        // Genau eine h1 je Seite: sie ist der Name der Seite. Zwei sind eine
        // Gliederung ohne Spitze, keine ist eine Seite ohne Namen.
        $h1s = preg_match_all('/<h1\b/', $page['html']);
        if ($h1s !== 1) $fail("{$page['file']}: $h1s <h1> — es muss genau eine sein");

        // Ein <title> pro Seite, und nicht auf allen derselbe: das war der
        // Grund, die Seite überhaupt aufzuteilen.
        if (!preg_match('/<title>([^<]+)<\/title>/', $page['html'], $title)) {
            $fail("{$page['file']}: <title> fehlt oder ist leer");
            continue;
        }

        $titles[$page['file']] = $title[1];
    }

    foreach (array_count_values($titles) as $title => $count) {
        if ($count > 1) $fail("zwei Seiten tragen denselben <title>: \"$title\"");
    }
}

/* ------------------------------------------------------------
   5. Navigation — zeigt sie auf Seiten, die es gibt?
   ------------------------------------------------------------ */

function check_nav(callable $fail, callable $warn): void
{
    $items = array_merge(site()['nav'], site()['legalNav']);

    foreach ($items as $item) {
        if (!in_array($item['page'], slugs(), true)) {
            $fail("content/site.json: \"{$item['label']}\" zeigt auf die Seite \"{$item['page']}\", die es in lib/pages.php nicht gibt");
        }
    }

    // Umgekehrt: eine Seite, die in keiner Liste steht, ist nur über einen
    // direkten Link erreichbar — meist ein Versehen.
    //
    // 404 ist die Ausnahme und keine Nachlässigkeit: sie wird nicht
    // angesteuert, sondern von Apache eingesetzt (ErrorDocument in der
    // .htaccess). Ein Menüpunkt "Nicht gefunden" wäre Unsinn.
    $linked = array_column($items, 'page');
    $unlisted = ['404'];

    foreach (slugs() as $slug) {
        if (!in_array($slug, $linked, true) && !in_array($slug, $unlisted, true)) {
            $warn("die Seite \"$slug\" steht in keiner Navigation — niemand findet sie");
        }
    }
}

/* ------------------------------------------------------------
   6. Der Vertrag aus js/classes.js

   Zustandsklassen und Haken stehen dort einmal; CSS und Markup müssen
   sie kennen. Gelesen wird der Quelltext mit einem Suchmuster — ein
   import wie früher geht nicht, PHP kann keine ES-Module. Wer die Datei
   umformatiert, muss also hier nachsehen; die Namen selbst stehen
   weiterhin nur an einer Stelle.
   ------------------------------------------------------------ */

/** Die Werte eines Objektliterals aus js/classes.js. */
function js_values(string $source, string $name): array
{
    if (!preg_match('/export const ' . $name . ' = \{(.*?)\n\};/s', $source, $block)) return [];

    preg_match_all('/:\s*"([^"]+)"/', $block[1], $values);

    return $values[1];
}

function kebab(string $value): string
{
    return strtolower(preg_replace('/([A-Z])/', '-$1', $value));
}

function check_contract(array $rendered, callable $fail, callable $warn, string $out): void
{
    $source = (string) file_get_contents("$out/js/classes.js");

    $css = '';
    foreach (glob("$out/css/*.css") ?: [] as $file) $css .= file_get_contents($file);

    // Jeder Zustandsname muss im CSS vorkommen, sonst schaltet das JS etwas,
    // das niemand darstellt.
    foreach (array_merge(js_values($source, 'CSS_CLASS'), js_values($source, 'DATA_KEY')) as $name) {
        if ($name === 'js') continue; // steht im CSS als Präfix .js, nicht als Klasse

        if (!str_contains($css, ".$name") && !str_contains($css, 'data-' . kebab($name))) {
            $warn("js/classes.js kennt \"$name\", im CSS steht dazu nichts");
        }
    }

    // Und umgekehrt: die Haken, an denen die Module hängen, müssen im Markup
    // stehen. Auf welcher Seite, ist offen — die Lightbox gehört nur ins
    // Archiv. Aber auf keiner einzigen wäre das Modul toter Code.
    foreach (js_values($source, 'DATA_HOOK') as $hook) {
        $found = false;

        foreach ($rendered as $page) {
            if (str_contains($page['html'], $hook)) { $found = true; break; }
        }

        if (!$found) $fail("js/ sucht $hook, keine Seite hat es");
    }
}

/* ------------------------------------------------------------
   7. Abschnitte, die niemand aufruft

   Kein Fehler, aber ein Hinweis wert: eine Datei in sections/, die in
   lib/pages.php nicht vorkommt, wird nie ausgeliefert. Ein fehlender
   Abschnitt fällt schon beim Rendern oben auf.
   ------------------------------------------------------------ */

function check_sections(callable $warn): void
{
    // Die Abschnitte, die eine Seite direkt nennt …
    $used = [];

    foreach (pages() as $page) {
        foreach ($page['sections'] as $entry) {
            $used[] = is_array($entry) ? $entry[0] : $entry;
            // … und die, die als Angabe daneben stehen (page-hero → actions).
            if (is_array($entry) && isset($entry[1]['actions'])) $used[] = $entry[1]['actions'];
        }
    }

    // Die ersten vier ruft lib/render.php für jede Seite auf, sie stehen
    // deshalb in keiner Liste. Die übrigen ruft ein anderer Abschnitt auf:
    // nav-items aus header und footer, follow-cards aus follow und dates,
    // legal-gap aus impressum und privacy.
    $always = [
        'head', 'header', 'footer', 'lightbox',
        'nav-items', 'follow-cards', 'legal-gap',
    ];

    foreach (glob(SITE_ROOT . '/sections/*.php') ?: [] as $file) {
        $name = basename($file, '.php');

        if (!in_array($name, $used, true) && !in_array($name, $always, true)) {
            $warn("sections/$name.php ruft keine Seite auf — übrig geblieben?");
        }
    }
}

/* ------------------------------------------------------------
   8. Die Termine selbst

   Zwei Dinge, die niemand sonst bemerkt: ein Datum, das keins ist, und
   ein "nächster" Termin, der längst vorbei ist.
   ------------------------------------------------------------ */

function check_shows(callable $fail, callable $warn): void
{
    $next = shows()['upcoming'] ?? null;

    if ($next) {
        if (empty($next['date']) || !is_iso_date((string) $next['date'])) {
            $fail('content/shows.json: "upcoming.date" ist kein Datum in der Form 2026-09-19');
        } elseif ($next['date'] < today()) {
            // Kein Fehler: die Seite zeigt den Termin dank upcoming_show()
            // schon nicht mehr an. Aber der Eintrag gehört umgeräumt, sonst
            // steht er ewig als Leiche in der Datei.
            $warn(
                'content/shows.json: die Show am ' . date_de((string) $next['date']) . ' ist vorbei. ' .
                'Die Seite zeigt sie nicht mehr als nächsten Termin — der Eintrag gehört jetzt ' .
                'nach "past" (mit Fotos in public/images/shows/' . $next['date'] . '/), und ' .
                '"upcoming" zurück auf null.'
            );
        }
    }

    foreach (shows()['past'] as $show) {
        if (empty($show['date']) || !is_iso_date((string) $show['date'])) {
            $fail(
                'content/shows.json: "' . ($show['title'] ?? '?') . '" hat kein Datum in der Form ' .
                '2026-01-09 — daran hängen der Ordnername der Fotos und das JSON-LD'
            );
            continue;
        }

        if ($show['date'] > today()) {
            $warn(
                'content/shows.json: "' . $show['title'] . '" steht unter "past", liegt aber am ' .
                date_de((string) $show['date']) . ' noch in der Zukunft'
            );
        }
    }
}

/* ------------------------------------------------------------
   8. Offene Punkte im Inhalt
   ------------------------------------------------------------ */

function report_content_gaps(callable $fail, callable $warn, string $out): void
{
    $open = 0;

    foreach (shows()['past'] as $show) {
        foreach ($show['photos'] as $photo) if (!$photo['alt']) $open++;
    }

    if ($open) {
        $warn(
            "$open von " . photo_count() . ' Fotos haben noch keinen eigenen alt-Text ' .
            '(content/shows.json). Ersatz ist die laufende Nummer — für Screenreader ' .
            'ist das fast nichts.'
        );
    }

    if (!site()['url']) {
        $warn('content/site.json: "url" ist noch nicht gesetzt, also gibt es kein canonical, kein og:url und keine sitemap.xml');
    } elseif (!site()['ogImage']) {
        $warn('content/site.json: "ogImage" fehlt — Links in Instagram und WhatsApp zeigen eine Karte ohne Bild');
    } elseif (!is_file($out . '/' . site()['ogImage'])) {
        // Steht nur als absolute URL im Markup, fällt dort also nicht auf.
        $fail('content/site.json verweist auf ' . site()['ogImage'] . ', public/' . site()['ogImage'] . ' gibt es nicht');
    }

    // Pflichtangaben: FEHLER, nicht Hinweis. Ohne sie darf die Seite nicht
    // öffentlich gehen (§ 5 DDG), und der Platzhalter steht sichtbar auf der
    // Seite — das soll niemand versehentlich veröffentlichen.
    $required = [
        ['impressum.responsible', legal()['impressum']['responsible'], 'Name der vertretungsberechtigten Person'],
        ['impressum.street', legal()['impressum']['street'], 'Straße und Hausnummer'],
        ['impressum.postalCode', legal()['impressum']['postalCode'], 'PLZ'],
        ['impressum.city', legal()['impressum']['city'], 'Ort'],
        ['privacy.host', legal()['privacy']['host'], 'Hosting-Anbieter für die Datenschutzerklärung'],
    ];

    foreach ($required as [$key, $value, $what]) {
        if (!$value) {
            $fail("content/legal.json: \"$key\" fehlt — $what. Solange steht auf der Seite ein sichtbarer Platzhalter.");
        }
    }

    // Solange die kleinen Symbole fehlen, liefert jede Seite das Logo in
    // voller Größe als Favicon, als Symbol fürs Homescreen und als 52-px-Marke
    // in der Kopfleiste aus (asset_or in lib/paths.php fängt es ab).
    $icons = ['images/logo/favicon.png', 'images/logo/apple-touch-icon.png', 'images/logo/logo-mark.jpg'];
    $missing_icons = array_values(array_filter($icons, fn(string $i) => !is_file("$out/$i")));

    if ($missing_icons) {
        $warn(
            count($missing_icons) . ' von 3 Symboldateien fehlen (' . implode(', ', $missing_icons) . '). ' .
            'Solange liefert jede Seite dafür ' . site()['brand']['logo'] . ' in voller Größe aus. ' .
            'Anlegen mit: make icons'
        );
    }

    if (!booking()['reviewed']) {
        $warn(
            'content/booking.json: "reviewed" steht auf false — Formate, Dauer, Personenzahl und ' .
            'die Antworten unter faq sind Entwürfe und noch von niemandem geprüft.'
        );
    }
}

/* ------------------------------------------------------------
   Los
   ------------------------------------------------------------ */

check_page_files($fail, $out);
check_local_targets($rendered, $fail, $out);
check_css_urls($fail, $out);
check_photo_folders($fail, $warn, $out);
check_anchors($rendered, $fail);
check_images_and_links($rendered, $fail);
check_nav($fail, $warn);
check_contract($rendered, $fail, $warn, $out);
check_sections($warn);
check_shows($fail, $warn);
report_content_gaps($fail, $warn, $out);

foreach ($warnings as $message) echo "  Hinweis: $message\n";
foreach ($problems as $message) echo "  FEHLER:  $message\n";

echo "\n" . count($rendered) . ' Seiten geprüft.';
echo $problems
    ? ' ' . count($problems) . ' Fehler, ' . count($warnings) . " Hinweise.\n"
    : ' Alles in Ordnung' . ($warnings ? ', ' . count($warnings) . ' Hinweise' : '') . ".\n";

exit($problems ? 1 : 0);
