# Sturmfrei – Hamburger Impro Shows

Website für **Sturmfrei**, eine Improvisationstheater-Gruppe aus Hamburg.
Keine Abhängigkeiten, kein Build: jede Seite wird bei ihrem Aufruf von PHP
aus `content/*.json` und `sections/*.php` zusammengesetzt. Was im Repo
steht, ist die Seite — ein `git push` genügt.

🔗 Instagram: [@impro_sturmfrei](https://www.instagram.com/impro_sturmfrei/)

📄 Lizenz: Code unter MIT, **die Fotos nicht** — auf ihnen sind erkennbare
Personen, und deren Einverständnis gilt für diese Seite und nicht darüber
hinaus. Einzelheiten in [`LICENSE`](LICENSE).

## Aufbau

Ausgeliefert wird genau ein Ordner: **`public/`**. Er liegt im Repo und
wird nicht erzeugt — was dort liegt, ist im Browser erreichbar, und was
im Browser erreichbar ist, liegt dort. Daneben liegen die Teile, aus denen
die Seiten bestehen: `content/`, `sections/`, `lib/`. Die sind **absichtlich
nicht** unter `public/` — dadurch kann der Webserver sie weder ausliefern
noch ausführen (siehe „Deploy").

Das ist die eine Regel, die das Projekt hat: **`public/` ist der Docroot.**
Ein frischer Klon braucht keinen ersten Befehl:

```bash
make serve          # http://localhost:8000
make check          # Syntax, Pfade, Sprungziele, alt-Texte, Pflichtangaben
```

```
public/ — DIE SEITE. Wird ausgeliefert, liegt im Repo.

  index.php                 Start: zwei Zeilen, die lib/ und sections/ rufen
  termine/index.php         dasselbe für /termine/ — und so für jede Seite
  buchen/  archiv/  kontakt/  impressum/  datenschutz/
  404/index.php             die Antwort auf eine falsche Adresse
  sitemap.php robots.php    erreichbar als /sitemap.xml und /robots.txt
  css/00-fonts.css …        die Styles, einzeln verlinkt, in Dateinamenfolge
  js/main.js …              die Module: header, reveal, crossfade, slider,
                            lightbox + config (Zahlen), classes (Vertrag zu
                            CSS und Markup), motion, images
  fonts/                    Anton & Inter als woff2, selbst gehostet
  images/logo/              Logo (auch als Favicon)
  images/group/             Gruppenfotos für Hero und Überblendung
  images/shows/<datum>/     Fotos je Show, Ordner nach Datum (YYYY-MM-DD)

DIE TEILE — liegen über public/ und sind deshalb nicht adressierbar

content/site.json           Links, Navigation, E-Mail, Meta-Angaben, Fotos
content/shows.json          Alle Shows: Datum, Titel, Ort, Spieler, Fotos
content/booking.json        Formate, Voraussetzungen und Fragen für /buchen/
content/legal.json          Angaben für Impressum und Datenschutz

sections/hero.php …         je ein Abschnitt der Seite, als HTML mit Löchern
sections/head.php           der <head>: Titel, og:, canonical, JSON-LD
sections/header.php         Kopfleiste  ·  footer.php  ·  lightbox.php
sections/page-hero.php      das dunkle Band am Kopf jeder Unterseite

lib/boot.php                der Anfang jeder Seite: lädt alles darunter
lib/data.php                content/*.json lesen, Datum, Fotozählung
lib/html.php                esc(), Links, mailto, fehlende Pflichtangaben
lib/paths.php               relative Pfade, Änderungsstempel, Stylesheet-Liste
lib/schema.php              JSON-LD (Gruppe, Events, Brotkrumen)
lib/pages.php               DIE SEITEN: Titel, Beschreibung, Abschnitte
lib/render.php              setzt daraus die Seite zusammen

tools/check.php             prüft die Seiten (rendert sie dazu im Speicher)
tools/router.php            nur für `make serve`
tools/deploy.sh             der Befehl, den netcup nach dem Deploy aufruft
tools/optimize-images.sh    verkleinert die Fotos
tools/make-icons.sh         schneidet Favicon & Co. aus dem Logo
tools/fetch-fonts.sh        holt die Schriften
.htaccess                   liefert nur public/ aus (siehe "Deploy")
Makefile                    die Aufgaben (make help)
```

**Jede Angabe steht genau einmal.** Eine Show — Datum, Titel, Ort, Spieler,
Fotos — steht in `content/shows.json`; daraus entstehen der Artikel im
Archiv, die Kacheln des Sliders samt `alt`-Texten, dessen `aria-label` und
der `TheaterEvent`-Eintrag im JSON-LD. Ebenso Links, E-Mail-Adresse und
Navigation: einmal in `content/`, nicht je einmal an jeder Stelle, an der
sie auftauchen. `<head>`, Kopfleiste und Footer stehen einmal in
`sections/` und gelten für jede Seite.

**Ein kaputter Push ist sofort live.** Der Checkout ist die Seite — es gibt
nichts dazwischen, das scheitern und dabei die alte Fassung stehen lassen
könnte. Deshalb gehört `make check` vor den Push: `tools/deploy.sh` prüft
danach noch einmal, aber da ist der Stand schon ausgeliefert. Genau dafür
läuft dieselbe Prüfung zusätzlich bei GitHub (siehe „Deploy").

Ein Aufruf kostet dafür PHP-Zeit statt nur einen Dateizugriff: bei dieser
Seitengröße sind das wenige Millisekundenteile, und `css/`, `js/`, `fonts/`
und `images/` liefert der Webserver weiterhin als reine Dateien aus.

## Die Seiten

Eine Seite ist ein Eintrag in `pages()` in `lib/pages.php` — Ordnername,
Titel, Beschreibung, und welche Abschnitte in welcher Reihenfolge darin
stehen. Kopfleiste, Footer, `<head>` und JSON-LD entstehen daraus.

```php
'termine' => [
    'navLabel' => 'Termine',
    'title' => "Termine – {$brand['alternateName']}",
    'description' => '…',              // meta description
    'schema' => ['upcoming'],          // welche Events ins JSON-LD gehören
    'sections' => [
        ['page-hero', ['eyebrow' => 'Wann wir spielen', 'title' => 'Termine']],
        'dates',                       // sections/dates.php
    ],
],
```

Dazu gehört die Datei, die sie aufrufbar macht — `public/termine/index.php`,
zwei Zeilen:

```php
require dirname(__DIR__, 2) . '/lib/boot.php';

render_page('termine');
```

Ein Abschnitt ist eine Datei in `sections/`: HTML mit Löchern. Er sieht
`$site`, `$shows`, `$booking` und `$legal` — die Daten aus `content/` —
und das, was in `pages()` neben seinem Namen steht. Mehr braucht er nicht
zu wissen, insbesondere nicht, auf welcher Seite er steht:

```php
<h2><?= esc($next['title']) ?></h2>
<p class="lead"><?= esc($next['venue']) ?> · <?= esc($site['city']) ?></p>
```

**Alles, was aus Daten ins Markup wandert, geht durch `esc()`** — für Text
und Attributwerte gleichermaßen.

**Pfade sind relativ, nicht absolut.** Jede Seite kennt ihr `base`: `""`
auf der Startseite, `"../"` in einem Unterordner. Ein Verweis auf eine
Datei läuft über `asset('images/…')`, einer auf eine andere Seite über
`page_link('archiv')`. Wer stattdessen `images/…` direkt ins Markup
schreibt, baut einen Link, der von der Startseite aus stimmt und aus jedem
Unterordner ins Leere zeigt — `make check` löst deshalb jeden Verweis
relativ zu der Seite auf, in der er steht.

`public/` kommt in keinem dieser Pfade vor: die Wurzel der ausgelieferten
Seite *ist* `public/`, und im Browser heißt sie `/`.

Die Startseite ist die einzige mit Hero. Alle anderen beginnen mit
`page-hero` — demselben dunklen Band, nur ohne Foto und ohne volle
Fensterhöhe: es trägt Titel und Vorspann.

Die Kopfleiste ist ein eigener Abschnitt und kein Schleier über dem Hero:
sie bringt ihren dunklen Grund selbst mit, liegt fest oben, und ihre Höhe
steht als `--header-h` in `public/css/01-tokens.css`. Drei Stellen rechnen
mit ihr — `<body>` hält sie als `padding-top` frei, der Hero zieht sie von
der Fensterhöhe ab, und jedes Sprungziel hält sie als `scroll-margin-top`
frei. Wer die Leiste höher haben will, ändert genau dieses eine Token.

## Die Stylesheets

`public/css/*.css` werden **einzeln verlinkt**, in Dateinamenfolge — die
Reihenfolge und damit die Kaskade macht der Dateiname, nicht eine Liste,
die man pflegen müsste. Ein neuer Abschnitt ist eine neue Datei in
`public/css/` und sonst nichts; `sections/head.php` findet sie von selbst.

Hinter jedem Namen steht die Änderungszeit der Datei (`?v=…`): so darf der
Browser sie beliebig lange behalten und holt sie doch sofort neu, sobald
sie bearbeitet wurde. `js/main.js` bekommt bewusst keinen — es lädt die
übrigen Module selbst nach, und deren Pfade stehen in seinem Quelltext; ein
Stempel am Elternteil würde ein Update von `slider.js` also nur scheinbar
durchreichen.

Statt 17 Dateien eine zusammengesetzte auszuliefern wäre eine Anfrage
weniger — dafür bräuchte es wieder etwas, das zusammensetzt, mit eigenen
Kopfzeilen fürs Zwischenspeichern. Über HTTP/2 laufen die 17 über dieselbe
Verbindung; das ist der bessere Tausch.

## Befehle

```bash
make check          # Syntax, Pfade, Sprungziele, alt-Texte, Pflichtangaben
make serve          # http://localhost:8000

make images         # zeigt, was die Fotos wiegen
make images-apply   # verkleinert sie (überschreibt public/images/!)
make icons          # Favicon, Homescreen-Symbol und Marke aus dem Logo
make fonts          # Anton & Inter neu nach public/fonts/ holen
```

`make check` rendert jede Seite im Speicher — genau das, was auch
ausgeliefert wird — und prüft daran:

* jeden Verweis, aufgelöst relativ zu der Seite, in der er steht — auch
  die Bilderfolge in `data-crossfade` und die `url()` in den Stylesheets
  (dort steckt der Pfad zu den Schriften);
* je Seite genau eine `<h1>` und einen eigenen `<title>`, und keine zwei
  Seiten mit demselben;
* Sprungziele und `aria`-Verweise je Seite;
* `<img>` ohne `alt`, `target="_blank"` ohne `rel="noopener"`;
* dass zu jedem Eintrag in `pages()` eine Datei unter `public/` gehört, die
  auch diese Seite aufruft — und umgekehrt;
* die Pflichtangaben aus `content/legal.json` (als **Fehler**, nicht als
  Hinweis);
* den Vertrag aus `js/classes.js` gegen CSS und Markup;
* die Datumsangaben in `content/shows.json` — und ob unter `upcoming` noch
  ein Termin steht, der längst vorbei ist.

Davor läuft `php -l` über jede PHP-Datei. Das ist keine Formalität: ohne
Build ist ein Tippfehler in einer PHP-Datei keine halbe Seite, sondern eine
leere.

**Nicht per Doppelklick öffnen.** Über `file://` führt niemand das PHP aus,
und Chrome blockiert dort außerdem die Schriften und die ES-Module. Also
`make serve` — das liefert `public/` als Wurzel aus, also genauso wie live,
und `tools/router.php` übernimmt dabei die zwei Adressen, die auf dem
Server die `.htaccess` umschreibt.

## Navigationspunkt ändern

`nav` in `content/site.json`: Reihenfolge und Beschriftung stehen dort
einmal. Der Footer zeigt alle Punkte, die Kopfleiste die mit
`"inHeader": true`. `"page"` ist der Ordnername der Seite — es muss ihn in
`pages()` geben, sonst meldet `make check` es. Umgekehrt warnt `make check`
auch bei einer Seite, die in keiner Navigation steht: die findet dann
niemand.

`legalNav` sind Impressum und Datenschutz. Die stehen unten im Footer und
absichtlich nicht in der Kopfleiste.

## Eine Seite hinzufügen

1. `sections/<name>.php` schreiben — HTML mit Löchern, nach dem Muster von
   `contact.php` oder `dates.php`. Prosa gehört dorthin, nicht nach
   `content/`: dort läse sie sich als Ballast, hier als Text.
2. In `lib/pages.php` einen Eintrag ergänzen (`title`, `description`,
   `sections`).
3. `public/<slug>/index.php` anlegen — die zwei Zeilen von oben.
4. In `content/site.json` unter `nav` einen Punkt mit demselben `page`
   ergänzen.
5. `make check`

Die Ausnahme von Schritt 4 ist `404`: die Seite wird nicht angesteuert,
sondern von Apache eingesetzt, und gehört deshalb in keine Navigation.
`make check` kennt sie als Ausnahme (`$unlisted` in `check_nav`).

## Neue Show hinzufügen

1. Ordner `public/images/shows/YYYY-MM-DD/` anlegen und die Fotos als
   `1.jpg`, `2.jpg`, … ablegen. Vorher verkleinern: `make images-apply`.
2. In `content/shows.json` unter `past` einen Block nach dem Muster der
   bestehenden ergänzen — Datum, Titel, Ort, Spieler, ein Eintrag je Foto.
3. `make check`

Artikel, Slider, `aria-label`, alt-Texte und der JSON-LD-Eintrag entstehen
daraus automatisch; Slider-Pfeile und Lightbox kommen aus `public/js/`.

**alt-Texte.** Bleibt `"alt": ""` leer, steht als Notbehelf
„Impro-Szene 3 – Show vom 21.04.2026" im Bild. Für Screenreader ist eine
laufende Nummer fast nichts — ein halber Satz zum Bild ist deutlich
besser. `make check` sagt, wie viele noch offen sind.

## Nächste Show ankündigen

`upcoming` in `content/shows.json` von `null` auf einen Block setzen (das
Beispiel steht als Kommentar daneben). Der Abschnitt „Nächste Show" zeigt
dann Datum, Titel und einen Ticket-Button statt des Platzhalters, und das
JSON-LD bekommt ein weiteres `TheaterEvent`. Ein `git push` genügt — es
gibt nichts zu bauen.

**Nach der Show** verschwindet der Termin von selbst: `upcoming_show()` in
`lib/data.php` gibt ihn nur heraus, solange sein Datum nicht vorbei ist
(gezählt bis zum Ende des Showtags). Die Startseite fällt danach auf den
Platzhalter zurück, statt weiter für einen gelaufenen Abend zu werben —
und `make check` erinnert daran, den Eintrag nach `past` umzuräumen.

Optional je Show: `"durationMinutes"` (sonst 120, fürs `endDate` im
JSON-LD) und `"price"` als Zahl in Euro, wenn eine `ticketUrl` dabeisteht.

## Die Domain

`"url"` in `content/site.json` steht auf `https://www.impro-sturmfrei.de`.
Daraus entstehen `canonical` und `og:url` je Seite, absolute URLs im
JSON-LD, die Brotkrumen sowie `/robots.txt` und `/sitemap.xml`. Impressum
und Datenschutz stehen absichtlich nicht in der Sitemap: sie sind `noindex`
und gehören in den Footer, nicht in den Index.

Beim Aufsetzen bei netcup noch zu erledigen:

* **Das Git-Deployment einrichten** — siehe „Deploy" unten. Mehr als das
  PHP, das der Webspace ohnehin mitbringt, braucht die Seite nicht.
* **`impro-sturmfrei.de` auf `www.impro-sturmfrei.de` umleiten** (301) und
  **HTTPS erzwingen** — Let's Encrypt reicht. Beides steht als
  auskommentierter Block in `.htaccess`; wenn netcup es im WCP anbietet,
  gehört es dorthin und der Block bleibt aus. Das `canonical` zeigt auf die
  www-Form; ohne Umleitung wären beide Formen erreichbar und Google müsste
  raten. Den HTTPS-Zwang erst einschalten, wenn das Zertifikat steht —
  vorher schaltet er die Seite ab.
* **Die Postfächer anlegen.** `info@impro-sturmfrei.de` steht jetzt im
  Impressum, in der Datenschutzerklärung, auf `/kontakt/` und hinter jedem
  „Anfrage schicken"-Knopf. Eine Adresse, die niemand liest, ist im
  Impressum schlimmer als keine.

Offen bleibt die Social-Karte: ein Bild als 1200×630 nach
`public/images/og.jpg` legen und `"ogImage": "images/og.jpg"` setzen (der
Pfad ist relativ zur Wurzel der Seite, also ohne `public/`). Ohne Bild
bleibt `twitter:card` absichtlich auf `summary`, weil eine große leere
Karte schlechter aussieht als eine kleine ohne Bild — `make check`
erinnert daran.

## Deploy

netcup zieht das Repo selbst (WCP → Git-Deployment). Damit ist der Deploy
fertig: **der Checkout *ist* die Seite**, es gibt nichts zu bauen und
nichts zu installieren. Ein Veröffentlichen ist ein `git push`.

Im Feld **„Zusätzliche Deployment-Aktionen"** steht trotzdem eine Zeile:

```
sh tools/deploy.sh
```

Sie baut nicht, sie sieht nach: `php -l` über jede PHP-Datei, dann
`tools/check.php`, dann eine Zeile nach `deploy.log` (Zeitstempel, Commit,
Ergebnis). Findet sie etwas, ist es **schon online** — die Meldung sagt
das auch. Deshalb: `make check` vor dem Push.

Genau dafür läuft dieselbe Prüfung zusätzlich bei GitHub
(`.github/workflows/check.yml`, im Container `php:8.2-cli`, bei jedem Push
und jedem Pull Request). Das ist die Stelle, an der ein Fehler auffallen
kann, *bevor* netcup zieht — und sie braucht kein PHP auf dem Rechner, von
dem aus gepusht wird. Fehlt `php` auf der
Kommandozeile des Webspace, prüft das Skript nichts und sagt das; die Seite
selbst läuft davon unberührt, denn sie braucht nur das PHP des Webservers.

**Der Checkout ist gleichzeitig das Webverzeichnis.** Ausgeliefert werden
soll aber nur `public/`. Das erledigt die `.htaccess` im Projektstamm, und
sie erledigt mit derselben Regel beides: jede Anfrage wird nach `public/`
umgeschrieben, und was dort nicht liegt — `content/`, `lib/`, `sections/`,
`tools/`, `.git/` — ist damit nicht „gesperrt", sondern nicht adressierbar
und endet als 404. Eine Deny-Liste müsste man bei jeder neuen Quelldatei
nachziehen; hier gibt es nichts nachzuziehen.

Daran hängt mehr als Ordnung: `content/*.json` und die PHP-Dateien in
`lib/` und `sections/` werden vom Server **nie ausgeliefert und nie
ausgeführt**, weil sie außerhalb des ausgelieferten Ordners liegen. Nur
`public/` ist ein Einstiegspunkt, und dort steht in jeder Datei genau ein
`render_page(...)`.

Zwei Adressen brauchen je eine eigene Regel, weil sie die Endung tragen,
die Suchmaschinen erwarten, und doch von PHP kommen: `/sitemap.xml` →
`public/sitemap.php` und `/robots.txt` → `public/robots.php`.

Ohne `mod_rewrite` greift nichts davon — dann steht die Seite nicht. Als
Notbremse gibt es `Options -Indexes` und ein `RedirectMatch 404` auf
`.git`.

Drei weitere Blöcke stehen in derselben Datei, jeder mit `<IfModule>`
umschlossen, damit ein Server ohne das jeweilige Modul nicht aussteigt:

* **Zwischenspeichern.** Stylesheets, Schriften und Bilder ein Jahr mit
  `immutable`, Module eine Woche, die Seiten selbst `no-cache`. Erst das
  macht aus dem `?v=…` hinter jedem Stylesheet etwas: ohne diese Zeilen
  fragte der Browser bei jedem Besuch für jede Datei einzeln nach.
  Deshalb tragen jetzt auch Logo, Symbole und die Fotos mit festem
  Dateinamen (Hero, Gruppe) einen Stempel — die Show-Fotos brauchen keinen,
  sie liegen in Ordnern nach Datum.
* **Komprimieren.** `mod_deflate` über HTML, CSS, JS und XML. Bilder und
  Schriften stehen bewusst nicht drin, die sind es schon.
* **Kopfzeilen zur Sicherheit.** `nosniff`, `Referrer-Policy`,
  `Permissions-Policy` und eine Content-Security-Policy, die alles auf
  `'self'` festnagelt. Die Seite lädt nichts von fremden Servern, hat kein
  Formular und kein Skript im Markup — die Richtlinie beschreibt also nur,
  was ohnehin gilt. Nach dem ersten Deploy trotzdem einmal jede Seite mit
  offener Konsole aufrufen.

Und `ErrorDocument 404 /404/`: eine falsche Adresse landet auf der eigenen
404-Seite statt auf Apaches englischer Vorgabe. Der Status bleibt dabei 404.

**Eine Regel ist noch ungeprüft.** Der Block, der `/termine` auf
`/termine/` umleitet, lässt sich nur auf einem echten Apache testen. Er
soll verhindern, dass `mod_dir` den fehlenden Schrägstrich selbst anhängt
und dabei kurz `/public/termine/` in die Adresszeile schreibt. Nach dem
ersten Deploy einmal `http://…/termine` (ohne Schrägstrich) aufrufen und
sehen, wo man landet. Geht etwas schief, sind es die drei Zeilen unter
„Fehlender Schrägstrich am Ende" — löschen und es ist wie vorher.

Liegt das Webverzeichnis später **neben** dem Checkout, nimmt das Skript
das Ziel als Argument: `sh tools/deploy.sh ~/httpdocs` spiegelt den
Projektordner dorthin (`rsync -a --delete`, sonst `cp`) — mitsamt `lib/`,
`sections/`, `content/` und `.htaccess`, denn `public/` allein läuft nicht.

**PHP-Version:** gebraucht wird 8.0 oder neuer (`str_starts_with`,
`match`-freie Syntax, `??=`). Erweiterungen braucht die Seite gar keine
außer `json`, das fest eingebaut ist.

## Impressum und Datenschutz

Beide Seiten stehen und sind vollständig. Die Angaben kommen aus
`content/legal.json`, nicht aus dem Markup:

* `impressum.responsible`, `street`, `postalCode`, `city` — die
  ladungsfähige Anschrift. Steht auf `/impressum/`, wie § 5 DDG es verlangt.
* `impressum.entity` — nur, wenn es eine Rechtsform gibt (e. V., GbR).
  Solange `null`, steht dort nur die Person.
* `impressum.publishAddressInSchema` — steht auf `false`. Die vollständige
  Anschrift bleibt damit auf dem Impressum und wandert **nicht** ins
  JSON-LD. Dort trägt die Gruppe nur Ort und Land: es ist eine
  Privatanschrift und keine Spielstätte, und Google würde sie sonst als
  Anschrift der Gruppe in Karten und Wissenspanel anzeigen können.
* `impressum.publishPhoneInSchema` — steht auf `false`, aus demselben
  Grund wie die Anschrift eine Zeile höher. Die Nummer stand vorher ohne
  Rückfrage im JSON-LD, während die Anschrift geschützt war; das war ein
  Widerspruch, keine Entscheidung. Sie steht weiterhin im Impressum und
  auf `/kontakt/` — nur nicht mehr maschinenlesbar für das Wissenspanel.
  Auf `true` setzen, wenn sie dort stehen soll.
* `privacy.host`, `serverLocation` — netcup GmbH, Server in Nürnberg.
  Gehört in die Erklärung, weil dort die Server-Logs anfallen.
* `privacy.logRetentionDays` — noch `null`. Solange bleibt der Satz
  allgemein („richtet sich nach dessen eigenen Angaben"). Steht die Frist
  in netcups Datenschutzangaben, hier eintragen — eine konkrete Zahl ist
  besser als ein Verweis.
* `privacy.processingAgreement` — steht auf `false`. Sobald der Vertrag
  über die Auftragsverarbeitung (Art. 28 DSGVO) mit netcup geschlossen ist,
  auf `true` setzen; dann nennt die Erklärung ihn. Vorher behauptet die
  Seite ihn nicht.

Fehlt eine Pflichtangabe, steht an ihrer Stelle **sichtbar auf der Seite**,
dass sie fehlt, und `make check` meldet es als **FEHLER** — nicht als
Hinweis. Eine Seite mit erfundener Anschrift wäre schlimmer als eine, die
sagt, was noch fehlt.

Der Text der Datenschutzerklärung beschreibt, was diese Seite tatsächlich
tut: keine Cookies, keine Zugriffsmessung, keine fremden Schriften, kein
Formular. Am Serverseitigen ändert sich dadurch nichts, dass jetzt PHP die
Seite zusammensetzt: es entstehen keine anderen Daten als vorher, nur die
Zugriffsprotokolle des Anbieters. Das ist eine belastbare Grundlage, aber
kein Rechtsrat — lasst ihn einmal ansehen, bevor die Domain live geht.

**`content/booking.json` ist ein Entwurf.** Formate, Dauer, Personenzahl
und die Antworten unter `faq` sind geraten. Prüft sie und setzt dann
`"reviewed": true` — bis dahin weist `make check` darauf hin.

## Konventionen

* **`esc()` um jeden Wert**, der aus `content/` ins Markup geht. Ein
  Abschnitt, der einen Wert ohne `esc()` ausgibt, ist ein Fehler, auch wenn
  der Wert heute harmlos aussieht.
* **Keine Farbe im Klartext** in den Komponenten-CSS-Dateien. Wer eine
  andere Deckkraft braucht, nimmt das Kanal-Token:
  `rgb(var(--accent-rgb) / 0.35)`. Sonst wandert die Farbe im Dark Mode
  nicht mit.
* **Zustandsklassen** (`is-visible`, `is-scrolled`, …) und **Haken im
  Markup** (`data-reveal`, `data-slider`, …) stehen in
  `public/js/classes.js` und sind der Vertrag zwischen JS, CSS und HTML.
  Die Module bauen ihre Selektoren daraus (`hook("slider")`), `make check`
  liest dieselbe Datei und meldet, wenn eine Seite davon fehlt.
* **Abgestimmte Zahlen** (Scrollschwellen, Standzeiten, Wischdistanz)
  gehören in `public/js/config.js`, nicht mitten in die Logik.
* **Progressive Enhancement:** ohne JS bleibt die Seite vollständig
  lesbar, die Slider scrollen weiter, und jede Kachel ist ein Link auf
  das Originalfoto. Bedienelemente, die ohne JS nichts täten (die
  Slider-Pfeile), baut erst das JS.
