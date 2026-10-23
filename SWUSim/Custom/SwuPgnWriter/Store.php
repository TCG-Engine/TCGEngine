<?php
// The SWU-PGN writer's side folder, Games/<game>/Pgn/ — never the gamestate (RNG material). Writes are atomic
// (temp file + rename); reads tolerate missing or corrupted files.

function SwuPgnRecDir(string $gameName): string {
    return dirname(__DIR__, 2) . '/Games/' . $gameName . '/Pgn';
}

function _SwuPgnWriteAtomic(string $path, string $text): void {
    if (!is_dir(dirname($path))) mkdir(dirname($path), 0777, true);
    $tmp = $path . '.tmp' . getmypid();
    file_put_contents($tmp, $text);
    rename($tmp, $path);
}

// Deletes a folder and everything in it (PHP only — no shell); a missing folder is fine.
function _SwuPgnRmTree(string $dir): void {
    if (!is_dir($dir)) return;
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST) as $f) {
        $f->isDir() ? rmdir($f->getPathname()) : unlink($f->getPathname());
    }
    rmdir($dir);
}

function _SwuPgnReadJson(string $path): ?array {
    clearstatcache(true, $path);
    if (!is_file($path)) return null;
    $d = json_decode((string)file_get_contents($path), true);
    return is_array($d) ? $d : null;
}

function SwuPgnMetaRead(string $g): ?array { return _SwuPgnReadJson(SwuPgnRecDir($g) . '/meta.json'); }
function SwuPgnMetaWrite(string $g, array $meta): void {
    _SwuPgnWriteAtomic(SwuPgnRecDir($g) . '/meta.json', json_encode($meta, JSON_UNESCAPED_SLASHES));
}

// json_decode(…, true) turns an empty JSON object into []; the one object field a record can hold empty is a keyframe
// card's statusTokens (§11), and the reader rejects a list there — so it is restored on the way back in.
function _SwuPgnRestoreObjects(array $e): array {
    if (!is_array($e['keyframe']['players'] ?? null)) return $e;
    foreach ($e['keyframe']['players'] as $s => $pl) {
        foreach ((is_array($pl['cards'] ?? null) ? $pl['cards'] : []) as $i => $c) {
            if (is_array($c) && array_key_exists('statusTokens', $c) && $c['statusTokens'] === []) $e['keyframe']['players'][$s]['cards'][$i]['statusTokens'] = new stdClass();
        }
    }
    return $e;
}

function SwuPgnEventsRead(string $g): array {
    $path = SwuPgnRecDir($g) . '/events.jsonl';
    clearstatcache(true, $path);
    if (!is_file($path)) return [];
    $out = [];
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $e = json_decode($line, true);
        if (is_array($e)) $out[] = _SwuPgnRestoreObjects($e);
    }
    return $out;
}

function SwuPgnEventsAppend(string $g, array $events): void {
    if (!$events) return;
    $path = SwuPgnRecDir($g) . '/events.jsonl';
    if (!is_dir(dirname($path))) mkdir(dirname($path), 0777, true);
    $text = '';
    foreach ($events as $e) $text .= json_encode($e, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
    file_put_contents($path, $text, FILE_APPEND | LOCK_EX);
}

function SwuPgnEventsTruncate(string $g, int $count): void {
    SwuPgnEventsWrite($g, array_slice(SwuPgnEventsRead($g), 0, max(0, $count)));
}
function SwuPgnEventsWrite(string $g, array $events): void {
    $text = '';
    foreach ($events as $e) $text .= json_encode($e, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
    _SwuPgnWriteAtomic(SwuPgnRecDir($g) . '/events.jsonl', $text);
}
// A fingerprint of a run of records, as they are stored.
function SwuPgnEventsHash(array $events): string {
    $text = '';
    foreach ($events as $e) $text .= json_encode($e, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
    return md5($text);
}

// branches.jsonl = the records each cut dropped, kept so an undo that walks back up an abandoned line (undoing a
// bookmark load) can bring them back: {base: records kept before the cut, baseHash: their fingerprint, tail: [...]}.
function SwuPgnBranchAppend(string $g, int $base, array $kept, array $tail): void {
    if (!$tail) return;
    $line = json_encode(['base' => $base, 'baseHash' => SwuPgnEventsHash($kept), 'tail' => $tail], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    file_put_contents(SwuPgnRecDir($g) . '/branches.jsonl', $line . "\n", FILE_APPEND | LOCK_EX);
}
function SwuPgnBranchesRead(string $g): array {
    $path = SwuPgnRecDir($g) . '/branches.jsonl';
    if (!is_file($path)) return [];
    $out = [];
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $l) {
        $b = json_decode($l, true);
        if (is_array($b) && is_array($b['tail'] ?? null)) { $b['tail'] = array_map('_SwuPgnRestoreObjects', $b['tail']); $out[] = $b; }
    }
    return $out;
}

// state.json = {counters, ledger, prev}: the recorder's working state between requests.
function SwuPgnStateRead(string $g): array { return _SwuPgnReadJson(SwuPgnRecDir($g) . '/state.json') ?? []; }
function SwuPgnStateWrite(string $g, array $state): void {
    _SwuPgnWriteAtomic(SwuPgnRecDir($g) . '/state.json', json_encode($state, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
}

function _SwuPgnCheckpointPath(string $g, string $key): ?string {
    return preg_match('/^[A-Za-z0-9_-]{1,64}$/', $key) === 1 ? SwuPgnRecDir($g) . "/checkpoints/$key.json" : null;
}
function SwuPgnCheckpointSave(string $g, string $key, array $state, int $eventCount, ?string $prefixHash = null): void {
    $path = _SwuPgnCheckpointPath($g, $key);
    if ($path !== null) _SwuPgnWriteAtomic($path, json_encode(['state' => $state, 'eventCount' => $eventCount, 'prefixHash' => $prefixHash],
        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
}
function SwuPgnCheckpointLoad(string $g, string $key): ?array {
    $path = _SwuPgnCheckpointPath($g, $key);
    return $path === null ? null : _SwuPgnReadJson($path);
}

// One JSON line per finished game: SWUSim/Games/swupgn-health.jsonl (gitignored; 404'd on production).
// ($GLOBALS['SWUPGN_HEALTH_FILE'] redirects it for tests.)
function SwuPgnHealthAppend(array $line): void {
    $path = (string)($GLOBALS['SWUPGN_HEALTH_FILE'] ?? (dirname(__DIR__, 2) . '/Games/swupgn-health.jsonl'));
    file_put_contents($path, json_encode($line, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND | LOCK_EX);
}
