<?php
// Per-game multi-step undo stack (SWUSim undo redesign).
//
// Stored INSIDE the serialized gamestate via player 1's Versions zone (GetVersions(1)):
//   • WriteGamestate serializes the Versions zones, so the whole undo history rides inside Gamestate.txt
//     — and therefore inside any bug report that captures the gamestate (the manager's intake + loader
//     get the undo states for free; loading the gamestate restores them, and in-game Undo can step back
//     to last round / begin game).
//   • GetSerializedZones (the per-snapshot undo PAYLOAD) EXCLUDES the Versions zone, so snapshots never
//     recursively nest (that recursion is why the stack briefly lived in a separate file).
//   • The client render path (GetNextTurn) iterates GetVersions(1) but emits only entry separators, never
//     the payload contents, so no hidden info (opponent hands/decks inside a snapshot) leaks to a client.
// One Versions entry = one snapshot; the array index = the entry's ordinal.
// Record layout (see PushUndoSnapshot / UndoRecordBuild):
//   "{seat}\t{phase}\t{boundary}\t{revealedInfo}\t{base64(name)}\t{base64(payload)}"  (one newline-free line)

// The full undo record is stored as the entry's ->Version. A fixed "0:" DisplayNumber prefix makes the
// Versions constructor parse cleanly (the record itself contains no ':' before its first tab), and it
// round-trips through WriteGamestate/ParseGamestate as an ordinary Versions line.
function UndoStackAppend(string $line): void {
    $z = &GetVersions(1);
    $z[] = new Versions('0:' . $line, 'Versions', 1, count($z));
}

function UndoStackCount(): int {
    $z = &GetVersions(1);
    $n = 0;
    foreach ($z as $e) { if ($e !== null && empty($e->removed)) $n++; }
    return $n;
}

function UndoStackRead(int $ordinal): ?string {
    if ($ordinal < 0) return null;
    $z = &GetVersions(1);
    return (isset($z[$ordinal]) && $z[$ordinal] !== null) ? (string)$z[$ordinal]->Version : null;
}

// Overwrite the record at $ordinal (used by UndoStackSetRevealed). No-op if out of range.
function UndoStackWrite(int $ordinal, string $line): void {
    $z = &GetVersions(1);
    if (isset($z[$ordinal]) && $z[$ordinal] !== null) $z[$ordinal]->Version = $line;
}

// Keep entries 0..$ordinal (drop everything above) so the stack matches the restored state after undo.
function UndoStackTruncateTo(int $ordinal): void {
    $z = &GetVersions(1);
    if ($ordinal < 0) { $z = []; return; }
    array_splice($z, $ordinal + 1);
}

function UndoStackClear(): void {
    $z = &GetVersions(1);
    $z = [];
}

// ── Record schema ─────────────────────────────────────────────────────────────
// "{seat}\t{phase}\t{boundary}\t{revealedInfo}\t{base64(name)}\t{base64(payload)}"
// name + payload are base64 so a record is exactly one newline-free, colon-free-until-first-tab line.

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
