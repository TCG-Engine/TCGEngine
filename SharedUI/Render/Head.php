<?php
require_once __DIR__ . '/SiteDef.php';   // LoadSiteDef() — for RenderSiteStyles()

// Emit a site's versioned stylesheet stack straight from its SiteDef styles array, for pages
// that build their own <head> instead of going through RenderHead (the SWU Stats pages). Single
// source of truth = the SiteDef; replaces the old per-app hud-head.php partial.
function RenderSiteStyles(string $site): string {
    $def = LoadSiteDef($site);
    $out = _RenderThemeStack($def);
    foreach (($def['head']['styles'] ?? []) as $s) {
        $out .= '  <link rel="stylesheet" href="' . _VersionAsset($s) . "\">\n";
    }
    return $out;
}

// Map known font names to their Google Fonts <link> tags (faithful to current SWUDeck head).
function _RenderFontLinks(array $fonts): string {
    $map = [
        'Barlow' => '  <link href="https://fonts.googleapis.com/css2?family=Barlow:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">' . "\n",
        'Teko'   => '  <link href="https://fonts.googleapis.com/css2?family=Teko:wght@700&display=swap" rel="stylesheet">' . "\n",
    ];
    $out = '';
    foreach ($fonts as $f) { $out .= $map[$f] ?? ''; }
    return $out;
}

// Append an auto-updating ?v=<filemtime> token to a local asset URL so CDN/browser
// caches pick up edits immediately. External URLs and missing files pass through unchanged.
function _VersionAsset(string $webPath): string {
    if (preg_match('#^https?://#i', $webPath)) return $webPath;   // external → untouched
    $docRoot = $_SERVER['DOCUMENT_ROOT'] ?? '';
    if ($docRoot === '') return $webPath;                          // CLI / no docroot
    $mtime = @filemtime($docRoot . $webPath);
    if ($mtime === false) return $webPath;                         // missing → bare path
    $sep = (strpos($webPath, '?') === false) ? '?' : '&';
    return $webPath . $sep . 'v=' . $mtime;
}

// Themes whose menu base is the shared menuStyles.css (loaded FIRST, before components).
// Gradient/bespoke menu themes (clarent/gudnak/petranaki) supply their own menu structure
// via theme tokens + <app>-overrides and must NOT load menuStyles.css.
const _THEME_MENU_BASE = ['hud' => true, 'petranaki-hud' => true, 'neutral' => true];

// Emit the SHARED design-system menu stack for a site straight from its single `theme` key:
// [menuStyles if the theme uses it] → tokens → components → Themes/<theme> (only if it
// resolves). 'neutral'/unknown links no overlay (tokens.css defaults win), mirroring
// zzDesignSystemPreview. The app-specific <app>-overrides.css is NOT derived here — it stays
// in head.styles as the one genuine app extra (filenames don't follow a rootName rule), and
// RenderHead appends head.styles AFTER this stack so an override still lands last.
function _RenderThemeStack(array $def): string {
    $theme = is_string($def['theme'] ?? null) ? $def['theme'] : 'neutral';
    $docRoot = $_SERVER['DOCUMENT_ROOT'] ?? '';
    $files = [];
    if (!empty(_THEME_MENU_BASE[$theme])) $files[] = '/TCGEngine/SharedUI/css/menuStyles.css';
    $files[] = '/TCGEngine/SharedUI/css/tokens.css';
    $files[] = '/TCGEngine/SharedUI/css/components.css';
    $themePath = "/TCGEngine/SharedUI/Themes/$theme.tokens.css";
    if ($theme !== 'neutral' && $docRoot !== '' && @file_exists($docRoot . $themePath)) {
        $files[] = $themePath;
    }
    $out = '';
    foreach ($files as $f) $out .= '  <link rel="stylesheet" href="' . _VersionAsset($f) . "\">\n";
    return $out;
}

function RenderHead(array $def): string {
    $b = $def['branding']; $h = $def['head'];
    $headTitle = $b['headTitle'] ?? $b['title'];   // distinct browser-tab title; defaults to the h1 title
    $out  = "<head>  <meta charset=\"utf-8\">\n";
    $out .= "  <title>$headTitle</title>\n";
    $out .= "  <link rel=\"icon\" type=\"image/png\" href=\"{$b['favicon']}\">\n";
    // Design-system stack is derived from the SiteDef `theme` key; head.styles carries only extras.
    $styleTags = "\n" . _RenderThemeStack($def);
    foreach ($h['styles'] as $s) $styleTags .= "  <link rel=\"stylesheet\" href=\"" . _VersionAsset($s) . "\">";
    // StyledDialog loads on every SiteDef site so StyledConfirm/StyledAlert/StyledPrompt/Toast
    // are always available (self-injects its own CSS). No native alert/confirm/prompt anywhere.
    $scriptTags = "  <script src=\"" . _VersionAsset('/TCGEngine/Core/StyledDialog.js') . "\"></script>\n";
    $scriptTags .= "  <script src=\"" . _VersionAsset('/TCGEngine/Core/StyledSelect.js') . "\"></script>\n";
    foreach ($h['scripts'] as $s) $scriptTags .= "  <script src=\"" . _VersionAsset($s) . "\"></script>\n";
    // Faithful reproduction of the existing comment + spacing on the styles line:
    $out .= "  <!--<link rel=\"stylesheet\" href=\"./css/menuStyles.css\">-->$styleTags$scriptTags";
    $out .= "  <!-- <link rel=\"stylesheet\" href=\"./css/menuStyles2.css\"> -->\n";
    $out .= "  <link rel=\"preconnect\" href=\"https://fonts.googleapis.com\">\n";
    $out .= "  <link rel=\"preconnect\" href=\"https://fonts.gstatic.com\" crossorigin>\n";
    $out .= _RenderFontLinks($h['fonts']);
    $out .= "</head>\n";
    return $out;
}
