<?php
// SWU-PGN card ids (SWU-PGN/1.0 spec §6.1) → SWUSim CardIDs. Null means "SWUSim has no such card":
// the replay viewer then draws a named placeholder instead of guessing.
require_once __DIR__ . '/../../AppCore/SWU/SwuPgn/SwuPgn.php';

function SwuPgnMapCardId($pgnId): ?string
{
    if (!is_string($pgnId) || $pgnId === '') return null;
    $id = SwuPgnBaseId($pgnId);
    if (strncmp($id, 'TOKEN:', 6) === 0) return _SwuPgnMapToken($id);
    if (preg_match('/^([A-Za-z0-9]{2,5})#(\d{1,4})$/', $id, $m) !== 1) return null;
    $cardID = BuildCardID(strtoupper($m[1]), intval($m[2]));
    return IsSWUCardID($cardID) ? $cardID : null;
}

function _SwuPgnMapToken(string $id): ?string
{
    $parts = SwuPgnTokenParts($id);
    if ($parts === null) return null;
    $name = strtolower((string)($parts['name'] ?? ''));
    $reserved = ['credit' => 'LAW_T01', 'the-force' => 'LOF_T03'];
    if (isset($reserved[$name])) return $reserved[$name];
    $uuid = ltrim((string)($parts['cardId'] ?? ''), '0');
    if ($uuid !== '' && ctype_digit($uuid)) {
        $byUuid = _SwuPgnUuidIndex();
        if (isset($byUuid[$uuid])) return $byUuid[$uuid];
    }
    $byName = ['shield' => 'SOR_T02', 'experience' => 'SOR_T01', 'advantage' => 'ASH_T02', 'weakness' => 'HMW_T02'];
    return $byName[$name] ?? null;
}

// Official numeric id (leading zeros dropped) → CardID; the first printing wins.
function _SwuPgnUuidIndex(): array
{
    static $index = null;
    if ($index !== null) return $index;
    global $cardUUIDData;
    $index = [];
    foreach ((is_array($cardUUIDData) ? $cardUUIDData : []) as $cardID => $uuid) {
        $k = ltrim((string)$uuid, '0');
        if ($k !== '' && !isset($index[$k])) $index[$k] = $cardID;
    }
    return $index;
}
