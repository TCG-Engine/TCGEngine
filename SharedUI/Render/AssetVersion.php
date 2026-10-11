<?php
// The single cache-busting seam for local front-end assets.
//
// Appends ?v=<filemtime> to a web path so an edited CSS/JS file is refetched immediately instead of
// being served from a browser or CDN cache. It lived inside Render/Head.php, which meant only pages
// going through RenderHead could use it — every hand-written <script src> elsewhere (the game page's
// decision-UI bundles, the menu bar, the main menus, the admin tools) shipped unversioned and could
// serve a stale copy after a deploy with nothing to force a refresh.
//
// ⚠ This is NOT the same lever as the datestamped filename on Core/UILibraries<YYYYMMDD>.js. That
// rename survives a CDN configured to ignore query strings; this does not. The bundle deliberately
// carries BOTH — see DevTools/bump-uilibraries-cache.py.
//
// Fail-safe by design: an external URL, a missing DOCUMENT_ROOT (CLI) or a missing file all return the
// path unchanged rather than emitting a broken URL. That also means a wrong path silently loses its
// versioning instead of 404ing, so pass real web paths ("/TCGEngine/Core/x.js"), not filesystem ones.
// $pathVersioned = true emits /path/name.<mtime>.css instead of /path/name.css?v=<mtime>, so the
// bust survives a CDN that strips query strings — the same lever UILibraries<YYYYMMDD>.js uses,
// finally available to CSS. Requires the .htaccess rewrite that maps name.<digits>.css back to
// name.css; without it the request 404s, so never flip this on for a path the rewrite cannot reach.
if (!function_exists('_VersionAsset')) {
    function _VersionAsset(string $webPath, bool $pathVersioned = false): string {
        if (preg_match('#^https?://#i', $webPath)) return $webPath;   // external → untouched
        $docRoot = $_SERVER['DOCUMENT_ROOT'] ?? '';
        if ($docRoot === '') return $webPath;                          // CLI / no docroot
        $mtime = @filemtime($docRoot . $webPath);
        if ($mtime === false) return $webPath;                         // missing → bare path
        if ($pathVersioned) {
            // only rewrite a real .css/.js tail, and only when there is no query string to confuse
            if (strpos($webPath, '?') === false
                && preg_match('#\.(css|js)$#i', $webPath)) {
                return preg_replace('#\.(css|js)$#i', ".{$mtime}.$1", $webPath);
            }
            return $webPath . '?v=' . $mtime;                          // fall back rather than break
        }
        $sep = (strpos($webPath, '?') === false) ? '?' : '&';
        return $webPath . $sep . 'v=' . $mtime;
    }
}
