<?php
// Token-substitution renderer for static/branded pages (legal, disclaimer).
// Templates live in Render/templates/<name>.tmpl and use {{token}} placeholders
// filled from the SiteDef identity and legal blocks.

function RenderTemplate(string $name, array $def): string {
    $path = __DIR__ . '/templates/' . $name . '.tmpl';
    if (!is_file($path)) {
        throw new RuntimeException("Missing template: $path");
    }
    $html = file_get_contents($path);
    $id = $def['identity'] ?? [];
    $legal = $def['legal'] ?? [];
    $tokens = [
        '{{rootName}}'       => $id['rootName'] ?? '',
        '{{appName}}'        => $id['appName'] ?? '',
        '{{ipOwner}}'        => $id['ipOwner'] ?? '',
        '{{assetOwner}}'     => $id['assetOwner'] ?? ($id['ipOwner'] ?? ''),
        '{{tcgName}}'        => $id['tcgName'] ?? '',
        '{{termsHref}}'      => htmlspecialchars($legal['termsHref'] ?? '/TCGEngine/SharedUI/TermsOfUse.php', ENT_QUOTES, 'UTF-8'),
        '{{privacyHref}}'    => htmlspecialchars($legal['privacyHref'] ?? '/TCGEngine/SharedUI/PrivacyPolicy.php', ENT_QUOTES, 'UTF-8'),
        '{{disclaimerLead}}' => $id['disclaimerLead']
            ?? (($id['appName'] ?? '') . ' is in no way affiliated with ' . ($id['ipOwner'] ?? '') . '.'),
    ];
    $out = strtr($html, $tokens);
    if (preg_match('/\{\{[a-zA-Z]+\}\}/', $out, $m)) {
        error_log("RenderTemplate($name): unreplaced token " . $m[0]);
    }
    return $out;
}

// Terms of Use and Privacy Policy render templates/*.tmpl, which are BARE PROSE -- <h1>, <h2>, <p>,
// <ul> with nothing around them. Every site is expected to provide the frame, and SWUSim did not:
// the prose landed as direct children of <body>, so each line ran the full width of the viewport
// with no gutter and no panel.
//
// Sites can provide their own legal content or opt into the arena wrapper
// for the shared templates.
//
// The wrapper reuses `.row-wrapper` + `.card.ga-glass-card` rather than inventing classes, so the
// redesign's existing rules (the 1368px measure, the chamfered interior panel) already apply to it
// -- the recipes are MAPPED, never re-implemented.
function RenderLegalPage(array $def, string $template): string {
    $path = $def['legal']['templates'][$template] ?? null;
    if ($path !== null) {
        if (!is_file($path)) {
            throw new RuntimeException("Missing legal template: $path");
        }
        return '<main class="site-legal-page">' . file_get_contents($path) . '</main>';
    }

    $html = RenderTemplate($template, $def);
    if (($def['legal']['layout'] ?? '') !== 'arena') return $html;
    // ⚠ The INNER wrapper is load-bearing. PrivacyPolicy.tmpl is not paragraphs -- it is bare text
    // nodes separated by <br><br> -- so a reading measure applied to <p> reaches none of its body
    // copy. Measuring the CONTAINER bounds loose text too, whatever markup a template happens to
    // use, while the panel itself keeps its full width.
    return '<div class="row-wrapper">'
         . '<section class="card ga-glass-card legal-page">'
         .   '<div class="legal-page__prose">' . $html . '</div>'
         . '</section>'
         . '</div>';
}
