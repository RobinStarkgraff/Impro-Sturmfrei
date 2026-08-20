<?php
/* ------------------------------------------------------------
   The <head> of every page.

   Receives $slug and $page from lib/pages.php. Two things depend on the
   page: the hero photo is only preloaded on the home page, and Impressum
   and privacy policy are set to noindex.
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
  <!-- og:url, canonical, robots.txt and sitemap.xml appear automatically as
       soon as "url" in content/site.json points at the final domain. For the
       social card, also set "ogImage" (1200x630). -->
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
  <!-- Without an image it stays the small card: a large empty one is worse. -->
  <meta name="twitter:card" content="<?= $og ? 'summary_large_image' : 'summary' ?>"/>

  <!-- The icons, small. The logo itself is 2240 px wide and weighs 258 KB —
       as a favicon and as a home-screen icon that is many times what gets
       displayed, and it is not square. `make icons` puts the small versions
       next to it; while they are missing it stays with the logo (asset_or in
       lib/paths.php). -->
  <link rel="icon" href="<?= esc(asset_versioned(asset_or('images/logo/favicon.png', $brand['logo']))) ?>"/>
  <link rel="apple-touch-icon" href="<?= esc(asset_versioned(asset_or('images/logo/apple-touch-icon.png', $brand['logo']))) ?>"/>

  <!-- Fonts: one display face for headlines, one grotesque for body text.
       Self-hosted from public/fonts/ — @font-face lives in public/css/00-fonts.css.
       Please do not switch this back to fonts.googleapis.com: that hands every
       visitor's IP to Google. Anton is preloaded because it sits in the H1 of
       every page, right above the fold. -->
  <link rel="preload" as="font" type="font/woff2"
        href="<?= esc(asset('fonts/anton-v27-latin_latin-ext-regular.woff2')) ?>" crossorigin/>
<?php if ($slug === 'index'): ?>

  <link rel="preload" as="image" href="<?= esc(asset_versioned($site['hero']['photo'])) ?>" fetchpriority="high"/>
<?php endif; ?>

  <!-- The stylesheets individually, in filename order: the order comes from
       the filename, not from a list somebody would have to maintain. The stamp
       behind each name is the file's modification time — so the browser may
       keep it for as long as it likes and still fetches it again the moment it
       was edited. -->
<?php foreach (stylesheets() as $sheet): ?>
  <link rel="stylesheet" href="<?= esc(asset_versioned($sheet)) ?>"/>
<?php endforeach; ?>

  <!-- Structured data: so that Google understands the group as an entity and
       the shows as events (the prerequisite for event rich results). Built from
       content/shows.json — enter a new show there, not here.
       The performers' names deliberately do not appear as Person entities —
       first names are enough on screen, they need not be machine-readable. -->
  <script type="application/ld+json">
<?= json_ld($slug, $page) ?>

  </script>
