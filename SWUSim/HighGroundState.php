<?php
// High Ground rooms — the pure state (docs/superpowers/specs/2026-10-30-swusim-high-ground-design.md).
// One private room, many people, one match at a time: winner stays on the hill, the loser goes to the back of the line.
// Every function here takes the state array and returns the new one (null = refused, state unchanged). No I/O.
//
// Members are identified by their lobby Player's authKey. That key never leaves the server: the poll view
// (SWUHighGroundPollView, HighGround.php) maps it to the member's lobby playerID before anything reaches a browser.
//
//   hill      0-2 keys. [] before the first match, [winner] after a decided one, [a,b] after a drawn one or while live.
//   line      the ordered queue (join order, host-reorderable). The next pair is the hill topped up from its front.
//   bleachers people who only watch and are never called up.
//   live      null, or ['matchId'=>…, 'keys'=>[1=>seat-1 key, 2=>seat-2 key]] while a hill match runs.
//   names     key => fixed display name (username, or "Guest N" — N never reused, so a row never renames).
//   tonight   rowKey => row; rowKey = player + deck link. Counted in GAMES. recorded = gameName set (idempotence).

const HG_MAX_MEMBERS = 16;

function HGNew(): array {
    return ['hill' => [], 'line' => [], 'bleachers' => [], 'names' => [], 'userIds' => [], 'guestNo' => 0,
            'live' => null, 'drawn' => false, 'tonight' => [], 'rowSeq' => 0, 'recorded' => [], 'watchKey' => ''];
}

function HGIsMember(array $hg, string $key): bool {
    return isset($hg['names'][$key]);
}

function _HGWithout(array $list, string $key): array {
    return array_values(array_filter($list, fn($k) => $k !== $key));
}

function _HGIsLive(array $hg, string $key): bool {
    return is_array($hg['live']) && in_array($key, $hg['live']['keys'], true);
}

function HGAddMember(array $hg, string $key, ?string $username, int $userId = 0): array {
    if (HGIsMember($hg, $key)) return $hg;
    $name = trim((string)$username);
    if ($name === '') $name = 'Guest ' . (++$hg['guestNo']);
    $hg['names'][$key] = $name;
    $hg['userIds'][$key] = $userId;
    $hg['line'][] = $key;
    return $hg;
}

// Leaves the room. A live match is untouched: its result is applied when it ends (the leaver simply isn't re-queued).
function HGRemoveMember(array $hg, string $key): array {
    $hg['hill'] = _HGWithout($hg['hill'], $key);
    $hg['line'] = _HGWithout($hg['line'], $key);
    $hg['bleachers'] = _HGWithout($hg['bleachers'], $key);
    unset($hg['names'][$key], $hg['userIds'][$key]);
    return $hg;
}

function HGToBleachers(array $hg, string $key): ?array {
    if (_HGIsLive($hg, $key)) return null;
    if (!in_array($key, $hg['line'], true) && !in_array($key, $hg['hill'], true)) return null;
    $hg['hill'] = _HGWithout($hg['hill'], $key);
    $hg['line'] = _HGWithout($hg['line'], $key);
    $hg['bleachers'][] = $key;
    return $hg;
}

function HGJoinLine(array $hg, string $key): ?array {
    if (!in_array($key, $hg['bleachers'], true)) return null;
    $hg['bleachers'] = _HGWithout($hg['bleachers'], $key);
    $hg['line'][] = $key;
    return $hg;
}

// The host's new order must be exactly the current line (no one missing, added or repeated) — a stale page is refused.
function HGReorderLine(array $hg, array $keys): ?array {
    $keys = array_values(array_map('strval', $keys));
    if (count($keys) !== count(array_unique($keys))) return null;
    $a = $keys; $b = $hg['line'];
    sort($a); sort($b);
    if ($a !== $b) return null;
    $hg['line'] = $keys;
    return $hg;
}

// The host sends someone on the hill (between matches) or in the bleachers to the back of the line — the spec's "moves them
// down the line" for a hill-holder who walked away, which a line reorder can't reach.
function HGSendToBack(array $hg, string $key): ?array {
    if (_HGIsLive($hg, $key)) return null;
    if (!in_array($key, $hg['hill'], true) && !in_array($key, $hg['bleachers'], true) && !in_array($key, $hg['line'], true)) return null;
    $hg['hill'] = _HGWithout($hg['hill'], $key);
    $hg['bleachers'] = _HGWithout($hg['bleachers'], $key);
    $hg['line'] = _HGWithout($hg['line'], $key);
    $hg['line'][] = $key;
    if (count($hg['hill']) < 2) $hg['drawn'] = false;
    return $hg;
}

// Only after a drawn match: one of the two on the hill goes to the back of the line.
function HGStepDown(array $hg, string $key): ?array {
    if ($hg['live'] !== null || count($hg['hill']) !== 2 || !in_array($key, $hg['hill'], true)) return null;
    $hg['hill'] = _HGWithout($hg['hill'], $key);
    $hg['line'][] = $key;
    $hg['drawn'] = false;
    return $hg;
}

function HGNextPair(array $hg): array {
    $pair = array_slice($hg['hill'], 0, 2);
    foreach ($hg['line'] as $k) {
        if (count($pair) >= 2) break;
        if (!in_array($k, $pair, true)) $pair[] = $k;
    }
    return $pair;
}

// $claimed: the pair Start saw. The match is built OUTSIDE the room lock, so by the time it commits someone may have
// stepped down, gone to the bleachers or been moved — then the commit is refused rather than recording another pair.
function HGBeginMatch(array $hg, string $matchId, ?array $claimed = null): ?array {
    $pair = HGNextPair($hg);
    if (count($pair) < 2 || $hg['live'] !== null) return null;
    if ($claimed !== null && array_values($claimed) !== $pair) return null;
    foreach ($pair as $k) $hg['line'] = _HGWithout($hg['line'], $k);
    $hg['hill'] = $pair;
    $hg['live'] = ['matchId' => $matchId, 'keys' => [1 => $pair[0], 2 => $pair[1]]];
    $hg['drawn'] = false;
    return $hg;
}

// $winnerSeat 1|2 = the match winner; 0 = a drawn match (both stay; either may step down).
function HGEndMatch(array $hg, int $winnerSeat): array {
    if (!is_array($hg['live'])) return $hg;
    $keys = $hg['live']['keys'];
    $hg['live'] = null;
    if ($winnerSeat === 1 || $winnerSeat === 2) {
        $winner = $keys[$winnerSeat];
        $loser = $keys[3 - $winnerSeat];
        $hg['hill'] = HGIsMember($hg, $winner) ? [$winner] : [];
        if (HGIsMember($hg, $loser)) $hg['line'][] = $loser;
        $hg['drawn'] = false;
    } else {
        $hg['hill'] = array_values(array_filter([$keys[1], $keys[2]], fn($k) => HGIsMember($hg, $k)));
        $hg['drawn'] = count($hg['hill']) === 2;
    }
    return $hg;
}

// The host ended the match with no result because $goneKey vanished: the one who stayed keeps the hill.
function HGAbortMatch(array $hg, string $goneKey): array {
    if (!is_array($hg['live'])) return $hg;
    $keys = $hg['live']['keys'];
    $hg['live'] = null;
    $hg['drawn'] = false;
    $stayer = ($keys[1] === $goneKey) ? $keys[2] : $keys[1];
    $other  = ($stayer === $keys[1]) ? $keys[2] : $keys[1];   // the gone player — or, if none was named, simply the other one
    $hg['hill'] = HGIsMember($hg, $stayer) ? [$stayer] : [];
    if (HGIsMember($hg, $other)) $hg['line'][] = $other;
    return $hg;
}

// One decided or drawn GAME. $seats[1|2] = ['key','userId','deckLink','leader','base','deckName']; $winnerSeat 0 = draw.
function HGRecordGame(array $hg, string $gameName, array $seats, int $winnerSeat): array {
    if (isset($hg['recorded'][$gameName])) return $hg;
    $hg['recorded'][$gameName] = true;
    foreach ([1, 2] as $s) {
        $p = $seats[$s] ?? null;
        if (!is_array($p)) continue;
        $owner = (intval($p['userId'] ?? 0) > 0) ? 'u' . intval($p['userId']) : 'k' . strval($p['key']);
        $rowKey = $owner . '|' . trim(strval($p['deckLink'] ?? ''));
        if (!isset($hg['tonight'][$rowKey])) {
            $hg['tonight'][$rowKey] = [
                'seq' => ++$hg['rowSeq'], 'owner' => $owner,
                'name' => $hg['names'][strval($p['key'])] ?? 'Player',
                'leader' => strval($p['leader'] ?? ''), 'base' => strval($p['base'] ?? ''),
                'deckName' => strval($p['deckName'] ?? ''), 'w' => 0, 'l' => 0, 'd' => 0,
            ];
        }
        if ($winnerSeat === 0) $hg['tonight'][$rowKey]['d']++;
        elseif ($winnerSeat === $s) $hg['tonight'][$rowKey]['w']++;
        else $hg['tonight'][$rowKey]['l']++;
    }
    return $hg;
}

// The table, oldest row first (never ranked). A deck name only where one player has two rows on the same leader + base.
function HGTonightRows(array $hg): array {
    $rows = array_values($hg['tonight']);
    usort($rows, fn($a, $b) => $a['seq'] <=> $b['seq']);
    $count = [];
    foreach ($rows as $r) {
        $k = $r['owner'] . '|' . $r['leader'] . '|' . $r['base'];
        $count[$k] = ($count[$k] ?? 0) + 1;
    }
    $out = [];
    foreach ($rows as $r) {
        $row = ['name' => $r['name'], 'leader' => $r['leader'], 'base' => $r['base'], 'w' => $r['w'], 'l' => $r['l'], 'd' => $r['d']];
        if ($count[$r['owner'] . '|' . $r['leader'] . '|' . $r['base']] > 1) $row['deckName'] = $r['deckName'];
        $out[] = $row;
    }
    return $out;
}

function HGRotateWatchKey(array $hg): array {
    $hg['watchKey'] = bin2hex(random_bytes(16));
    return $hg;
}
