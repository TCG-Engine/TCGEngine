<?php
// Limits for SWU-PGN replay viewer games, which anyone (guests included) may create: an index of live
// viewer games caps how many one client and everyone together may hold, and expires them after a day.
// Engine-free; used by SwuPgnViewerStore.php.

const SWUPGN_VIEWER_INDEX_FILE = 'SwuPgnViewers.json';
const SWUPGN_VIEWER_SWEEP_FILE = 'SwuPgnViewers.sweep';
const SWUPGN_VIEWER_MAX_AGE = 86400;
const SWUPGN_VIEWER_MAX_LIVE = 300;           // all clients together (each game holds up to ~3 MB on disk)
const SWUPGN_VIEWER_MAX_PER_CLIENT = 60;      // per client, within the window below
const SWUPGN_VIEWER_CLIENT_WINDOW = 600;

// Deletes one viewer game folder. Only a folder holding Viewer.json is ever touched.
function SwuPgnViewerRemoveFolder(string $dir): bool
{
    if (!is_file($dir . '/Viewer.json')) return false;
    foreach (glob($dir . '/{,.}*', GLOB_BRACE) ?: [] as $f) if (is_file($f)) @unlink($f);
    return @rmdir($dir);
}

// Under one lock on the index: drop expired games (deleting their folders), refuse when $who or everyone
// is over the cap, else $create() the game and register it. Returns ['ok' => true, 'gameName'] or
// ['ok' => false, 'status', 'message'].
function SwuPgnViewerAdmit(string $gamesDir, string $who, int $now, callable $create): array
{
    $fh = fopen(rtrim($gamesDir, '/') . '/' . SWUPGN_VIEWER_INDEX_FILE, 'c+');
    if ($fh === false) return ['ok' => false, 'status' => 500, 'message' => 'The replay viewer is unavailable right now.'];
    flock($fh, LOCK_EX);
    try {
        $index = json_decode((string)stream_get_contents($fh), true);
        $games = is_array($index['games'] ?? null) ? $index['games'] : [];
        $recent = 0;
        foreach ($games as $g => $e) {
            $t = intval($e['t'] ?? 0);
            $dir = rtrim($gamesDir, '/') . '/' . $g;
            if ($t <= $now - SWUPGN_VIEWER_MAX_AGE || !is_file($dir . '/Viewer.json')) {
                if ($t <= $now - SWUPGN_VIEWER_MAX_AGE) SwuPgnViewerRemoveFolder($dir);
                unset($games[$g]);
                continue;
            }
            if (($e['who'] ?? '') === $who && $t > $now - SWUPGN_VIEWER_CLIENT_WINDOW) $recent++;
        }
        if ($recent >= SWUPGN_VIEWER_MAX_PER_CLIENT) $r = ['ok' => false, 'status' => 429, 'message' => 'Too many replays opened recently. Try again in a few minutes.'];
        elseif (count($games) >= SWUPGN_VIEWER_MAX_LIVE) $r = ['ok' => false, 'status' => 503, 'message' => 'Too many replays are open right now. Try again later.'];
        else {
            $g = (string)$create();
            $games[$g] = ['t' => $now, 'who' => $who];
            $r = ['ok' => true, 'gameName' => $g];
        }
        ftruncate($fh, 0); rewind($fh);
        fwrite($fh, json_encode(['games' => (object)$games]));
        fflush($fh);
        return $r;
    } finally {
        flock($fh, LOCK_UN);
        fclose($fh);
    }
}

// True (and marks it run) when the full folder sweep is due: at most once a day, for viewer folders the
// index never knew (older versions, a lost index).
function SwuPgnViewerSweepDue(string $gamesDir, int $now): bool
{
    $marker = rtrim($gamesDir, '/') . '/' . SWUPGN_VIEWER_SWEEP_FILE;
    clearstatcache(true, $marker);
    if (is_file($marker) && filemtime($marker) > $now - SWUPGN_VIEWER_MAX_AGE) return false;
    touch($marker, $now);
    return true;
}
