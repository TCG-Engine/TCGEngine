<?php
// Mod tool — delete the bot data a download has already taken. The DESTRUCTIVE half, so it is
// deliberately conservative: it deletes only game ids named in the LAST manifest.
//
// Consequences, all intended:
//   · a game started after the download survives (it is not in the manifest);
//   · a game STILL BEING PLAYED survives even though it IS in the manifest — its trajectory has grown
//     since the bundle was built, so the fingerprint no longer matches and purge leaves it alone.
//     Without that check, downloading mid-session deleted the live game out from under the player;
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
    $sizes = is_array($m['sizes'] ?? null) ? $m['sizes'] : [];
    $deleted = $skipped = 0;
    foreach ($games as $g) {
        // Never let a manifest entry escape the root: strip everything that is not an id character,
        // so "../victim" can only ever resolve to "victim" inside the root.
        $g = preg_replace('/[^A-Za-z0-9_]/', '', strval($g));
        if ($g === '') continue;
        $dir = $root . '/' . $g;
        if (!is_dir($dir)) continue;
        // ⚠ Only delete what the bundle ACTUALLY TOOK. A game that has recorded more since the
        // download was still being played when it ran, and the owner's bundle holds only its first
        // half — deleting it now would destroy the rest of a live game. A manifest with no 'sizes'
        // predates this check; treat it as unverifiable and skip rather than delete on faith.
        if (!array_key_exists($g, $sizes) || SWUBotDataFingerprint($root, $g) !== intval($sizes[$g])) {
            $skipped++;
            continue;
        }
        foreach (glob($dir . '/*') ?: [] as $f) @unlink($f);
        if (@rmdir($dir)) $deleted++;
    }
    @unlink($mp);   // the manifest is spent; a second purge has nothing to act on
    return ['deleted' => $deleted, 'skipped' => $skipped,
            'kept' => count(glob($root . '/*', GLOB_ONLYDIR) ?: [])];
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
    // ⚠ Say what happened IN WORDS. This returned raw JSON, so a purge that deleted nothing because
    // every game failed its fingerprint check looked identical to one that had nothing to do — the
    // owner reported "purge didn't work" with no way to tell which. A skip is not a no-op, and the
    // reason matters.
    $del = intval($r['deleted'] ?? 0);
    $skip = intval($r['skipped'] ?? 0);
    $kept = intval($r['kept'] ?? 0);
    $msg = $r['message'] ?? '';
    echo '<!DOCTYPE html><meta charset="utf-8"><body style="font-family:system-ui;padding:32px">';
    echo '<h2>Purge bot data</h2>';
    if ($msg !== '') {
        echo '<p><strong>' . htmlspecialchars($msg, ENT_QUOTES) . '</strong></p>';
    } else {
        echo "<p>Deleted <strong>$del</strong> game" . ($del === 1 ? '' : 's') . '.</p>';
        if ($skip > 0) {
            echo "<p><strong>Skipped $skip</strong> game" . ($skip === 1 ? '' : 's')
               . ' — their recorded trajectory no longer matches what the download took, so they were'
               . ' left alone. That normally means they were still being played when you downloaded.'
               . ' Download again and they will be purgeable.</p>';
        }
        echo "<p>$kept game" . ($kept === 1 ? '' : 's') . ' still on disk.</p>';
    }
    echo '<p><a href="/TCGEngine/SWUSim/Mod/index.php">&larr; Mod tools</a></p></body>';
}
