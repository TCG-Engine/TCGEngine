<?php
function RenderHeader(array $def): string {
    $b = $def['branding'];
    $out  = "<div class=\"home-header\">\n";
    $out .= "    <a href=\"{$b['homeHref']}\" class=\"title" . (!empty($b['logo']) ? ' has-logo' : '') . "\">\n";
    // Optional emblem (branding.logo). Decorative — the h1 next to it already names the site — so alt="" and
    // aria-hidden. Sites without the key render exactly as before.
    if (!empty($b['logo'])) {
        $out .= "        <img class=\"title-logo\" src=\"" . htmlspecialchars($b['logo'], ENT_QUOTES) . "\" alt=\"\" aria-hidden=\"true\">\n";
    }
    $out .= "        <h1>{$b['title']}</h1>\n";
    $out .= "        <p>{$b['tagline']}</p>\n";
    $out .= "    </a>\n\n";
    if (!empty($b['showBanner'])) {
        $out .= "    <div class=\"home-banner\">\n";
        for ($i = 1; $i <= 4; $i++) {
            $out .= "        <div class=\"banner block-$i\"></div>\n";
        }
        $out .= "    </div>\n";
    }
    $out .= "</div>";
    return $out;
}
