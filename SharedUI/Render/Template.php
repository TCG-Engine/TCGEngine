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
        '{{rootName}}' => $id['rootName'] ?? '',
        '{{appName}}'  => $id['appName'] ?? '',
        '{{ipOwner}}'  => $id['ipOwner'] ?? '',
        '{{tcgName}}'  => $id['tcgName'] ?? '',
    ];
    $out = strtr($html, $tokens);
    if (preg_match('/\{\{[a-zA-Z]+\}\}/', $out, $m)) {
        error_log("RenderTemplate($name): unreplaced token " . $m[0]);
    }
    return $out;
}
