<?php
// Gamestate bookmarks + the undo cursor — the SIDECAR to UndoStack.php.
//
// Backed by player 2's Versions zone (GetVersions(2)), for the same reasons UndoStack.php uses
// player 1's:
//   • WriteGamestate serializes the Versions zones, so bookmarks ride inside Gamestate.txt and are
//     captured by any bug report; loading that gamestate restores them.
//   • GetSerializedZones (the snapshot PAYLOAD) excludes the Versions zone, so a bookmark payload can
//     never contain another bookmark — no recursive nesting.
//   • GetNextTurn renders every seat's Versions as bare separators, never payload content, so no
//     hidden information reaches a client.
// The zone is a STORAGE SLOT, not player-owned data — SWUSim uses no Versions zone for gameplay — so
// this is equally valid in Twin Suns, where seat 2 is a real player.
//
// Layout:  index 0 = metadata header ("cursor=<n>")   index 1..n = bookmark records
// A bookmark's ID is its RAW ARRAY INDEX, so IDs start at 1 and index 0 is permanently reserved.
// Bookmarks are never deleted or reordered, so an ID is stable for the life of the game.
//
// Record layout (one newline-free line, mirroring UndoRecordBuild):
//   "{seat}\t{round}\t{phase}\t{cursorAt}\t{base64(label)}\t{encoded(payload)}"

if (!defined('SWU_BOOKMARK_MAX')) define('SWU_BOOKMARK_MAX', 30);

// The fixed "0:" DisplayNumber prefix makes the Versions constructor parse cleanly, exactly as
// UndoStackAppend does — the record itself contains no ':' before its first tab.
function _SWUBookmarkEnsureHeader(): void {
    $z = &GetVersions(2);
    if (!isset($z[0]) || $z[0] === null) {
        $z[0] = new Versions('0:cursor=-1', 'Versions', 2, 0);
    }
}

function UndoCursorGet(): int {
    _SWUBookmarkEnsureHeader();
    $z = &GetVersions(2);
    $v = (string)$z[0]->Version;
    return (strncmp($v, 'cursor=', 7) === 0) ? intval(substr($v, 7)) : -1;
}

function UndoCursorSet(int $c): void {
    _SWUBookmarkEnsureHeader();
    $z = &GetVersions(2);
    $z[0]->Version = 'cursor=' . $c;
}

function BookmarkStoreClear(): void {
    $z = &GetVersions(2);
    $z = [];
    _SWUBookmarkEnsureHeader();
}

function BookmarkCount(): int {
    $z = &GetVersions(2);
    $n = 0;
    for ($i = 1; $i < count($z); $i++) { if (isset($z[$i]) && $z[$i] !== null && empty($z[$i]->removed)) $n++; }
    return $n;
}

// Returns the new bookmark's ID, or -1 if the store is at SWU_BOOKMARK_MAX.
function BookmarkAppend(int $seat, int $round, string $phase, int $cursorAt, string $label, string $payload): int {
    if (BookmarkCount() >= SWU_BOOKMARK_MAX) return -1;
    _SWUBookmarkEnsureHeader();
    $z = &GetVersions(2);
    $line = implode("\t", [$seat, $round, $phase, $cursorAt, base64_encode($label), UndoPayloadEncode($payload)]);
    $id = count($z);
    $z[$id] = new Versions('0:' . $line, 'Versions', 2, $id);
    return $id;
}

function BookmarkRead(int $id): ?array {
    if ($id < 1) return null;                       // index 0 is the header
    $z = &GetVersions(2);
    if (!isset($z[$id]) || $z[$id] === null || !empty($z[$id]->removed)) return null;
    $f = explode("\t", (string)$z[$id]->Version, 6);
    if (count($f) < 6) return null;
    return [
        'seat'     => intval($f[0]),
        'round'    => intval($f[1]),
        'phase'    => (string)$f[2],
        'cursorAt' => intval($f[3]),
        'label'    => (string)base64_decode($f[4], true),
        'payload'  => UndoPayloadDecode($f[5]),
    ];
}

// [id => record without 'payload'] — this feeds the JSON endpoint, and a payload must never reach a
// client (it is the full serialized gamestate, including both players' hidden zones).
function BookmarkList(): array {
    $out = [];
    $z = &GetVersions(2);
    for ($i = 1; $i < count($z); $i++) {
        $r = BookmarkRead($i);
        if ($r === null) continue;
        unset($r['payload']);
        $out[$i] = $r;
    }
    return $out;
}
