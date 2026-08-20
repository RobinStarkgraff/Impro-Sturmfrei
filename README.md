# Sturmfrei – Hamburger Impro Shows

Website for **Sturmfrei**, an improv theatre group from Hamburg. No
dependencies, no build: every page is assembled by PHP from
`content/*.json` and `sections/*.php` when it is requested. What is in the
repo is the site — a `git push` is enough.

The site itself is in German; the documentation and the code comments are
in English.

🔗 Instagram: [@impro_sturmfrei](https://www.instagram.com/impro_sturmfrei/)

📄 Licence: the code is MIT, **the photos are not** — they show
identifiable people, and their consent covers this site and nothing beyond
it. Details in [`LICENSE`](LICENSE).

## Structure

Exactly one folder gets served: **`public/`**. It lives in the repo and is
not generated — what lies there is reachable in a browser, and what is
reachable in a browser lies there. Next to it are the parts the pages are
made of: `content/`, `sections/`, `lib/`. Those are **deliberately not**
under `public/` — which means the web server can neither serve nor execute
them (see "Deploy").

That is the one rule this project has: **`public/` is the docroot.** A
fresh clone needs no first command:

```bash
make serve          # http://localhost:8000
make check          # syntax, paths, jump targets, alt texts, legal details
```

```
public/ — THE SITE. Gets served, lives in the repo.

  index.php                 home: two lines that call lib/ and sections/
  termine/index.php         the same for /termine/ — and so for every page
  buchen/  archiv/  kontakt/  impressum/  datenschutz/
  404/index.php             the answer to a wrong address
  sitemap.php robots.php    reachable as /sitemap.xml and /robots.txt
  css/00-fonts.css …        the styles, linked individually, in name order
  js/main.js …              the modules: header, reveal, crossfade, slider,
                            lightbox + config (numbers), classes (contract
                            with CSS and markup), motion, images
  fonts/                    Anton & Inter as woff2, self-hosted
  images/logo/              logo (also used as the favicon)
  images/group/             group photos for the hero and the crossfade
  images/shows/<date>/      photos per show, folders by date (YYYY-MM-DD)

THE PARTS — sit above public/ and are therefore not addressable

content/site.json           links, navigation, email, meta values, photos
content/shows.json          every show: date, title, venue, cast, photos
content/booking.json        formats, requirements and questions for /buchen/
content/legal.json          the details for Impressum and privacy policy

sections/hero.php …         one section of the page each, HTML with holes
sections/head.php           the <head>: title, og:, canonical, JSON-LD
sections/header.php         header bar  ·  footer.php  ·  lightbox.php
sections/page-hero.php      the dark band at the top of every subpage

lib/boot.php                where every page begins: loads everything below
lib/data.php                reading content/*.json, dates, photo counts
lib/html.php                esc(), links, mailto, missing legal details
lib/paths.php               relative paths, mtime stamps, stylesheet list
lib/schema.php              JSON-LD (group, events, breadcrumbs)
lib/pages.php               THE PAGES: title, description, sections
lib/render.php              assembles the page from them

tools/check.php             checks the pages (rendering them into memory)
tools/router.php            for `make serve` only
tools/deploy.sh             the command netcup runs after a deploy
tools/optimize-images.sh    shrinks the photos
tools/make-icons.sh         cuts favicon & co. out of the logo
tools/fetch-fonts.sh        fetches the fonts
.htaccess                   serves public/ only (see "Deploy")
Makefile                    the tasks (make help)
```

**Every value stands exactly once.** A show — date, title, venue, cast,
photos — lives in `content/shows.json`; from it come the article in the
archive, the slider tiles including their `alt` texts, the slider's
`aria-label` and the `TheaterEvent` entry in the JSON-LD. The same goes for
links, the email address and the navigation: once in `content/`, not once
per place they appear in. `<head>`, header bar and footer stand once in
`sections/` and apply to every page.

**A broken push is live immediately.** The checkout is the site — there is
nothing in between that could fail and leave the old version standing. Which
is why `make check` belongs before the push: `tools/deploy.sh` checks again
afterwards, but by then the state is already served. That is exactly why the
same check additionally runs on GitHub (see "Deploy").

The price is PHP time per request instead of a single file read: at this
size of site that is a few fractions of a millisecond, and `css/`, `js/`,
`fonts/` and `images/` are still served by the web server as plain files.

## The pages

A page is an entry in `pages()` in `lib/pages.php` — folder name, title,
description, and which sections it holds in which order. Header bar,
footer, `<head>` and JSON-LD all follow from that.

```php
'termine' => [
    'navLabel' => 'Termine',
    'title' => "Termine – {$brand['alternateName']}",
    'description' => '…',              // meta description
    'schema' => ['upcoming'],          // which events belong in the JSON-LD
    'sections' => [
        ['page-hero', ['eyebrow' => 'Wann wir spielen', 'title' => 'Termine']],
        'dates',                       // sections/dates.php
    ],
],
```

Along with it goes the file that makes it reachable —
`public/termine/index.php`, two lines:

```php
require dirname(__DIR__, 2) . '/lib/boot.php';

render_page('termine');
```

A section is a file in `sections/`: HTML with holes. It sees `$site`,
`$shows`, `$booking` and `$legal` — the data from `content/` — plus
whatever stands next to its name in `pages()`. It does not need to know
more, and in particular not which page it is on:

```php
<h2><?= esc($next['title']) ?></h2>
<p class="lead"><?= esc($next['venue']) ?> · <?= esc($site['city']) ?></p>
```

**Everything that travels from data into the markup goes through `esc()`** —
for text and attribute values alike.

**Paths are relative, not absolute.** Every page knows its `base`: `""` on
the home page, `"../"` in a subfolder. A reference to a file goes through
`asset('images/…')`, one to another page through `page_link('archiv')`.
Write `images/…` straight into the markup instead and you have built a link
that is right from the home page and points nowhere from every subfolder —
which is why `make check` resolves every reference relative to the page it
stands in.

`public/` appears in none of those paths: the root of the served site *is*
`public/`, and in a browser it is called `/`.

The home page is the only one with a hero. All the others begin with
`page-hero` — the same dark band, only without a photo and without the full
viewport height: it carries the title and the lead.

The header bar is a section of its own and not a veil over the hero: it
brings its own dark ground, is fixed to the top, and its height lives as
`--header-h` in `public/css/01-tokens.css`. Three places do arithmetic with
it — `<body>` keeps it free as `padding-top`, the hero subtracts it from
the viewport height, and every jump target keeps it free as
`scroll-margin-top`. If you want a taller bar, change exactly that one
token.

## The stylesheets

`public/css/*.css` are **linked individually**, in filename order — the
order and with it the cascade come from the filename, not from a list
somebody would have to maintain. A new section is a new file in
`public/css/` and nothing else; `sections/head.php` finds it by itself.

Behind each name stands the file's modification time (`?v=…`): the browser
may keep it for as long as it likes and still fetches it again the moment it
was edited. `js/main.js` deliberately gets none — it pulls in the remaining
modules itself, and their paths live in its source; a stamp on the parent
would therefore only appear to pass an update to `slider.js` through.

Serving one concatenated file instead of 17 would be one request fewer —
but that would need something doing the concatenating again, with cache
headers of its own. Over HTTP/2 the 17 travel over the same connection;
that is the better trade.

## Commands

```bash
make check          # syntax, paths, jump targets, alt texts, legal details
make serve          # http://localhost:8000

make images         # show what the photos weigh
make images-apply   # shrink them (overwrites public/images/!)
make icons          # favicon, home-screen icon and mark from the logo
make fonts          # fetch Anton & Inter into public/fonts/ again
```

`make check` renders every page into memory — exactly what gets served —
and checks against it:

* every reference, resolved relative to the page it stands in — including
  the image sequence in `data-crossfade` and the `url()` values in the
  stylesheets (that is where the path to the fonts sits);
* exactly one `<h1>` and a `<title>` of its own per page, and no two pages
  sharing one;
* jump targets and `aria` references per page;
* `<img>` without `alt`, `target="_blank"` without `rel="noopener"`;
* that every entry in `pages()` has a file under `public/` that actually
  calls that page — and the other way round;
* the mandatory details from `content/legal.json` (as an **error**, not a
  note);
* the contract from `js/classes.js` against CSS and markup;
* the dates in `content/shows.json` — and whether `upcoming` still holds a
  date that passed long ago.

Before that, `php -l` runs over every PHP file. That is no formality:
without a build, a typo in a PHP file is not half a page but an empty one.

**Do not open this by double-clicking.** Over `file://` nobody runs the
PHP, and Chrome additionally blocks the fonts and the ES modules there. So
`make serve` — that serves `public/` as the root, exactly as live, and
`tools/router.php` takes over the two addresses that `.htaccess` rewrites
on the server.

## Changing a navigation item

`nav` in `content/site.json`: order and labels stand there once. The footer
shows every item, the header bar the ones with `"inHeader": true`. `"page"`
is the folder name of the page — it has to exist in `pages()`, otherwise
`make check` reports it. Conversely, `make check` also warns about a page
that appears in no navigation: nobody will find that one.

`legalNav` is the Impressum and the privacy policy. Those live at the
bottom of the footer and deliberately not in the header bar.

## Adding a page

1. Write `sections/<name>.php` — HTML with holes, following the pattern of
   `contact.php` or `dates.php`. Prose belongs there, not in `content/`:
   there it would read as ballast, here as text.
2. Add an entry in `lib/pages.php` (`title`, `description`, `sections`).
3. Create `public/<slug>/index.php` — the two lines from above.
4. Add an item with the same `page` under `nav` in `content/site.json`.
5. `make check`

The exception to step 4 is `404`: that page is not navigated to but
inserted by Apache, and therefore belongs in no navigation. `make check`
knows it as an exception (`$unlisted` in `check_nav`).

## Adding a new show

1. Create the folder `public/images/shows/YYYY-MM-DD/` and put the photos
   in as `1.jpg`, `2.jpg`, … Shrink them first: `make images-apply`.
2. Add a block under `past` in `content/shows.json`, following the pattern
   of the existing ones — date, title, venue, cast, one entry per photo.
3. `make check`

Article, slider, `aria-label`, alt texts and the JSON-LD entry all follow
from that automatically; slider arrows and lightbox come from `public/js/`.

**alt texts.** If `"alt": ""` stays empty, the stopgap
"Impro-Szene 3 – Show vom 21.04.2026" ends up on the image. For screen
readers a running number is next to nothing — half a sentence about the
picture is considerably better. `make check` says how many are still open.

## Announcing the next show

Set `upcoming` in `content/shows.json` from `null` to a block (the example
stands next to it as a comment). The "Nächste Show" section then shows
date, title and a ticket button instead of the placeholder, and the JSON-LD
gains another `TheaterEvent`. A `git push` is enough — there is nothing to
build.

**After the show** the date disappears by itself: `upcoming_show()` in
`lib/data.php` only hands it out while its date has not passed (counted to
the end of the show day). The home page then falls back to the placeholder
instead of going on advertising an evening already played — and `make
check` is the reminder to move the entry to `past`.

Optional per show: `"durationMinutes"` (otherwise 120, for the `endDate` in
the JSON-LD) and `"price"` as a number in euros, if there is a `ticketUrl`
alongside.

## The domain

`"url"` in `content/site.json` is set to `https://www.impro-sturmfrei.de`.
From it come `canonical` and `og:url` per page, the absolute URLs in the
JSON-LD, the breadcrumbs as well as `/robots.txt` and `/sitemap.xml`.
Impressum and privacy policy are deliberately absent from the sitemap: they
are `noindex` and belong in the footer, not in the index.

Still to be done when setting things up at netcup:

* **Set up the git deployment** — see "Deploy" below. The site needs
  nothing beyond the PHP the webspace ships with anyway.
* **Redirect `impro-sturmfrei.de` to `www.impro-sturmfrei.de`** (301) and
  **force HTTPS** — Let's Encrypt is enough. Both stand as a commented-out
  block in `.htaccess`; if netcup offers it in the WCP, it belongs there
  and the block stays off. The `canonical` points at the www form; without
  a redirect both forms would be reachable and Google would have to guess.
  Only switch on the HTTPS enforcement once the certificate is in place —
  before that it takes the site offline.
* **Create the mailboxes.** `info@impro-sturmfrei.de` now stands in the
  Impressum, in the privacy policy, on `/kontakt/` and behind every
  "Anfrage schicken" button. An address nobody reads is worse in an
  Impressum than none at all.

Still open is the social card: put an image at 1200×630 into
`public/images/og.jpg` and set `"ogImage": "images/og.jpg"` (the path is
relative to the root of the site, so without `public/`). Without an image,
`twitter:card` deliberately stays on `summary`, because a large empty card
looks worse than a small one without an image — `make check` is the
reminder.

## Deploy

netcup pulls the repo itself (WCP → git deployment). That makes the deploy
complete: **the checkout *is* the site**, there is nothing to build and
nothing to install. Publishing is a `git push`.

The field **"Zusätzliche Deployment-Aktionen"** still holds one line:

```
sh tools/deploy.sh
```

It does not build, it looks: `php -l` over every PHP file, then
`tools/check.php`, then one line into `deploy.log` (timestamp, commit,
result). If it finds something, that something is **already online** — and
the message says so. Hence: `make check` before the push.

That is exactly why the same check additionally runs on GitHub
(`.github/workflows/check.yml`, in the container `php:8.2-cli`, on every
push and every pull request). That is the place where a mistake can show
up *before* netcup pulls — and it needs no PHP on the machine you push
from. If `php` is missing from the webspace's command line, the script
checks nothing and says so; the site itself is unaffected, because it only
needs the web server's PHP.

**The checkout is at the same time the web directory.** But only `public/`
is meant to be served. The `.htaccess` in the project root takes care of
that, and with the same rule it takes care of both things: every request is
rewritten into `public/`, and whatever does not lie there — `content/`,
`lib/`, `sections/`, `tools/`, `.git/` — is thereby not "locked off" but
not addressable, and ends as a 404. A deny list would have to be extended
with every new source file; here there is nothing to extend.

More than tidiness hangs on that: `content/*.json` and the PHP files in
`lib/` and `sections/` are **never served and never executed** by the
server, because they lie outside the served folder. Only `public/` is an
entry point, and every file there holds exactly one `render_page(...)`.

Two addresses need a rule each, because they carry the extension search
engines expect and yet come from PHP: `/sitemap.xml` → `public/sitemap.php`
and `/robots.txt` → `public/robots.php`.

Without `mod_rewrite` none of that applies — and then the site is down. As
an emergency brake there are `Options -Indexes` and a `RedirectMatch 404`
on `.git`.

Three further blocks stand in the same file, each wrapped in an
`<IfModule>` so that a server without the module in question does not bail
out:

* **Caching.** Stylesheets, fonts and images for a year with `immutable`,
  modules for a week, the pages themselves `no-cache`. Only that makes
  something of the `?v=…` behind every stylesheet: without these lines the
  browser would ask about every file individually on every visit. Which is
  also why the logo, the icons and the photos with fixed filenames (hero,
  group) now carry a stamp — the show photos need none, they live in
  folders by date.
* **Compression.** `mod_deflate` over HTML, CSS, JS and XML. Images and
  fonts are deliberately absent; those already are compressed.
* **Security headers.** `nosniff`, `Referrer-Policy`,
  `Permissions-Policy` and a Content-Security-Policy that nails everything
  down to `'self'`. The site loads nothing from third-party servers, has no
  form and no script in the markup — so the policy merely describes what
  is true anyway. Still, open every page once with the console open after
  the first deploy.

And `ErrorDocument 404 /404/`: a wrong address lands on the site's own 404
page instead of Apache's English default. The status stays 404 throughout.

**One rule is still untested.** The block redirecting `/termine` to
`/termine/` can only be tested on a real Apache. It is meant to keep
`mod_dir` from appending the missing slash itself and briefly writing
`/public/termine/` into the address bar. After the first deploy, open
`http://…/termine` (without the slash) once and see where you land. If
something goes wrong, it is the three lines under "Missing trailing slash"
— delete them and things are as they were.

If the web directory later sits **next to** the checkout, the script takes
the target as an argument: `sh tools/deploy.sh ~/httpdocs` mirrors the
project folder there (`rsync -a --delete`, otherwise `cp`) — including
`lib/`, `sections/`, `content/` and `.htaccess`, because `public/` alone
does not run.

**PHP version:** 8.0 or newer is required (`str_starts_with`,
`match`-free syntax, `??=`). The site needs no extensions at all beyond
`json`, which is built in.

## Impressum and privacy policy

Both pages exist and are complete. The details come from
`content/legal.json`, not from the markup:

* `impressum.responsible`, `street`, `postalCode`, `city` — the address at
  which legal documents can be served. Stands on `/impressum/`, as § 5 DDG
  requires.
* `impressum.entity` — only if there is a legal form (e. V., GbR). While it
  is `null`, only the person is shown.
* `impressum.publishAddressInSchema` — set to `false`. The full address
  therefore stays on the Impressum and does **not** travel into the
  JSON-LD. There the group carries only town and country: it is a private
  address and not a venue, and Google would otherwise be able to show it as
  the group's address in maps and the knowledge panel.
* `impressum.publishPhoneInSchema` — set to `false`, for the same reason as
  the address a line above. The number used to sit in the JSON-LD without
  anyone being asked while the address was protected; that was a
  contradiction, not a decision. It still stands in the Impressum and on
  `/kontakt/` — just no longer machine-readable for the knowledge panel.
  Set it to `true` if it should be there.
* `privacy.host`, `serverLocation` — netcup GmbH, servers in Nuremberg.
  Belongs in the policy, because that is where the server logs accrue.
* `privacy.logRetentionDays` — still `null`. While it is, the sentence
  stays general ("depends on the provider's own statements"). Once the
  retention period is in netcup's privacy statement, enter it here — a
  concrete number is better than a pointer.
* `privacy.processingAgreement` — set to `false`. As soon as the data
  processing agreement (Art. 28 GDPR) with netcup is signed, set it to
  `true`; the policy then names it. Before that the site does not claim it.

If a mandatory detail is missing, the fact that it is missing stands
**visibly on the page** in its place, and `make check` reports it as an
**ERROR** — not as a note. A page with a made-up address would be worse
than one that says what is still missing.

The text of the privacy policy describes what this site actually does: no
cookies, no analytics, no third-party fonts, no form. Nothing changes on
the server side now that PHP assembles the page: no data arises other than
before, only the provider's access logs. That is a solid basis but not
legal advice — have it looked over before the domain goes live.

**`content/booking.json` is a draft.** Formats, durations, head counts and
the answers under `faq` are guesses. Review them and then set
`"reviewed": true` — until that happens, `make check` points it out.

## Conventions

* **`esc()` around every value** that goes from `content/` into the markup.
  A section that prints a value without `esc()` is a bug, even if the value
  looks harmless today.
* **No colour written out in the clear** in the component CSS files. If you
  need a different opacity, take the channel token:
  `rgb(var(--accent-rgb) / 0.35)`. Otherwise the colour will not travel
  along in dark mode.
* **State classes** (`is-visible`, `is-scrolled`, …) and **hooks in the
  markup** (`data-reveal`, `data-slider`, …) live in `public/js/classes.js`
  and are the contract between JS, CSS and HTML. The modules build their
  selectors from it (`hook("slider")`), and `make check` reads the same
  file and reports when one of them is missing from a page.
* **Tuned numbers** (scroll thresholds, hold times, swipe distance) belong
  in `public/js/config.js`, not in the middle of the logic.
* **Progressive enhancement:** without JS the page stays fully readable,
  the sliders still scroll, and every tile is a link to the original photo.
  Controls that would do nothing without JS (the slider arrows) are only
  built by the JS.
* **Language:** the site is German — every string a visitor reads stays
  German, including alt texts, `aria-label`s and the prepared enquiry mail.
  Documentation, code comments and developer-facing tool output are
  English.
