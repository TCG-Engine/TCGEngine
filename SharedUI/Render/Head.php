<?php
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

function RenderHead(array $def): string {
    $b = $def['branding']; $h = $def['head'];
    $headTitle = $b['headTitle'] ?? $b['title'];   // distinct browser-tab title; defaults to the h1 title
    $out  = "<head>  <meta charset=\"utf-8\">\n";
    $out .= "  <title>$headTitle</title>\n";
    $out .= "  <link rel=\"icon\" type=\"image/png\" href=\"{$b['favicon']}\">\n";
    $styleTags = '';
    foreach ($h['styles'] as $s) $styleTags .= "  <link rel=\"stylesheet\" href=\"$s\">";
    $scriptTags = '';
    foreach ($h['scripts'] as $s) $scriptTags .= "  <script src=\"$s\"></script>\n";
    // Faithful reproduction of the existing comment + spacing on the styles line:
    $out .= "  <!--<link rel=\"stylesheet\" href=\"./css/menuStyles.css\">-->$styleTags$scriptTags";
    $out .= "  <!-- <link rel=\"stylesheet\" href=\"./css/menuStyles2.css\"> -->\n";
    $out .= "  <link rel=\"preconnect\" href=\"https://fonts.googleapis.com\">\n";
    $out .= "  <link rel=\"preconnect\" href=\"https://fonts.gstatic.com\" crossorigin>\n";
    $out .= _RenderFontLinks($h['fonts']);
    $out .= "</head>\n";
    return $out;
}
