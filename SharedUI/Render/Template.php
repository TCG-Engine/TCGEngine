<?php
// Token-substitution renderer for static/branded pages (legal, disclaimer).
// Templates live in Render/templates/<name>.tmpl and use {{token}} placeholders
// filled from the SiteDef 'identity' block. Pure substitution — no logic.

function RenderTemplate(string $name, array $def): string {
    $path = __DIR__ . '/templates/' . $name . '.tmpl';
    if (!is_file($path)) {
        throw new RuntimeException("Missing template: $path");
    }
    $html = file_get_contents($path);
    $id = $def['identity'] ?? [];
    $tokens = [
        '{{rootName}}'       => $id['rootName'] ?? '',
        '{{appName}}'        => $id['appName'] ?? '',
        '{{ipOwner}}'        => $id['ipOwner'] ?? '',
        '{{assetOwner}}'     => $id['assetOwner'] ?? ($id['ipOwner'] ?? ''),
        '{{tcgName}}'        => $id['tcgName'] ?? '',
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
// OPT-IN, because those templates are shared with FaBSim, HellbreakSim, SWUDeck and HellbreakDeck:
// a site that does not set legal.layout = 'arena' gets byte-identical output to before. Same shape
// as the auth.layout / profile.layout opt-ins.
//
// The wrapper reuses `.row-wrapper` + `.card.ga-glass-card` rather than inventing classes, so the
// redesign's existing rules (the 1368px measure, the chamfered interior panel) already apply to it
// -- the recipes are MAPPED, never re-implemented.
function RenderLegalPage(array $def, string $template): string {
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
