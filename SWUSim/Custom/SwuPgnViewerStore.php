<?php
// Read-only SWU-PGN replay viewer games: create one from an uploaded file, write the board for a step,
// cleanup. Used by SWUSim/SwuPgnViewer.php. A viewer game folder holds Viewer.swupgn, Viewer.json and
// the Gamestate.txt the normal board page draws.
require_once __DIR__ . '/SwuPgnBoard.php';
require_once __DIR__ . '/SwuPgnViewerLimits.php';

const SWUPGN_VIEWER_MAX_BYTES = 2097152;
const SWUPGN_VIEWER_GAMES = __DIR__ . '/../Games';

function _SwuPgnViewerErr(int $status, string $message): array { return ['ok' => false, 'status' => $status, 'message' => $message]; }
function _SwuPgnViewerDir(string $g): string { return SWUPGN_VIEWER_GAMES . '/' . $g; }

// Deletes viewer games older than $maxAgeSeconds by scanning every game folder (slow: run through
// SwuPgnViewerSweepDue). Only folders holding Viewer.json are ever touched.
function SwuPgnViewerCleanup(string $gamesDir, int $maxAgeSeconds = 86400): int
{
    $removed = 0;
    foreach (glob(rtrim($gamesDir, '/') . '/*/Viewer.json') ?: [] as $marker) {
        if (filemtime($marker) > time() - $maxAgeSeconds) continue;
        if (SwuPgnViewerRemoveFolder(dirname($marker))) $removed++;
    }
    return $removed;
}

function _SwuPgnViewerSummary(array $doc): array
{
    $h = $doc['headers'] ?? [];
    $idx = SwuPgnCardIndex($doc);
    return ['p1' => $h['P1'] ?? 'Player 1', 'p2' => $h['P2'] ?? 'Player 2',
        'p1Leader' => SwuPgnName($idx, $h['P1Leader'] ?? ''), 'p2Leader' => SwuPgnName($idx, $h['P2Leader'] ?? ''),
        'p1Base' => SwuPgnName($idx, $h['P1Base'] ?? ''), 'p2Base' => SwuPgnName($idx, $h['P2Base'] ?? ''),
        'result' => $h['Result'] ?? '', 'reason' => $h['Reason'] ?? '', 'rounds' => $h['Rounds'] ?? '',
        'engine' => $h['Engine'] ?? '', 'date' => $h['Date'] ?? '', 'perspective' => $h['Perspective'] ?? null];
}

// The seat whose view a viewer game shows (captions hide the other seat's draws / resources); null = both.
function _SwuPgnViewerSeat(string $view): ?int { return ['p1' => 1, 'p2' => 2][$view] ?? null; }

// $who: an opaque id for the client (the per-client cap).
function SwuPgnViewerOpen(string $text, string $who = ''): array
{
    if (strlen($text) > SWUPGN_VIEWER_MAX_BYTES) return _SwuPgnViewerErr(413, 'That file is larger than 2 MB.');
    if (SwuPgnViewerSweepDue(SWUPGN_VIEWER_GAMES, time())) SwuPgnViewerCleanup(SWUPGN_VIEWER_GAMES);
    $parsed = SwuPgnTryParse($text);
    if ($parsed['doc'] === null) return _SwuPgnViewerErr(400, (string)$parsed['error']);
    $doc = $parsed['doc'];
    if (!empty($doc['dropped']['EVENTS'])) return _SwuPgnViewerErr(413, 'That file has more than 20,000 events.');
    $v = SwuPgnValidate($doc);
    if (!$v['ok']) {
        $first = array_slice($v['errors'], 0, 5);
        return _SwuPgnViewerErr(400, implode("\n", array_map(fn($e) => (!empty($e['line']) ? "line {$e['line']}: " : '') . $e['message'], $first)));
    }
    $perspective = in_array($doc['headers']['Perspective'] ?? null, ['P1', 'P2'], true) ? $doc['headers']['Perspective'] : null;
    $s = SwuPgnSteps($doc, $perspective ? intval($perspective[1]) : null);
    if (!$s['steps']) return _SwuPgnViewerErr(400, 'That file has no actions to play back.');
    global $gameName;
    $admit = SwuPgnViewerAdmit(SWUPGN_VIEWER_GAMES, $who, time(), function () use ($text): string {
        $g = (string)GetGameCounter(SWUPGN_VIEWER_GAMES);
        @mkdir(_SwuPgnViewerDir($g), 0777, true);
        file_put_contents(_SwuPgnViewerDir($g) . '/Viewer.swupgn', $text);
        file_put_contents(_SwuPgnViewerDir($g) . '/Viewer.json', '{}');   // the viewer marker, before anything else can look
        return $g;
    });
    if (!$admit['ok']) return $admit;
    $gameName = $admit['gameName'];
    $keys = SimGameDefaultAuthKeys();
    foreach (['p1', 'p2', 'p3', 'p4', 'spectator'] as $k) $keys[$k] = bin2hex(random_bytes(16));
    $keys['isPrivate'] = true;
    $keys['casterMode'] = true;     // the converter decides which cards are face up; the spectator sees them as written
    SimGameWriteAuthKeys('SWUSim', $gameName, $keys);
    InitializeCache($gameName);
    SetCachePiece($gameName, 10, '1');   // "Is Replay": the inactivity clock stays off
    $warnings = array_map(fn($w) => $w['message'], array_slice($v['warnings'], 0, 50));
    $kf = SwuPgnCheckKeyframes($doc['events']);
    if (!$kf['ok']) {
        $m = $kf['mismatches'][0];
        array_unshift($warnings, count($kf['mismatches']) . " keyframe mismatch(es) with the events; the board follows the keyframes (first: {$m['seq']} {$m['path']})");
    }
    $meta = ['summary' => _SwuPgnViewerSummary($doc), 'steps' => $s['steps'], 'rounds' => $s['rounds'],
        'perspective' => $perspective, 'view' => $perspective ? strtolower($perspective) : 'both', 'step' => 0,
        'names' => [], 'warnings' => $warnings, 'update' => 0];
    $meta = _SwuPgnViewerWriteStep($gameName, $doc, $meta, 0);
    return ['ok' => true, 'gameName' => $gameName, 'key' => $keys['spectator'], 'meta' => $meta];
}

function _SwuPgnViewerWriteStep(string $g, array $doc, array $meta, int $step): array
{
    global $gameName, $updateNumber;
    $gameName = $g;
    $step = max(0, min(count($meta['steps']) - 1, $step));
    $tl = SwuPgnBoardTimeline($doc);
    $state = SwuPgnTimelineStateAt($tl, $meta['steps'][$step]['pos']);
    InitializeGamestate();
    $res = SwuPgnBoardWrite($state, $doc, $meta['view']);
    $meta['step'] = $step;
    $meta['names'] = $res['placeholders'] + ($meta['names'] ?? []);
    $meta['update'] = intval($meta['update']) + 1;
    $updateNumber = $meta['update'];
    WriteGamestate(__DIR__ . '/../');
    GamestateUpdated($g);
    file_put_contents(_SwuPgnViewerDir($g) . '/Viewer.json', json_encode($meta, JSON_UNESCAPED_UNICODE));
    return $meta;
}

function _SwuPgnViewerLoad(string $g, string $key): array
{
    if (preg_match('/^\d+$/', $g) !== 1 || !is_file(_SwuPgnViewerDir($g) . '/Viewer.json')) return _SwuPgnViewerErr(404, 'Replay not found.');
    $keys = SimGameReadAuthKeys('SWUSim', $g);
    if (!is_array($keys) || !hash_equals((string)($keys['spectator'] ?? ''), $key)) return _SwuPgnViewerErr(403, 'Invalid key.');
    $meta = json_decode((string)file_get_contents(_SwuPgnViewerDir($g) . '/Viewer.json'), true);
    $doc = SwuPgnTryParse((string)file_get_contents(_SwuPgnViewerDir($g) . '/Viewer.swupgn'))['doc'];
    if (!is_array($meta) || $doc === null) return _SwuPgnViewerErr(404, 'Replay not found.');
    return ['ok' => true, 'meta' => $meta, 'doc' => $doc];
}

function SwuPgnViewerInfo(string $g, string $key): array
{
    $l = _SwuPgnViewerLoad($g, $key);
    return $l['ok'] ? ['ok' => true, 'meta' => $l['meta']] : $l;
}

function SwuPgnViewerSeek(string $g, string $key, int $step, ?string $view): array
{
    $l = _SwuPgnViewerLoad($g, $key);
    if (!$l['ok']) return $l;
    $meta = $l['meta'];
    if ($meta['perspective'] === null && in_array($view, ['both', 'p1', 'p2'], true) && $view !== $meta['view']) {
        $meta['view'] = $view;
        $meta['steps'] = SwuPgnSteps($l['doc'], _SwuPgnViewerSeat($view))['steps'];
        $meta['names'] = [];   // names collected under the old view may belong to cards this view hides
    }
    return ['ok' => true, 'meta' => _SwuPgnViewerWriteStep($g, $l['doc'], $meta, $step)];
}
