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

// ── Held writes ────────────────────────────────────────────────────────────────────────────────────
// While a frame is open, every write for its game is held here and reads see it; the frame's commit writes it all. The
// runner commits only an action it keeps (it writes the gamestate), so a request that dies mid-action — after a round
// boundary, an undo point or a split recorded part of it — leaves nothing behind.
//   SWU_PGN_TX[$g] = [meta, state, events (the whole list once rewritten), appended (records added to the file's),
//                     checkpoints [key => record], branches [lines], clearOut]
function SwuPgnTxBegin(string $g): void {
    if ($g !== '' && !isset($GLOBALS['SWU_PGN_TX'][$g])) {
        $GLOBALS['SWU_PGN_TX'][$g] = ['meta' => null, 'state' => null, 'events' => null, 'appended' => [], 'checkpoints' => [], 'branches' => [], 'clearOut' => false,
            'read' => []];   // what the frame has read from disk, so it reads each file once
    }
}
function &_SwuPgnTx(string $g): ?array {
    $none = null;
    if (!isset($GLOBALS['SWU_PGN_TX'][$g])) return $none;
    return $GLOBALS['SWU_PGN_TX'][$g];
}
// Writes every game's held writes (normally the one game of the frame).
function SwuPgnTxCommit(): void {
    $all = $GLOBALS['SWU_PGN_TX'] ?? [];
    unset($GLOBALS['SWU_PGN_TX']);
    foreach ($all as $g => $tx) {
        $g = strval($g);
        if ($tx['clearOut']) _SwuPgnRmTree(SwuPgnRecDir($g) . '/out');
        if ($tx['events'] !== null) _SwuPgnEventsWriteDisk($g, array_merge($tx['events'], $tx['appended']));
        elseif ($tx['appended']) _SwuPgnEventsAppendDisk($g, $tx['appended']);
        foreach ($tx['branches'] as $line) file_put_contents(SwuPgnRecDir($g) . '/branches.jsonl', $line . "\n", FILE_APPEND | LOCK_EX);
        _SwuPgnCheckpointAppendDisk($g, $tx['checkpoints']);
        if ($tx['state'] !== null) _SwuPgnWriteAtomic(SwuPgnRecDir($g) . '/state.json', json_encode($tx['state'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        if ($tx['meta'] !== null) _SwuPgnWriteAtomic(SwuPgnRecDir($g) . '/meta.json', json_encode($tx['meta'], JSON_UNESCAPED_SLASHES));
    }
}
// The finished files are deleted with the commit (a reopened game's files go only if the reopen is kept).
function SwuPgnOutClear(string $g): void {
    $tx = &_SwuPgnTx($g);
    if ($tx !== null) { $tx['clearOut'] = true; return; }
    _SwuPgnRmTree(SwuPgnRecDir($g) . '/out');
}

function SwuPgnMetaRead(string $g): ?array {
    $tx = &_SwuPgnTx($g);
    if ($tx !== null) {
        if ($tx['meta'] !== null) return $tx['meta'];
        if (!array_key_exists('meta', $tx['read'])) $tx['read']['meta'] = _SwuPgnReadJson(SwuPgnRecDir($g) . '/meta.json');
        return $tx['read']['meta'];
    }
    return _SwuPgnReadJson(SwuPgnRecDir($g) . '/meta.json');
}
function SwuPgnMetaWrite(string $g, array $meta): void {
    $tx = &_SwuPgnTx($g);
    if ($tx !== null) { $tx['meta'] = $meta; return; }
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
    $tx = &_SwuPgnTx($g);
    if ($tx !== null) {
        if ($tx['events'] === null && !array_key_exists('events', $tx['read'])) $tx['read']['events'] = _SwuPgnEventsReadDisk($g);
        return array_merge($tx['events'] ?? $tx['read']['events'], $tx['appended']);
    }
    return _SwuPgnEventsReadDisk($g);
}
function _SwuPgnEventsReadDisk(string $g): array {
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
    $tx = &_SwuPgnTx($g);
    if ($tx !== null) { foreach ($events as $e) $tx['appended'][] = $e; return; }
    _SwuPgnEventsAppendDisk($g, $events);
}
function _SwuPgnEventsAppendDisk(string $g, array $events): void {
    $idx = _SwuPgnEventsIndexDisk($g);
    $path = SwuPgnRecDir($g) . '/events.jsonl';
    if (!is_dir(dirname($path))) mkdir(dirname($path), 0777, true);
    $text = '';
    foreach ($events as $e) $text .= json_encode($e, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
    file_put_contents($path, $text, FILE_APPEND | LOCK_EX);
    _SwuPgnEventsIndexWrite($g, [$idx[0] + count($events), SwuPgnEventsHashMore($idx[1], $events)]);
}

function SwuPgnEventsTruncate(string $g, int $count): void {
    SwuPgnEventsWrite($g, array_slice(SwuPgnEventsRead($g), 0, max(0, $count)));
}
function SwuPgnEventsWrite(string $g, array $events): void {
    $tx = &_SwuPgnTx($g);
    if ($tx !== null) { $tx['events'] = $events; $tx['appended'] = []; return; }
    _SwuPgnEventsWriteDisk($g, $events);
}
function _SwuPgnEventsWriteDisk(string $g, array $events): void {
    $text = '';
    foreach ($events as $e) $text .= json_encode($e, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
    _SwuPgnWriteAtomic(SwuPgnRecDir($g) . '/events.jsonl', $text);
    _SwuPgnEventsIndexWrite($g, [count($events), SwuPgnEventsHash($events)]);
}
// A fingerprint of a run of records, as they are stored: chained record by record, so it extends without re-reading.
function SwuPgnEventsHash(array $events): string { return SwuPgnEventsHashMore('', $events); }
function SwuPgnEventsHashMore(string $hash, array $events): string {
    foreach ($events as $e) $hash = md5($hash . json_encode($e, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    return $hash;
}

// events.idx.json = [count, fingerprint] of events.jsonl, kept by every write of it: an undo point reads this, not the file.
function SwuPgnEventsIndex(string $g): array {
    $tx = &_SwuPgnTx($g);
    if ($tx !== null) {
        if ($tx['events'] !== null) $base = [count($tx['events']), SwuPgnEventsHash($tx['events'])];
        else $base = $tx['read']['index'] ??= _SwuPgnEventsIndexDisk($g);
        return [$base[0] + count($tx['appended']), SwuPgnEventsHashMore($base[1], $tx['appended'])];
    }
    return _SwuPgnEventsIndexDisk($g);
}
function _SwuPgnEventsIndexDisk(string $g): array {
    $idx = _SwuPgnReadJson(SwuPgnRecDir($g) . '/events.idx.json');
    if (is_array($idx) && isset($idx[0], $idx[1])) return [intval($idx[0]), strval($idx[1])];
    $ev = _SwuPgnEventsReadDisk($g);   // an older recording: built once
    $idx = [count($ev), SwuPgnEventsHash($ev)];
    if (is_dir(SwuPgnRecDir($g))) _SwuPgnEventsIndexWrite($g, $idx);
    return $idx;
}
// A plain write (it is small and written every frame): a torn one reads back as no index and is rebuilt from the file.
function _SwuPgnEventsIndexWrite(string $g, array $idx): void {
    file_put_contents(SwuPgnRecDir($g) . '/events.idx.json', json_encode($idx), LOCK_EX);
}

// branches.jsonl = the records each cut dropped, kept so an undo that walks back up an abandoned line (undoing a
// bookmark load) can bring them back: {base: records kept before the cut, baseHash: their fingerprint, tail: [...]}.
function SwuPgnBranchAppend(string $g, int $base, array $kept, array $tail): void {
    if (!$tail) return;
    $line = json_encode(['base' => $base, 'baseHash' => SwuPgnEventsHash($kept), 'tail' => $tail], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    $tx = &_SwuPgnTx($g);
    if ($tx !== null) { $tx['branches'][] = $line; return; }
    file_put_contents(SwuPgnRecDir($g) . '/branches.jsonl', $line . "\n", FILE_APPEND | LOCK_EX);
}
function SwuPgnBranchesRead(string $g): array {
    $path = SwuPgnRecDir($g) . '/branches.jsonl';
    $tx = &_SwuPgnTx($g);
    $lines = array_merge(is_file($path) ? file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [], $tx['branches'] ?? []);
    $out = [];
    foreach ($lines as $l) {
        $b = json_decode($l, true);
        if (is_array($b) && is_array($b['tail'] ?? null)) { $b['tail'] = array_map('_SwuPgnRestoreObjects', $b['tail']); $out[] = $b; }
    }
    return $out;
}

// state.json = {counters, ledger, prev}: the recorder's working state between requests.
function SwuPgnStateRead(string $g): array {
    $tx = &_SwuPgnTx($g);
    if ($tx !== null) {
        if ($tx['state'] !== null) return $tx['state'];
        return $tx['read']['state'] ??= (_SwuPgnReadJson(SwuPgnRecDir($g) . '/state.json') ?? []);
    }
    return _SwuPgnReadJson(SwuPgnRecDir($g) . '/state.json') ?? [];
}
function SwuPgnStateWrite(string $g, array $state): void {
    $tx = &_SwuPgnTx($g);
    if ($tx !== null) { $tx['state'] = $state; return; }
    _SwuPgnWriteAtomic(SwuPgnRecDir($g) . '/state.json', json_encode($state, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
}

// checkpoints.jsonl = one line per save, {key, state, eventCount, prefixHash}; the last line of a key wins. (One small
// file each cost a disk block apiece; an older recording's checkpoints/<key>.json still load.) The state is saved without
// its board snapshot — the restore takes the board it restores.
function _SwuPgnCheckpointKeyOk(string $key): bool { return preg_match('/^[A-Za-z0-9_-]{1,64}$/', $key) === 1; }
function SwuPgnCheckpointSave(string $g, string $key, array $state, int $eventCount, ?string $prefixHash = null): void {
    if (!_SwuPgnCheckpointKeyOk($key)) return;
    unset($state['prev']);
    $line = json_encode(['key' => $key, 'state' => $state, 'eventCount' => $eventCount, 'prefixHash' => $prefixHash], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    $tx = &_SwuPgnTx($g);
    if ($tx !== null) { $tx['checkpoints'][] = $line; return; }
    _SwuPgnCheckpointAppendDisk($g, [$line]);
}
function _SwuPgnCheckpointAppendDisk(string $g, array $lines): void {
    if (!$lines) return;
    if (!is_dir(SwuPgnRecDir($g))) mkdir(SwuPgnRecDir($g), 0777, true);
    file_put_contents(SwuPgnRecDir($g) . '/checkpoints.jsonl', implode("\n", $lines) . "\n", FILE_APPEND | LOCK_EX);
}
function SwuPgnCheckpointLoad(string $g, string $key): ?array {
    if (!_SwuPgnCheckpointKeyOk($key)) return null;
    $path = SwuPgnRecDir($g) . '/checkpoints.jsonl';
    $tx = &_SwuPgnTx($g);
    $lines = array_merge(is_file($path) ? file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [], $tx['checkpoints'] ?? []);
    for ($i = count($lines) - 1; $i >= 0; $i--) {
        if (!str_contains($lines[$i], '"key":"' . $key . '"')) continue;
        $r = json_decode($lines[$i], true);
        if (is_array($r) && ($r['key'] ?? null) === $key) { unset($r['key']); return $r; }
    }
    return _SwuPgnReadJson(SwuPgnRecDir($g) . "/checkpoints/$key.json");
}

// One JSON line per finished game: SWUSim/Games/swupgn-health.jsonl (gitignored; 404'd on production).
// ($GLOBALS['SWUPGN_HEALTH_FILE'] redirects it for tests.)
function SwuPgnHealthAppend(array $line): void {
    $path = (string)($GLOBALS['SWUPGN_HEALTH_FILE'] ?? (dirname(__DIR__, 2) . '/Games/swupgn-health.jsonl'));
    file_put_contents($path, json_encode($line, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND | LOCK_EX);
}
