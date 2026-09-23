<?php
// Mod tool — download SWUSim/BotData/ as one .tar.gz and record a manifest of exactly what went out.
// The manifest is what makes PURGE safe: it deletes only what a download actually took.
// Spec: docs/superpowers/specs/2026-09-23-swusim-bot-data-loop-design.md §2.
require_once __DIR__ . '/../../AccountFiles/AccountSessionAPI.php';

function SWUBotDataRoot(): string { return __DIR__ . '/../BotData'; }
function SWUBotDataManifestPath(string $root): string { return rtrim($root, '/') . '/.manifest.json'; }

// Every game directory currently present. Dotfiles are never games — in particular the manifest
// itself, which lives in the same directory and would otherwise be listed as one.
// The FINGERPRINT a purge checks before deleting a game: the size of the trajectory the bundle took.
// A game that has recorded more since — because it was still being PLAYED when the download ran — no
// longer matches, and purge leaves it alone.
function SWUBotDataFingerprint(string $root, string $game): int {
    $p = rtrim($root, '/') . '/' . $game . '/states.jsonl';
    return is_file($p) ? intval(filesize($p)) : -1;
}

function SWUBotDataBuildManifest(string $root): array {
    $root = rtrim($root, '/');
    $games = [];
    foreach (glob($root . '/*', GLOB_ONLYDIR) ?: [] as $d) {
        $b = basename($d);
        if ($b === '' || $b[0] === '.') continue;
        $games[] = $b;
    }
    sort($games);
    $m = ['createdAt' => time(), 'games' => $games, 'count' => count($games),
          // Keyed by game id. Purge deletes a game only if its trajectory is still byte-for-byte the
          // length the bundle captured — see SWUBotDataFingerprint.
          'sizes' => array_combine($games, array_map(fn($g) => SWUBotDataFingerprint($root, $g), $games)) ?: []];
    @file_put_contents(SWUBotDataManifestPath($root), json_encode($m, JSON_PRETTY_PRINT), LOCK_EX);
    return $m;
}

// Build the tarball to a temp file and return its path, or null if anything failed.
//
// ⚠ THE MANIFEST IS WRITTEN LAST, and only on success. It is what ARMS PURGE, so writing it before the
// bundle exists means a failed or aborted download leaves purge armed for games the owner never
// received — and BotData is gitignored, so there is no other copy. Building to a file first also means
// tar's exit code is checked before a single byte of a 200 response has gone out, instead of streaming
// a truncated archive under an HTTP 200.
function SWUBotDataBuildBundle(string $root): ?string {
    $root = rtrim($root, '/');
    if (!is_dir($root)) return null;

    // ⚠ ONE manifest builder, not two. This used to construct its own manifest inline while
    // SWUBotDataBuildManifest built a different one — so the 'sizes' fingerprint purge depends on
    // existed only on the path the TESTS exercised. Every real download shipped a manifest without it,
    // purge skipped every game as unverifiable and then consumed the manifest, and the owner saw
    // "purge did nothing" with nothing left to retry against.
    //
    // The manifest ships INSIDE the bundle, so an uploaded bundle is self-describing — which means it
    // has to exist on disk before tar runs. If tar then fails it is DELETED again, so it never
    // survives as an armed purge for a bundle nobody got.
    $mp = SWUBotDataManifestPath($root);
    $m = SWUBotDataBuildManifest($root);
    $games = $m['games'] ?? [];
    if (empty($games)) { @unlink($mp); return null; }

    $tmp = tempnam(sys_get_temp_dir(), 'swubotdata');
    if ($tmp === false) { @unlink($mp); return null; }
    // ⚠ -T reads the member list from a FILE. One escaped argument per game would put every id on a
    // single command line and can overrun ARG_MAX once the corpus is a few thousand games.
    $listFile = $tmp . '.list';
    @file_put_contents($listFile, implode("\n", array_merge(['.manifest.json'], $games)) . "\n");
    $out = []; $rc = 0;
    @exec('tar -czf ' . escapeshellarg($tmp) . ' -C ' . escapeshellarg($root)
          . ' -T ' . escapeshellarg($listFile) . ' 2>/dev/null', $out, $rc);
    @unlink($listFile);
    if ($rc !== 0 || !is_file($tmp) || filesize($tmp) === 0) {
        @unlink($tmp);
        @unlink($mp);   // disarm: no usable bundle was produced
        return null;
    }
    return $tmp;
}

// Only run the HTTP half when this file IS the request, so the tests can require it safely.
if (realpath(__FILE__) === realpath(strval($_SERVER['SCRIPT_FILENAME'] ?? ''))) {
    $modErr = CheckLoggedInUserMod();
    if ($modErr !== '') { http_response_code(403); echo 'Access denied'; exit; }

    $tgz = SWUBotDataBuildBundle(SWUBotDataRoot());
    if ($tgz === null) { http_response_code(404); echo 'No bot data recorded yet (or the bundle could not be built).'; exit; }

    $name = 'swusim-botdata-' . date('Ymd-His') . '.tar.gz';
    header('Content-Type: application/gzip');
    header('Content-Disposition: attachment; filename="' . $name . '"');
    header('Content-Length: ' . filesize($tgz));   // so the browser can tell a truncated download apart
    readfile($tgz);
    @unlink($tgz);
}
