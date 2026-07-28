<?php
// Per-game append-only undo stack (SWUSim undo redesign).
//
// Stored OUTSIDE the live Gamestate.txt (in Games/<gameName>/UndoStack.txt) so the per-action hot
// path stays lean under whole-game / unlimited retention — the growing history is only read when an
// undo actually happens. One line = one snapshot entry; the LINE INDEX is the entry's ordinal.
// Record layout (see PushUndoSnapshot): "{seat}\t{phase}\t{boundary}\t{revealedInfo}\t{base64(payload)}".
// The payload is base64, so every record is exactly one newline-free line.

function UndoStackPath(): string {
    // Production: co-located with the game's other files. Tests set $GLOBALS['SWU_UNDO_DIR'] to a fast
    // container-local dir so the per-action append doesn't cross the slow macOS->container bind mount
    // (~3x whole-suite slowdown otherwise). Prod never sets it, so behavior there is unchanged.
    $base = $GLOBALS['SWU_UNDO_DIR'] ?? ('./Games/' . ($GLOBALS['gameName'] ?? ''));
    return rtrim($base, '/') . '/UndoStack.txt';
}

function UndoStackCount(): int {
    $p = UndoStackPath();
    if (!is_file($p)) return 0;
    $lines = file($p, FILE_IGNORE_NEW_LINES);
    return $lines === false ? 0 : count($lines);
}

// Append one entry line — O(1) (no whole-file count). The caller owns the ordinal (PushUndoSnapshot
// derives it from UNDO_TOP), so appends stay cheap even when the whole-game stack is large. Ensures the
// game dir exists first.
function UndoStackAppend(string $line): void {
    $p = UndoStackPath();
    $dir = dirname($p);
    if (!is_dir($dir)) @mkdir($dir, 0777, true);
    file_put_contents($p, $line . "\n", FILE_APPEND);
}

function UndoStackRead(int $ordinal): ?string {
    if ($ordinal < 0) return null;
    $p = UndoStackPath();
    if (!is_file($p)) return null;
    $lines = file($p, FILE_IGNORE_NEW_LINES);
    if ($lines === false || $ordinal >= count($lines)) return null;
    return $lines[$ordinal];
}

// Overwrite the record at $ordinal (used by UndoStackSetRevealed). No-op if out of range.
function UndoStackWrite(int $ordinal, string $line): void {
    $p = UndoStackPath();
    if (!is_file($p)) return;
    $lines = file($p, FILE_IGNORE_NEW_LINES);
    if ($lines === false || $ordinal < 0 || $ordinal >= count($lines)) return;
    $lines[$ordinal] = $line;
    file_put_contents($p, implode("\n", $lines) . "\n");
}

// Keep entries 0..$ordinal (drop everything above) so the file matches the restored state after undo.
function UndoStackTruncateTo(int $ordinal): void {
    $p = UndoStackPath();
    if (!is_file($p)) return;
    $lines = file($p, FILE_IGNORE_NEW_LINES);
    if ($lines === false) return;
    $kept = array_slice($lines, 0, max(0, $ordinal + 1));
    file_put_contents($p, $kept ? implode("\n", $kept) . "\n" : '');
}

function UndoStackClear(): void {
    $p = UndoStackPath();
    if (is_file($p)) @unlink($p);
}

// ── Record schema ─────────────────────────────────────────────────────────────
// "{seat}\t{phase}\t{boundary}\t{revealedInfo}\t{base64(name)}\t{base64(payload)}"
// name + payload are base64 so a record is exactly one newline/tab-free-in-the-payload line.

function UndoRecordBuild(int $seat, string $phase, string $boundary, int $revealed, string $name, string $payload): string {
    return implode("\t", [$seat, $phase, $boundary, $revealed ? '1' : '0', base64_encode($name), base64_encode($payload)]);
}

function UndoRecordParse(string $line): array {
    $f = explode("\t", $line, 6);
    return [
        'seat'     => intval($f[0] ?? 0),
        'phase'    => (string)($f[1] ?? ''),
        'boundary' => (string)($f[2] ?? 'action'),
        'revealed' => ($f[3] ?? '0') === '1',
        'name'     => base64_decode($f[4] ?? ''),
        'payload'  => base64_decode($f[5] ?? ''),
    ];
}

// Stamp revealedInfo=1 on the entry at $ordinal (used by MarkUndoRequiresConsent).
function UndoStackSetRevealed(int $ordinal): void {
    $line = UndoStackRead($ordinal);
    if ($line === null) return;
    $r = UndoRecordParse($line);
    UndoStackWrite($ordinal, UndoRecordBuild($r['seat'], $r['phase'], $r['boundary'], 1, $r['name'], $r['payload']));
}
