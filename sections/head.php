<?php
/* ------------------------------------------------------------
   Der <head> jeder Seite.

   Bekommt $slug und $page aus lib/pages.php. Zwei Dinge hängen von der
   Seite ab: das Hero-Foto wird nur auf der Startseite vorgeladen, und
   Impressum und Datenschutz stehen auf noindex.
   ------------------------------------------------------------ */

$meta = $site['meta'];
$brand = $site['brand'];
$canonical = canonical($slug);
$og = og_image();
?>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?= esc($page['title']) ?></title>
  <meta name="description" content="<?= esc($page['description']) ?>"/>
  <meta name="theme-color" content="<?= esc($meta['themeColor']) ?>"/>
<?php if (!empty($page['noindex'])): ?>
  <meta name="robots" content="noindex, follow"/>
<?php endif; ?>

<?php if (!$canonical): ?>
  <!-- og:url, canonical, robots.txt und sitemap.xml entstehen automatisch,
       sobald in content/site.json "url" auf die endgültige Domain zeigt.
       Für die Social-Karte zusätzlich "ogImage" setzen (1200x630). -->
<?php else: ?>
  <link rel="canonical" href="<?= esc($canonical) ?>"/>
<?php endif; ?>

  <meta property="og:type" content="website"/>
  <meta property="og:site_name" content="<?= esc($brand['alternateName']) ?>"/>
  <meta property="og:locale" content="<?= esc($meta['locale']) ?>"/>
  <meta property="og:title" content="<?= esc($page['title']) ?>"/>
  <meta property="og:description" content="<?= esc($page['ogDescription'] ?? $page['description']) ?>"/>
<?php if ($canonical): ?>
  <meta property="og:url" content="<?= esc($canonical) ?>"/>
<?php endif; ?>
<?php if ($og): ?>
  <meta property="og:image" content="<?= esc($og) ?>"/>
  <meta property="og:image:width" content="1200"/>
  <meta property="og:image:height" content="630"/>
<?php endif; ?>
  <!-- Ohne Bild bleibt es bei der kleinen Karte: eine große leere ist schlechter. -->
  <meta name="twitter:card" content="<?= $og ? 'summary_large_image' : 'summary' ?>"/>

  <!-- Die Symbole in klein. Das Logo selbst ist 2240 px breit und wiegt
       258 KB — als Favicon und als Symbol fürs Homescreen ist das ein
       Vielfaches dessen, was angezeigt wird, und es ist nicht quadratisch.
       `make icons` legt die kleinen Fassungen daneben; solange sie fehlen,
       bleibt es beim Logo (asset_or in lib/paths.php). -->
  <link rel="icon" href="<?= esc(asset_versioned(asset_or('images/logo/favicon.png', $brand['logo']))) ?>"/>
  <link rel="apple-touch-icon" href="<?= esc(asset_versioned(asset_or('images/logo/apple-touch-icon.png', $brand['logo']))) ?>"/>

  <!-- Fonts: ein Display-Schnitt für Headlines, ein Grotesk für den Text.
       Selbst gehostet aus public/fonts/ — @font-face steht in public/css/00-fonts.css.
       Bitte nichts wieder auf fonts.googleapis.com umstellen: das überträgt
       die IP jedes Besuchers an Google. Anton wird vorgeladen, weil es in der
       H1 jeder Seite direkt über dem Falz steckt. -->
  <link rel="preload" as="font" type="font/woff2"
        href="<?= esc(asset('fonts/anton-v27-latin_latin-ext-regular.woff2')) ?>" crossorigin/>
<?php if ($slug === 'index'): ?>

  <link rel="preload" as="image" href="<?= esc(asset_versioned($site['hero']['photo'])) ?>" fetchpriority="high"/>
<?php endif; ?>

  <!-- Die Stylesheets einzeln, in Dateinamenfolge: die Reihenfolge macht der
       Dateiname, nicht eine Liste, die man pflegen müsste. Der Stempel hinter
       jedem Namen ist die Änderungszeit der Datei — so darf der Browser sie
       beliebig lange behalten und holt sie doch sofort neu, sobald sie
       bearbeitet wurde. -->
<?php foreach (stylesheets() as $sheet): ?>
  <link rel="stylesheet" href="<?= esc(asset_versioned($sheet)) ?>"/>
<?php endforeach; ?>

  <!-- Strukturierte Daten: damit Google die Gruppe als Entität und die Shows
       als Events versteht (Voraussetzung für Event-Rich-Results). Entsteht aus
       content/shows.json — neue Show dort eintragen, nicht hier.
       Die Spielernamen stehen bewusst nicht als Person-Entitäten drin —
       im Sichtbaren reichen die Vornamen, maschinenlesbar müssen sie nicht. -->
  <script type="application/ld+json">
<?= json_ld($slug, $page) ?>

  </script>
