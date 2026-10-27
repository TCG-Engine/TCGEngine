<?php
// Game end: GAME_END, then the three files — out/all.swupgn (sees everything) and out/p1.swupgn / out/p2.swupgn (each
// player's own view) — each checked by our reader, behind the PII gate, plus one health line. Runs once per finish
// (meta.finished); a bookmark that reopens the game clears it (Recorder.php SwuPgnRecRestore).

// [WriterRevision] (§5.2): bump on EVERY change to what the writer writes that a reader can see (§22). 1 = the first
// revision with the tag (the Homeworlds revision of 1.0, 2026-10-26).
const SWUPGN_WRITER_REVISION = 1;

// The engine's own words for the ending, in the spec's style (§5.1 Reason, e.g. "Base Destroyed").
function _SwuPgnEndReason(): string {
    $r = strval(DecisionQueueController::GetVariable('GAMEOVER_REASON') ?? '');
    if ($r === 'concede') return 'Concession';
    if ($r === 'abandon') return 'Abandoned';
    $log = strval($GLOBALS['SWU_PGN_END_REASON'] ?? '');
    if (stripos($log, 'base was defeated') !== false) return 'Base Destroyed';
    return $log === '' ? 'Game Over' : 'Card Effect';
}

function SwuPgnRecFinishIfOver(string $g): void {
    $w = SWUGetGameWinner();
    if ($w <= 0) return;
    $meta = SwuPgnMetaRead($g);
    if ($meta === null || empty($meta['recording']) || !empty($meta['finished'])) return;
    SwuPgnRecFinish($g, $w, _SwuPgnEndReason());
}

// A whole Bo3 match conceded (mode 10007): the game ends with no gamestate winner.
function SwuPgnRecFinishConcede(int $winnerSeat): void {
    global $gameName;
    $g = strval($gameName ?? '');
    $meta = SwuPgnMetaRead($g);
    if ($meta === null || empty($meta['recording']) || !empty($meta['finished'])) return;
    SwuPgnRecFinish($g, $winnerSeat, 'Match conceded');
}

// Usernames of both seats, for the PII scan only (never written anywhere). Looked up by account id.
function SwuPgnSeatUsernames(array $meta): array {
    if (isset($GLOBALS['SWUPGN_TEST_USERNAMES'])) return $GLOBALS['SWUPGN_TEST_USERNAMES'];
    $out = [];
    try {
        if (!function_exists('GetLocalMySQLConnection') || !function_exists('MatchHistoryUsername')) return [];
        $conn = GetLocalMySQLConnection();
        foreach ($meta['accounts'] ?? [] as $seat => $id) {
            if ($id !== null && ($u = MatchHistoryUsername($conn, intval($id))) !== null) $out[intval($seat)] = $u;
        }
    } catch (Throwable $e) {
        error_log('SWU-PGN: username lookup for the PII scan failed: ' . $e->getMessage());
    }
    return $out;
}

// True when a username (3+ characters, any case) appears in the file outside the CARDS index, card ids and card names
// (a player named "Wampa" must not block a game with a Wampa in it).
function SwuPgnPiiScan(string $text, array $usernames): bool {
    $names = array_values(array_filter(array_map('strval', $usernames), fn($u) => mb_strlen(trim($u)) >= 3));
    if (!$names) return false;
    $cardNames = [];
    $section = '';
    $lines = explode("\n", $text);
    foreach ($lines as $line) {
        if (str_starts_with($line, '%%%')) { $section = strtoupper(trim(substr($line, 3))); continue; }
        if ($section === 'CARDS' && ($r = json_decode($line, true)) && isset($r['name'])) $cardNames[] = strval($r['name']);
    }
    usort($cardNames, fn($a, $b) => mb_strlen($b) <=> mb_strlen($a));
    $idKeys = array_flip(array_merge(SWUPGN_ID_FIELDS, SWUPGN_ID_LIST_FIELDS, ['id', 'name', 'leader', 'base', 'deck', 'sideboard',
        'hand', 'discard', 'resources', 'upgrades', 'captured', 'p1DeckOrder', 'p2DeckOrder']));
    $strings = [];
    $collect = function ($v, $key) use (&$collect, &$strings, $idKeys): void {
        if ($key !== null && isset($idKeys[$key])) return;
        if (is_string($v)) { $strings[] = $v; return; }
        if (is_array($v)) foreach ($v as $k => $x) $collect($x, is_string($k) ? $k : $key);
    };
    $section = '';
    foreach ($lines as $line) {
        if (str_starts_with($line, '%%%')) { $section = strtoupper(trim(substr($line, 3))); continue; }
        if ($section === 'CARDS') continue;
        if ($section === '' || $section === 'STORY') { $strings[] = $line; continue; }
        $r = json_decode($line, true);
        if (is_array($r)) $collect($r, null); else $strings[] = $line;
    }
    foreach ($strings as $s) {
        foreach ($cardNames as $cn) $s = str_ireplace($cn, ' ', $s);
        foreach ($names as $u) if (mb_stripos($s, $u) !== false) return true;
    }
    return false;
}

// §6.5: what a card IS, by printed type — "unit", "upgrade", or absent (events, bases, leaders, Credits, the Force).
function _SwuPgnCardKind(string $cardID): ?string {
    $t = strval(CardType($cardID) ?? '');
    if (str_contains($t, 'Upgrade')) return 'upgrade';
    if (str_contains($t, 'Unit')) return 'unit';
    return null;
}

function _SwuPgnCardsIndex(array $doc): array {
    $ids = [];
    $collect = function ($v) use (&$collect, &$ids): void {
        if (is_string($v)) { $ids[SwuPgnBaseId($v)] = true; return; }
        if (is_array($v) || is_object($v)) foreach ((array)$v as $x) $collect($x);
    };
    $collect([$doc['headers'], $doc['decks'], $doc['setup'], $doc['events']]);
    $out = [];
    foreach (array_keys($ids) as $id) {
        $cardID = SwuPgnMapCardId($id);
        if ($cardID === null) continue;
        $title = strval(CardTitle($cardID) ?? '');
        if ($title === '') continue;
        $sub = strval(CardSubtitle($cardID) ?? '');
        $kind = _SwuPgnCardKind($cardID);
        $name = $sub !== '' ? "$title, $sub" : $title;
        // A leader whose Leader Unit side is another card (Tarkin → The Death Star): the front is the name, the other side
        // backSideName (§6.5).
        $back = strval(CardLeaderUnitTitle($cardID) ?? '');
        $backSub = strval(CardLeaderUnitSubtitle($cardID) ?? '');
        if ($back !== '') $back = $backSub !== '' ? "$back, $backSub" : $back;
        $out[] = ['id' => $id, 'name' => $name] + ($kind !== null ? ['kind' => $kind] : []) + ($back !== '' && $back !== $name ? ['backSideName' => $back] : []);
    }
    usort($out, fn($a, $b) => strcmp($a['id'], $b['id']));
    return $out;
}

function _SwuPgnDeckList(array $cardIDs): array {
    $count = [];
    foreach ($cardIDs as $c) { $ref = SwuPgnCardRef(strval($c)); $count[$ref] = ($count[$ref] ?? 0) + 1; }
    ksort($count);
    $out = [];
    foreach ($count as $ref => $n) $out[] = [$ref, $n];
    return $out;
}

// The all-seeing document.
function SwuPgnBuildDoc(string $g, array $meta, array $state, array $events, int $winner, string $reason): array {
    $gameId = SwuPgnGameId('SWUSim', $g);
    $playerId = fn(int $seat) => ($meta['accounts'][$seat] ?? null) !== null
        ? SwuPgnHashId('acct:' . intval($meta['accounts'][$seat]), $gameId)
        : SwuPgnHashId("guest:$g:$seat", $gameId);
    $decks = [];
    foreach ([1, 2] as $s) {
        $d = $meta['decks'][$s] ?? [];
        $row = ['p' => $s, 'leader' => SwuPgnCardRef(strval($d['leader'] ?? '')), 'base' => SwuPgnCardRef(strval($d['base'] ?? '')),
            'deck' => _SwuPgnDeckList($d['deck'] ?? [])];
        if (!empty($d['sideboard'])) $row['sideboard'] = _SwuPgnDeckList($d['sideboard']);
        $decks[] = $row;
    }
    $headers = [
        'Game' => 'SWU-PGN/1.0', 'WriterRevision' => strval(SWUPGN_WRITER_REVISION), 'GameId' => $gameId, 'Date' => strval($meta['startedAt'] ?? gmdate('Y-m-d\TH:i:s\Z')),
        'EndDate' => gmdate('Y-m-d\TH:i:s\Z'), 'Format' => strval($meta['format'] ?? ''),
        'CardPool' => implode(',', $meta['cardPool'] ?? []), 'Engine' => SwuPgnEngineId(), 'Seed' => strval($meta['seed'] ?? ''),
        'P1Id' => $playerId(1), 'P2Id' => $playerId(2), 'P1' => 'Player 1', 'P2' => 'Player 2',
        'P1Leader' => $decks[0]['leader'], 'P1Base' => $decks[0]['base'], 'P2Leader' => $decks[1]['leader'], 'P2Base' => $decks[1]['base'],
        'Result' => $winner === 1 || $winner === 2 ? "P$winner" : 'Draw', 'Reason' => $reason,
        'Rounds' => strval(max(1, intval($state['pgn']['round'] ?? 1))),
    ];
    // Match / GameNumber only for a Bo3 (design §7); the id is keyed, never the raw match id.
    $matchId = $meta['matchId'] ?? null; $gameNumber = $meta['gameNumber'] ?? null;
    if (($matchId === null || $gameNumber === null) && function_exists('SWUReadMatchRef') && is_array($ref = SWUReadMatchRef($g))) {
        $matchId = $matchId ?? ($ref['matchId'] ?? null);
        $gameNumber = $gameNumber ?? ($ref['gameNumber'] ?? null);
    }
    $match = $matchId !== null && function_exists('MatchRead') ? MatchRead('SWUSim', strval($matchId)) : null;
    if (is_array($match) && intval($match['bestOf'] ?? 1) === 3) {
        $headers['Match'] = SwuPgnHashId('match:' . $matchId, $gameId);
        $headers['GameNumber'] = strval(intval($gameNumber ?? 1));
    }
    if (intval($meta['undos'] ?? 0) > 0) $headers['Undos'] = strval(intval($meta['undos']));
    if (intval($meta['recorderErrors'] ?? 0) > 0) $headers['RecorderErrors'] = strval(intval($meta['recorderErrors']));
    $setup = isset($meta['setup']) ? [['seq' => 'R1.S.0', 't' => 'INIT', 'p1DeckOrder' => $meta['setup']['p1DeckOrder'] ?? [],
        'p2DeckOrder' => $meta['setup']['p2DeckOrder'] ?? []]] : [];
    $doc = ['headers' => $headers, 'story' => null, 'decks' => $decks, 'cards' => [], 'setup' => $setup, 'events' => $events];
    $doc['cards'] = _SwuPgnCardsIndex($doc);
    $doc['story'] = explode("\n", SwuPgnRender($doc));
    return $doc;
}

// Our reader's verdict on one file: [] when it validates and folds to its own keyframes.
function _SwuPgnFileProblems(string $text): array {
    $parsed = SwuPgnTryParse($text);
    if ($parsed['doc'] === null) return ['parse: ' . $parsed['error']];
    $out = [];
    foreach (SwuPgnValidate($parsed['doc'])['errors'] as $e) $out[] = 'validate: ' . ($e['message'] ?? json_encode($e));
    foreach (SwuPgnCheckKeyframes($parsed['doc']['events'])['mismatches'] ?? [] as $m) $out[] = 'keyframe: ' . json_encode($m);
    return $out;
}

function SwuPgnRecFinish(string $g, int $winner, string $reason): void {
    try {
        $meta = SwuPgnMetaRead($g);
        if ($meta === null || empty($meta['recording']) || !empty($meta['finished'])) return;
        if (_SwuPgnRecFrameGame() === $g) _SwuPgnRecMidFlush($g);
        $state = SwuPgnStateRead($g);
        $pos = _SwuPgnRecPos($state, SwuPgnSnapshot());
        SwuPgnEventsAppend($g, [['seq' => "R{$pos['round']}.{$pos['phase']}.game-end", 't' => 'GAME_END',
            'winner' => $winner === 1 || $winner === 2 ? $winner : 'Draw', 'reason' => $reason]]);

        $events = SwuPgnEventsRead($g);
        $build = function () use ($g, &$meta, $state, $events, $winner, $reason) {
            $doc = SwuPgnBuildDoc($g, $meta, $state, $events, $winner, $reason);
            $texts = ['all' => SwuPgnWriteText($doc)];
            foreach ([1, 2] as $s) $texts["p$s"] = SwuPgnWriteText(SwuPgnCutPerspective($doc, $s));
            return [$doc, $texts];
        };
        [$doc, $texts] = $build();
        $problems = [];
        foreach ($texts as $k => $t) foreach (_SwuPgnFileProblems($t) as $p) $problems[] = "$k $p";
        if ($problems) {
            error_log("SWU-PGN: game $g files do not check clean (" . count($problems) . "): " . implode(' | ', array_slice($problems, 0, 20)));
            $meta['recorderErrors'] = intval($meta['recorderErrors'] ?? 0) + 1;
            [$doc, $texts] = $build();   // again, so the files carry the [RecorderErrors] they now have
        }
        $usernames = SwuPgnSeatUsernames($meta);
        $refused = false;
        foreach ($texts as $t) if (SwuPgnPiiScan($t, $usernames)) $refused = true;
        if ($refused) {
            error_log("SWU-PGN: game $g files REFUSED by the PII gate (a player's username is in the file)");
        } else {
            $dir = SwuPgnRecDir($g) . '/out';
            foreach ($texts as $k => $t) _SwuPgnWriteAtomic("$dir/$k.swupgn", $t);
            // SWU Forge wants the all-seeing file; the delivery itself is the worker's (SWUSim/SWUForgeDelivery.php).
            if (function_exists('SWUForgeEnqueueGame')) SWUForgeEnqueueGame($g, $meta, $texts['all']);
        }
        $meta['finished'] = true;
        $meta['endedAt'] = gmdate('Y-m-d\TH:i:s\Z');
        SwuPgnMetaWrite($g, $meta);
        $GLOBALS['SWU_PGN_RECORDING'] = false;   // nothing after the end is recorded in this request
        SwuPgnHealthAppend(['game' => $g, 'endedAt' => $meta['endedAt'], 'format' => $meta['format'] ?? null, 'result' => $winner,
            'reason' => $reason, 'events' => count($doc['events']), 'gaps' => intval($state['gaps'] ?? 0), 'gapKinds' => $state['gapKinds'] ?? [],
            'recorderErrors' => intval($meta['recorderErrors'] ?? 0), 'undos' => intval($meta['undos'] ?? 0),
            'problems' => array_slice($problems, 0, 50), 'refused' => $refused]);
    } catch (Throwable $e) {
        _SwuPgnRecError($g, $e, 'finish');
    }
}
