<?php declare(strict_types=1);
/* ------------------------------------------------------------
   Paths

   Paths are relative, not absolute: every page knows its `base` ("" on
   the home page, "../" in a subfolder). That way the site also runs in a
   subdirectory, not only at the root of a domain.

   Everything goes through base: a file from the project (photo,
   stylesheet) via asset(), a reference to another page via page_link().
   Write "images/..." straight into the markup and you have built a link
   that points nowhere from every subpage.

   Which page is currently being built lives in a single variable: one
   request is exactly one page. tools/check.php builds them all in a row
   and sets it over and over as it goes — which is why it is a function
   and not a constant.
   ------------------------------------------------------------ */

/** Sets or reads the page currently being built. */
function current_slug(?string $slug = null): string
{
    static $current = 'index';
    if ($slug !== null) $current = $slug;

    return $current;
}

/** How deep the page sits — home page at the root, everything else one level down. */
function base(): string
{
    return current_slug() === 'index' ? '' : '../';
}

/** A file from the project, seen from this page. */
function asset(string $path): string
{
    return base() . $path;
}

/**
 * A file with a modification stamp: css/01-tokens.css?v=1750000000.
 *
 * Without a build there is no file whose name changes when its content
 * changes. The stamp does the same job: the browser may keep any file for
 * as long as it likes, yet fetches it again the moment it was edited.
 */
function asset_versioned(string $path): string
{
    $file = SITE_ROOT . "/public/$path";
    $stamp = is_file($file) ? filemtime($file) : false;

    return asset($path) . ($stamp ? "?v=$stamp" : '');
}

/**
 * The preferred file, otherwise the fallback.
 *
 * For the icons: the logo is a full-size photo (2240 px), and serving it
 * as the favicon, as the home-screen icon and as the 52 px mark in the
 * header means loading a quarter of a megabyte for a 52 px circle on
 * every page. `make icons` puts the small versions next to it; while
 * those are missing it stays with the logo — rather than a dead
 * reference, which `make check` would rightly report.
 */
function asset_or(string $preferred, string $fallback): string
{
    return is_file(SITE_ROOT . "/public/$preferred") ? $preferred : $fallback;
}

/** Another page, seen from this page. */
function page_link(string $slug): string
{
    if ($slug === 'index') return base() ?: './';

    return base() . "$slug/";
}

/** Absolute URL, or null while the domain is missing. */
function absolute(string $path): ?string
{
    $url = site()['url'] ?? null;
    if (!$url) return null;

    return rtrim($url, '/') . '/' . ltrim($path, '/');
}

/** Canonical URL of a page, or null while the domain is missing. */
function canonical(string $slug): ?string
{
    return absolute($slug === 'index' ? '' : "$slug/");
}

/** The image for the social card, absolute — or null. */
function og_image(): ?string
{
    $path = site()['ogImage'] ?? null;

    return $path ? absolute($path) : null;
}

/**
 * The stylesheets, in filename order.
 *
 * The build used to concatenate the parts into one style.css. Without a
 * build the page links them individually — the order (and with it the
 * cascade) comes from the filename, not from a list somebody would have
 * to maintain. A new section is a new file in public/css/ and nothing
 * else.
 */
function stylesheets(): array
{
    $files = glob(SITE_ROOT . '/public/css/*.css') ?: [];
    sort($files);

    return array_map(fn(string $file) => 'css/' . basename($file), $files);
}
