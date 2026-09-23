<?php
// Mod tool — delete the bot data a download has already taken. The DESTRUCTIVE half, so it is
// deliberately conservative: it deletes only game ids named in the LAST manifest.
//
// Consequences, all intended:
//   · a game started after the download survives;
//   · a game still in progress survives;
//   · purge before any download is a NO-OP, not a wipe;
//   · purge twice is a no-op the second time;
//   · an interrupted download leaves nothing purgeable.
// Spec: docs/superpowers/specs/2026-09-23-swusim-bot-data-loop-design.md §2.
require_once __DIR__ . '/BotDataBundle.php';

function SWUBotDataPurge(string $root): array {
    $root = rtrim($root, '/');
    $mp = SWUBotDataManifestPath($root);
    if (!is_file($mp)) {
        return ['deleted' => 0, 'kept' => count(glob($root . '/*', GLOB_ONLYDIR) ?: []),
                'message' => 'No manifest — download a bundle first. Nothing was deleted.'];
    }
    $m = json_decode(strval(@file_get_contents($mp)), true);
    $games = is_array($m['games'] ?? null) ? $m['games'] : [];
    $deleted = 0;
    foreach ($games as $g) {
        // Never let a manifest entry escape the root: strip everything that is not an id character,
        // so "../victim" can only ever resolve to "victim" inside the root.
        $g = preg_replace('/[^A-Za-z0-9_]/', '', strval($g));
        if ($g === '') continue;
        $dir = $root . '/' . $g;
        if (!is_dir($dir)) continue;
        foreach (glob($dir . '/*') ?: [] as $f) @unlink($f);
        if (@rmdir($dir)) $deleted++;
    }
    @unlink($mp);   // the manifest is spent; a second purge has nothing to act on
    return ['deleted' => $deleted, 'kept' => count(glob($root . '/*', GLOB_ONLYDIR) ?: [])];
}

if (realpath(__FILE__) === realpath(strval($_SERVER['SCRIPT_FILENAME'] ?? ''))) {
    $modErr = CheckLoggedInUserMod();
    if ($modErr !== '') { http_response_code(403); echo 'Access denied'; exit; }
    if (($_POST['confirm'] ?? '') !== 'yes') {
        echo '<!DOCTYPE html><meta charset="utf-8"><body style="font-family:system-ui;padding:32px">'
           . '<h2>Purge bot data</h2>'
           . '<p>This deletes the games the <strong>last download</strong> took, and nothing else. '
           . 'Games started since that download are kept.</p>'
           . '<form method="post"><input type="hidden" name="confirm" value="yes">'
           . '<button type="submit">Purge downloaded bot data</button></form></body>';
        exit;
    }
    $r = SWUBotDataPurge(SWUBotDataRoot());
    header('Content-Type: application/json');
    echo json_encode($r);
}
