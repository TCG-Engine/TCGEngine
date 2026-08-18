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
if (!function_exists('_VersionAsset')) {
    function _VersionAsset(string $webPath): string {
        if (preg_match('#^https?://#i', $webPath)) return $webPath;   // external → untouched
        $docRoot = $_SERVER['DOCUMENT_ROOT'] ?? '';
        if ($docRoot === '') return $webPath;                          // CLI / no docroot
        $mtime = @filemtime($docRoot . $webPath);
        if ($mtime === false) return $webPath;                         // missing → bare path
        $sep = (strpos($webPath, '?') === false) ? '?' : '&';
        return $webPath . $sep . 'v=' . $mtime;
    }
}
