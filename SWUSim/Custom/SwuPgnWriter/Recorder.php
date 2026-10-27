<?php
// The recorder: one frame per engine action. GameLogBeginFrame (before the action) snapshots the board; GameLogCommitFrame
// (after the queue drain and the base-defeat check, just before WriteGamestate) snapshots it again, diffs, matches the
// request's notes, and appends the events. Everything is caught: a recorder failure is logged and counted in
// meta.recorderErrors, and the game goes on.
//
// Request-scoped globals (never gamestate): SWU_PGN_RECORDING (bool), SWU_PGN_NOTES (list), SWU_PGN_S0 (snapshot),
// SWU_PGN_FRAME_GAME (the game the frame began on).

function _SwuPgnRecError(string $g, Throwable $e, string $where): void {
    error_log("SWU-PGN recorder ($where, game $g): " . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
    try {
        $m = SwuPgnMetaRead($g);
        if ($m !== null) { $m['recorderErrors'] = intval($m['recorderErrors'] ?? 0) + 1; SwuPgnMetaWrite($g, $m); }
    } catch (Throwable $ignored) {}
}

// Test seam: $GLOBALS['SWUPGN_TEST_THROW'] = '<where>' makes that recorder step throw.
function _SwuPgnTestThrow(string $where): void {
    if (($GLOBALS['SWUPGN_TEST_THROW'] ?? null) === $where) throw new RuntimeException("injected recorder failure ($where)");
}

function SwuPgnRecIsRecording(string $g): bool {
    if ($g === '') return false;
    $m = SwuPgnMetaRead($g);
    return $m !== null && !empty($m['recording']) && empty($m['finished']);
}

function _SwuPgnRecResetRequest(): void {
    $GLOBALS['SWU_PGN_RECORDING'] = false;
    $GLOBALS['SWU_PGN_NOTES'] = [];
    unset($GLOBALS['SWU_PGN_S0'], $GLOBALS['SWU_PGN_FRAME_GAME'], $GLOBALS['SWU_PGN_ACTION_SRC']);
}

// The SWU-PGN phase letter for a snapshot: setup (S) until the recorder's state says the first round began.
function _SwuPgnPhaseLetter(array $state, array $snap): string {
    if (!empty($state['setup'])) return 'S';
    return in_array($snap['phase'], ['RGS', 'DRAW', 'RES', 'READY'], true) ? 'G' : 'A';
}

// Where the file is: state.pgn = ['round' => int, 'phase' => 'S'|'A'|'G']. Kept by the boundaries below; derived from
// the board for a recording that has none yet.
function _SwuPgnRecPos(array $state, array $snap): array {
    if (isset($state['pgn']['round'], $state['pgn']['phase'])) return $state['pgn'];
    return !empty($state['setup']) ? ['round' => 0, 'phase' => 'S'] : ['round' => intval($snap['round']), 'phase' => _SwuPgnPhaseLetter($state, $snap)];
}

const SWUPGN_CARRY_FRAMES = 4;

// Diff $before against the board now, match $notes, append the events; returns the new snapshot.
function _SwuPgnRecFlush(string $g, array $before, array $notes): array {
    $after = SwuPgnSnapshot();
    $state = SwuPgnStateRead($g);
    $pos = _SwuPgnRecPos($state, $before);
    $ctx = ['ledger' => $state['ledger'] ?? SwuPgnLedgerInit([]), 'counters' => $state['counters'] ?? [],
        'round' => $pos['round'], 'phase' => $pos['phase']];
    // Notes carried from the previous frame (a play whose card moves only after a later pick) go first. One that
    // is still unclaimed after SWUPGN_CARRY_FRAMES frames is dropped.
    $carried = [];
    foreach ($state['carry'] ?? [] as $c) if (intval($c['_age'] ?? 0) < SWUPGN_CARRY_FRAMES) $carried[] = $c;
    $r = SwuPgnMatch(SwuPgnDiff($before, $after), array_merge($carried, $notes), $ctx);
    $state['carry'] = array_map(fn($c) => ['_age' => intval($c['_age'] ?? 0) + 1] + $c, $r['carry']);
    SwuPgnEventsAppend($g, $r['events']);
    $state['ledger'] = $ctx['ledger'];
    $state['counters'] = $ctx['counters'];
    $state['prev'] = $after;
    foreach ($r['events'] as $e) if ($e['t'] === 'CLAIM_INITIATIVE') $state['initiativeTaken'] = true;
    $state['gaps'] = intval($state['gaps'] ?? 0) + count($r['gaps']);
    foreach ($r['gaps'] as $gap) {
        $state['gapKinds'][$gap['k']] = intval($state['gapKinds'][$gap['k']] ?? 0) + 1;
        $card = _SwuPgnGapCard($gap, $before, $after);
        $state['gapCards'][$card] = intval($state['gapCards'][$card] ?? 0) + 1;
    }
    SwuPgnStateWrite($g, $state);
    return $after;
}

// Which card an announcement gap was about (the health line and shadow mode count gaps per card).
function _SwuPgnGapCard(array $gap, array $before, array $after): string {
    if (isset($gap['cardID'])) return strval($gap['cardID']);
    $uid = $gap['target'] ?? $gap['uid'] ?? null;
    if (is_int($uid)) {
        foreach ([$after, $before] as $snap) foreach ($snap['seats'] as $s) if (isset($s['units'][$uid])) return $s['units'][$uid]['cardID'];
    }
    if (is_string($uid) && preg_match('/^(base|leader)@(\d)$/', $uid, $m)) {
        $s = $after['seats'][intval($m[2])] ?? null;
        $c = $m[1] === 'base' ? ($s['base']['cardID'] ?? null) : ($s['leader']['cardID'] ?? null);
        if ($c !== null) return strval($c);
    }
    return '(' . $gap['k'] . ')';
}

function SwuPgnRecFrameBegin(): void {
    global $gameName;
    _SwuPgnRecResetRequest();
    $g = strval($gameName ?? '');
    try {
        if (!SwuPgnRecIsRecording($g)) return;
        _SwuPgnTestThrow('begin');
        $GLOBALS['SWU_PGN_S0'] = SwuPgnSnapshot();
        $GLOBALS['SWU_PGN_FRAME_GAME'] = $g;
        $GLOBALS['SWU_PGN_RECORDING'] = true;
        SwuPgnTxBegin($g);   // the frame's writes wait for its commit
        // The board the recorder last saw is the board now — unless something outside the runner changed it: that
        // change is written here (plain records, counted as gaps), so the file and the board never drift apart.
        $prev = SwuPgnStateRead($g)['prev'] ?? null;
        if (is_array($prev) && $prev !== $GLOBALS['SWU_PGN_S0']) _SwuPgnRecFlush($g, $prev, []);
    } catch (Throwable $e) {
        _SwuPgnRecResetRequest();
        _SwuPgnRecError($g, $e, 'begin');
    }
}

function SwuPgnRecFrameCommit(): void {
    global $gameName;
    $g = strval($gameName ?? '');
    $active = !empty($GLOBALS['SWU_PGN_RECORDING']);
    $frameGame = $GLOBALS['SWU_PGN_FRAME_GAME'] ?? null;
    $before = $GLOBALS['SWU_PGN_S0'] ?? null;
    $notes = $GLOBALS['SWU_PGN_NOTES'] ?? [];
    _SwuPgnRecResetRequest();
    // The frame must end on the game it began on: a request that loaded another game mid-action (a quick
    // rematch) would otherwise diff one game's board against another's.
    try {
        if ($active && $frameGame === $g && is_array($before)) {
            _SwuPgnTestThrow('commit');
            _SwuPgnRecFlush($g, $before, $notes);
            if (function_exists('SwuPgnRecFinishIfOver')) SwuPgnRecFinishIfOver($g);
        }
    } catch (Throwable $e) {
        _SwuPgnRecError($g, $e, 'commit');
    } finally {
        // The action is kept (the runner commits only one it writes): everything the frame held goes to disk — also
        // for a frame that ended on another game, whose own records up to the switch were real.
        try { SwuPgnTxCommit(); } catch (Throwable $e) { _SwuPgnRecError($g, $e, 'commit-write'); }
    }
}

// ── Boundaries ──────────────────────────────────────────────────────────────────────────────────
// A phase or round can end in the middle of one request (the final PASS runs the whole regroup), so each engine
// phase entry flushes what happened so far into the phase it happened in, then writes the boundary records.

// Flush the open frame up to now; it continues from the new snapshot. Returns [state, snapshot].
function _SwuPgnRecMidFlush(string $g): array {
    $after = _SwuPgnRecFlush($g, $GLOBALS['SWU_PGN_S0'], $GLOBALS['SWU_PGN_NOTES'] ?? []);
    $GLOBALS['SWU_PGN_S0'] = $after;
    $GLOBALS['SWU_PGN_NOTES'] = [];
    return [SwuPgnStateRead($g), $after];
}

// Records what the open frame has done so far, so the rest of the action diffs from here. For a step that undoes itself
// inside one action — a mulligan puts the hand back and draws the same cards: one diff of the whole action sees nothing.
function SwuPgnRecSplit(): void {
    $g = _SwuPgnRecFrameGame();
    if ($g === null) return;
    try { _SwuPgnRecMidFlush($g); } catch (Throwable $e) { _SwuPgnRecError($g, $e, 'split'); }
}

function _SwuPgnRecFrameGame(): ?string {
    global $gameName;
    if (empty($GLOBALS['SWU_PGN_RECORDING'])) return null;
    $g = $GLOBALS['SWU_PGN_FRAME_GAME'] ?? null;
    return $g !== null && $g === strval($gameName ?? '') ? $g : null;
}

const SWUPGN_PHASE_NAMES = ['S' => 'setup', 'A' => 'action', 'G' => 'regroup'];

// At the top of each SWUSim phase function (MainPhase, ActionPhaseStart, RegroupPhaseStart, DrawPhase, ResourcePhase,
// ReadyPhase). The engine's own PersistCurrentPhase hook runs AFTER the phase (and every phase it chains into), too
// late to split the records.
function SwuPgnRecBoundary(string $enginePhase): void {
    $g = _SwuPgnRecFrameGame();
    if ($g === null) return;
    try {
        [$state, $snap] = _SwuPgnRecMidFlush($g);
        $pos = _SwuPgnRecPos($state, $snap);
        $recs = [];
        if (in_array($enginePhase, ['APS', 'MAIN'], true) && $pos['phase'] !== 'A') {
            $round = intval($snap['round']);
            if ($pos['phase'] === 'S') $recs[] = ['seq' => 'R0.S.end', 't' => 'PHASE_END', 'phase' => 'setup'];
            if ($pos['phase'] === 'G') array_push($recs, ...SwuPgnRecRoundEndRecords($state, $snap, intval($pos['round'])));
            $state['initiativeTaken'] = false;
            $kf = SwuPgnKeyframe($snap, $state['ledger'], ['round' => $round, 'phase' => SWUPGN_PHASE_NAMES[$pos['phase']] ?? 'regroup', 'initiativeTaken' => false]);
            $recs[] = ['seq' => "R$round.start", 't' => 'ROUND_START', 'round' => $round] + ($kf !== null ? ['keyframe' => $kf] : []);
            $recs[] = ['seq' => "R$round.A.start", 't' => 'PHASE_START', 'phase' => 'action'];
            $pos = ['round' => $round, 'phase' => 'A'];
            unset($state['setup']);
        } elseif ($enginePhase === 'RGS' && $pos['phase'] === 'A') {
            $recs[] = ['seq' => "R{$pos['round']}.A.end", 't' => 'PHASE_END', 'phase' => 'action'];
            $recs[] = ['seq' => "R{$pos['round']}.G.start", 't' => 'PHASE_START', 'phase' => 'regroup'];
            $pos['phase'] = 'G';
        }
        SwuPgnEventsAppend($g, $recs);
        $state['pgn'] = $pos;
        SwuPgnStateWrite($g, $state);
    } catch (Throwable $e) {
        _SwuPgnRecError($g, $e, 'boundary');
    }
}

// The round's end, written when the NEXT round's action phase begins (the top of ActionPhaseStart): the regroup's
// ready step queues work of its own after the round counter moves (SOR_193 Millennium Falcon's pay-or-bounce, the
// ready taxes), and it belongs to this round. It still runs before ActionPhaseStart tears the round down, and
// initiativeTaken / phase come from the records this round wrote anyway (§13's trap).
function SwuPgnRecRoundEndRecords(array $state, array $snap, int $round): array {
    $kf = SwuPgnKeyframe($snap, $state['ledger'], ['round' => $round, 'phase' => 'regroup', 'initiativeTaken' => !empty($state['initiativeTaken'])]);
    return [
        ['seq' => "R$round.G.end", 't' => 'PHASE_END', 'phase' => 'regroup'],
        ['seq' => "R$round.end", 't' => 'ROUND_END', 'round' => $round] + ($kf !== null ? ['keyframe' => $kf] : []),
    ];
}

// ── Undo and bookmarks ──────────────────────────────────────────────────────────────────────────
// Every undo point and bookmark saves the recorder's state beside the engine's: the events so far (as a count), the
// ledger, the counters and the last snapshot. Restoring one cuts the records it retracted and leaves an UNDO mark
// (§10.2); the header's Undos count is the authority (marks can be cut by a later, deeper undo).

function SwuPgnRecCheckpoint(string $key): void {
    global $gameName;
    $g = strval($gameName ?? '');
    try {
        if (SwuPgnMetaRead($g) === null) return;
        if (_SwuPgnRecFrameGame() === $g) _SwuPgnRecMidFlush($g);   // the board the engine just saved, recorded up to now
        [$count, $hash] = SwuPgnEventsIndex($g);   // no read of the whole file
        SwuPgnCheckpointSave($g, $key, SwuPgnStateRead($g), $count, $hash);
    } catch (Throwable $e) {
        _SwuPgnRecError($g, $e, 'checkpoint');
    }
}

// The first $count records of the line a checkpoint was taken on (its fingerprint $want): the current records, or —
// for a checkpoint on an abandoned line — the current records with kept branches spliced back in. null if none fits.
function _SwuPgnRecLineAt(string $g, array $events, int $count, ?string $want): ?array {
    if ($want === null) return count($events) >= $count ? array_slice($events, 0, $count) : null;   // an older checkpoint
    $branches = null;
    $lines = [$events]; $seen = [];
    for ($depth = 0; $depth < 4 && $lines; $depth++) {
        $next = [];
        foreach ($lines as $c) {
            if (count($c) >= $count && SwuPgnEventsHash(array_slice($c, 0, $count)) === $want) return array_slice($c, 0, $count);
            $branches ??= SwuPgnBranchesRead($g);
            foreach ($branches as $b) {
                $base = intval($b['base']);
                if (count($c) < $base || SwuPgnEventsHash(array_slice($c, 0, $base)) !== $b['baseHash']) continue;
                $joined = array_merge(array_slice($c, 0, $base), $b['tail']);
                $h = SwuPgnEventsHash($joined);
                if (!isset($seen[$h]) && count($next) < 64) { $seen[$h] = true; $next[] = $joined; }
            }
        }
        $lines = $next;
    }
    return null;
}

// After the engine restored undo point / bookmark $key. Works on a finished game too: a bookmark from before the end
// reopens it (the winner is gone), so the recording is un-finished and its stale files deleted.
function SwuPgnRecRestore(string $key, int $bySeat): void {
    global $gameName;
    $g = strval($gameName ?? '');
    try {
        $meta = SwuPgnMetaRead($g);
        if ($meta === null || empty($meta['recording'])) return;
        $events = SwuPgnEventsRead($g);
        $cp = SwuPgnCheckpointLoad($g, $key);
        if ($cp === null) {
            $realEv = array_values(array_filter($events, fn($e) => ($e['t'] ?? '') !== 'UNDO'));
            $at = end($realEv)['seq'] ?? 'R0.S.start';
            _SwuPgnRecError($g, new RuntimeException("no checkpoint $key"), 'restore');
            $meta = SwuPgnMetaRead($g);
        } else {
            // The checkpoint's line: usually what is recorded now, cut back — but an undo that walks back up a line a
            // bookmark load abandoned (or any branch an undo left) finds its records in the branches kept at each cut.
            $line = _SwuPgnRecLineAt($g, $events, intval($cp['eventCount']), $cp['prefixHash'] ?? null);
            if ($line === null) {
                _SwuPgnRecError($g, new RuntimeException("checkpoint $key is on a line no longer recorded"), 'restore');
                $meta = SwuPgnMetaRead($g);
                $line = array_slice($events, 0, intval($cp['eventCount']));
            }
            $enc = fn($e) => json_encode($e, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            $common = 0;
            while ($common < count($events) && $common < count($line) && $enc($events[$common]) === $enc($line[$common])) $common++;
            // What the rewind retracts: the records after the cut that are not themselves marks of earlier rewinds.
            $dropped = array_slice($events, $common);
            $real = array_values(array_filter($dropped, fn($e) => ($e['t'] ?? '') !== 'UNDO'));
            $at = $real[0]['seq'] ?? null;
            SwuPgnBranchAppend($g, $common, array_slice($events, 0, $common), $dropped);   // kept, in case it is undone
            // The marks that sat after the cut stay where they were: a mark is about the file, not the game.
            $line = array_merge($line, array_values(array_filter($dropped, fn($e) => ($e['t'] ?? '') === 'UNDO')));
            SwuPgnEventsWrite($g, $line);
            SwuPgnStateWrite($g, ['prev' => SwuPgnSnapshot()] + $cp['state']);   // the restored board (checkpoints keep none)
            $events = $line;
        }
        // An UNDO's seq is exactly <at>-undo and seqs are unique (§9.1): a second cut back to the same point leaves the mark
        // already there standing for both, and a rewind that retracted nothing leaves none — meta.undos (the [Undos]
        // header) is the authority (§10.2).
        if ($at !== null && !in_array("$at-undo", array_column($events, 'seq'), true)) {
            SwuPgnEventsAppend($g, [['seq' => "$at-undo", 't' => 'UNDO', 'at' => $at, 'by' => $bySeat > 0 ? $bySeat : null]]);
        }
        $meta['undos'] = intval($meta['undos'] ?? 0) + 1;
        if (!empty($meta['finished']) && SWUGetGameWinner() === 0) {
            $meta['finished'] = false;
            SwuPgnOutClear($g);
        }
        SwuPgnMetaWrite($g, $meta);
        // The open frame continues from the restored board: the restore itself is not a change.
        if (_SwuPgnRecFrameGame() === $g) {
            $GLOBALS['SWU_PGN_S0'] = SwuPgnSnapshot();
            $GLOBALS['SWU_PGN_NOTES'] = [];
        }
    } catch (Throwable $e) {
        _SwuPgnRecError($g, $e, 'restore');
    }
}

// ── Keyframes (§11, §13) ────────────────────────────────────────────────────────────────────────

// The ledger's copy ids for a list of CardIDs: each takes the first unused copy of its card in $copies (the board's
// order with the ledger's identities).
function _SwuPgnKfIds(array $cardIDs, array $copies): array {
    $out = [];
    foreach ($cardIDs as $id) {
        $ref = SwuPgnCardRef($id);
        $hit = null;
        foreach ($copies as $j => $c) if (SwuPgnBaseId($c) === $ref) { $hit = $j; break; }
        if ($hit === null) { $out[] = $ref; continue; }
        $out[] = $copies[$hit];
        unset($copies[$hit]);
    }
    return $out;
}

// §11 ReducedState for both seats, or null when a seat cannot be built (a partial keyframe is non-conformant, §13).
function SwuPgnKeyframe(array $snap, array $ledger, array $ctx): ?array {
    $players = [];
    foreach ([1, 2] as $p) {
        $s = $snap['seats'][$p] ?? null;
        if ($s === null || $s['base'] === null) return null;
        $z = $ledger['zones'][$p] ?? [];
        $rows = array_values(array_filter($s['resources'], fn($r) => !$r['credit']));
        $cards = [];
        foreach ($s['units'] as $uid => $u) {
            $statusTokens = [];
            foreach ($u['tokens'] as $kind => $n) if (!in_array($kind, ['shield', 'experience'], true) && $n > 0) $statusTokens[$kind] = $n;
            // A unit's attachments and captives are listed under whichever seat moved them (UniqueIDs are game-wide).
            $both = fn(string $k) => array_merge($ledger['zones'][1][$k] ?? [], $ledger['zones'][2][$k] ?? []);
            $attached = array_values(array_filter($both("attached:$uid"), fn($c) => !str_starts_with($c, 'TOKEN:')));
            $cards[] = ['id' => SwuPgnLedgerByUid($ledger, intval($uid)) ?? SwuPgnCardRef($u['cardID']), 'zone' => $u['arena'],
                'damage' => $u['damage'], 'exhausted' => !$u['ready'],
                'upgrades' => _SwuPgnKfIds(array_column($u['upgrades'], 'cardID'), $attached),
                'shields' => intval($u['tokens']['shield'] ?? 0), 'experience' => intval($u['tokens']['experience'] ?? 0),
                'statusTokens' => (object)$statusTokens, 'captured' => _SwuPgnKfIds($u['captives'], $both("capture:$uid")),
                'power' => $u['power'], 'hp' => $u['hp'], 'keywords' => $u['keywords']];
        }
        // Cards a deck search is holding are off the engine's deck, but nothing left the deck in the file.
        $staged = count(array_filter($z['temp'] ?? [], fn($c) => ($ledger['tempOrigin'][$c] ?? 'deck') === 'deck'))
            + count(array_filter($ledger['limbo'] ?? [], fn($lb) => $lb['seat'] === $p && $lb['from'] === 'deck'));   // held until played
        $seat = ['seat' => $p, 'baseHp' => $s['base']['maxHp'] - $s['base']['damage'], 'baseMaxHp' => $s['base']['maxHp'],
            'handSize' => count($s['hand']), 'hand' => _SwuPgnKfIds($s['hand'], $z['hand'] ?? []), 'deckSize' => count($s['deck']) + $staged,
            'resourcesReady' => count(array_filter($rows, fn($r) => $r['ready'])),
            'resourcesExhausted' => count(array_filter($rows, fn($r) => !$r['ready'])),
            'resources' => _SwuPgnKfIds(array_column($rows, 'cardID'), $z['resource'] ?? []),
            'baseEpicActionUsed' => $s['base']['epicUsed'],
            // What the base holds: an upgrade on it (Fortify) and a unit it captured (Arrest).
            'baseUpgrades' => _SwuPgnKfIds($s['base']['upgrades'], $z['base'] ?? []),
            'baseCaptured' => _SwuPgnKfIds($s['base']['captives'], array_merge($ledger['zones'][1]["capture:base@$p"] ?? [], $ledger['zones'][2]["capture:base@$p"] ?? [])),
            'credits' => count($s['resources']) - count($rows), 'hasForce' => $s['force'],
            'discard' => _SwuPgnKfIds($s['discard'], $z['discard'] ?? []), 'cards' => $cards];
        $l = $s['leader'];
        if ($l !== null) {
            $id = $l['deployedUid'] !== null ? SwuPgnLedgerByUid($ledger, $l['deployedUid']) : null;
            if ($id === null) {
                $ref = SwuPgnCardRef($l['cardID']);
                foreach ($z as $list) foreach ($list as $c) if ($id === null && SwuPgnBaseId($c) === $ref) $id = $c;
            }
            // A deployed leader's ready state is its unit's: the fold applies the unit's EXHAUST/READY to the leader (§12.2).
            // Deployed as a Pilot it is an upgrade, which is never exhausted (DEPLOY_LEADER leaves it ready).
            $unit = $l['deployedUid'] !== null ? ($s['units'][$l['deployedUid']] ?? null) : null;
            $exhausted = $unit !== null ? !$unit['ready'] : ($l['deployed'] ? false : !$l['ready']);
            $seat['leader'] = ['id' => $id ?? SwuPgnCardRef($l['cardID']), 'deployed' => $l['deployed'], 'exhausted' => $exhausted,
                'epicActionUsed' => $l['epicUsed']] + (array_key_exists('onStartingSide', $l) ? ['onStartingSide' => $l['onStartingSide']] : []);
        }
        $players[strval($p)] = $seat;
    }
    return ['round' => intval($ctx['round']), 'phase' => strval($ctx['phase']), 'initiative' => $snap['initiative'] ?: null,
        'initiativeTaken' => (bool)$ctx['initiativeTaken'], 'active' => $snap['active'] ?: null, 'players' => $players];
}

// Copy ids for every card already on the board outside the decks (a fixture, or a game whose recording starts late).
function SwuPgnLedgerAdopt(array &$ledger, array $snap): void {
    foreach ($snap['seats'] as $seat => $s) {
        $seat = intval($seat);
        foreach (['hand', 'discard'] as $z) foreach ($s[$z] as $id) SwuPgnLedgerPut($ledger, $seat, $z, SwuPgnLedgerNewToken($ledger, $id));
        foreach ($s['resources'] as $r) {
            SwuPgnLedgerPut($ledger, $seat, $r['credit'] ? 'credit' : 'resource', SwuPgnLedgerNewToken($ledger, $r['cardID']));
        }
        if ($s['force']) SwuPgnLedgerPut($ledger, $seat, 'force', SwuPgnLedgerNewToken($ledger, 'LOF_T03'));
        if ($s['leader'] !== null && !$s['leader']['deployed']) SwuPgnLedgerPut($ledger, $seat, 'base', SwuPgnLedgerNewToken($ledger, $s['leader']['cardID']));
        foreach ($s['base']['captives'] ?? [] as $id) SwuPgnLedgerPut($ledger, $seat, "capture:base@$seat", SwuPgnLedgerNewToken($ledger, $id));
        foreach ($s['base']['upgrades'] ?? [] as $id) SwuPgnLedgerPut($ledger, $seat, 'base', SwuPgnLedgerNewToken($ledger, $id));   // Fortify
        foreach ($s['units'] as $uid => $u) {
            $uid = intval($uid);
            SwuPgnLedgerPut($ledger, $seat, $u['arena'], SwuPgnLedgerNewToken($ledger, $u['cardID']), $uid);
            foreach ($u['upgrades'] as $up) SwuPgnLedgerPut($ledger, $seat, "attached:$uid", SwuPgnLedgerNewToken($ledger, $up['cardID']));
            foreach ($u['tokens'] as $kind => $n) {
                for ($i = 0; $i < $n; $i++) SwuPgnLedgerPut($ledger, $seat, "attached:$uid", SwuPgnLedgerNewToken($ledger, SWUPGN_TOKEN_CARDS[$kind] ?? 'SOR_T02'));
            }
            foreach ($u['captives'] as $id) SwuPgnLedgerPut($ledger, $seat, "capture:$uid", SwuPgnLedgerNewToken($ledger, $id));
        }
    }
}

// Record only human 2-seat PvP: no game mode (goldfish/hotseat/Arenabot), no bot seat, exactly two seats.
function SwuPgnRecShouldRecord(): bool {
    return SWUGameMode() === '' && !SWUHasBotSeats() && SeatCountForGame() === 2;
}

// Called by SWUSetupGame after the decks are loaded (shuffled) and the seats, first player and phase are set, before
// the pregame runs. Opens the setup frame that SWUSetupGame commits just before its final WriteGamestate, so the opening
// draws, the mulligan prompt and the starting resources land as round-0 events.
function SwuPgnRecStart($lobby, array $opts = [], bool $deckLoadOk = true): void {
    global $gameName;
    $g = strval($gameName ?? '');
    _SwuPgnRecResetRequest();
    try {
        // Game names are reused: a recording an earlier game left under this name must never be adopted — an
        // unrecorded game would record into it, and its seats could export the earlier game's files.
        if ($g !== '') _SwuPgnRmTree(SwuPgnRecDir($g));
        // $opts['swupgnRecord'] (in-process callers only — the self-play harness's --swupgn) records a game the PvP
        // rule would not, such as bot vs bot.
        if (getenv('SWUPGN_WRITER_OFF') === '1' || !$deckLoadOk || $g === '' || !(!empty($opts['swupgnRecord']) || SwuPgnRecShouldRecord())) return;
        $matchId = isset($opts['matchId']) && $opts['matchId'] !== '' ? strval($opts['matchId']) : null;
        $match = $matchId !== null && function_exists('MatchRead') ? MatchRead('SWUSim', $matchId) : null;
        // A Bo3 child game or a rematch is set up from a synthetic lobby with no format: the match record has it.
        $format = strtolower(strval($lobby->format ?? ($match['format'] ?? '')));
        $accounts = [];
        $seat = 1;
        foreach (($lobby->players ?? []) as $p) {
            $uid = is_object($p) && method_exists($p, 'getUserId') ? $p->getUserId() : null;
            $uid ??= $match['players'][strval($seat)]['userId'] ?? null;
            $accounts[$seat] = $uid === null || $uid === '' ? null : intval($uid);
            ++$seat;
        }
        $snap = SwuPgnSnapshot();
        $ledger = SwuPgnLedgerInit([1 => $snap['seats'][1]['deck'], 2 => $snap['seats'][2]['deck']]);
        $decks = _SwuPgnRecDecks($snap, $opts);
        $setup = ['p1DeckOrder' => $ledger['zones'][1]['deck'], 'p2DeckOrder' => $ledger['zones'][2]['deck']];
        SwuPgnLedgerAdopt($ledger, $snap);   // the leaders at their bases
        SwuPgnMetaWrite($g, [
            'recording' => true, 'startedAt' => gmdate('Y-m-d\TH:i:s\Z'), 'format' => $format,
            'cardPool' => SWUFormatLegalSets($format), 'matchId' => $matchId,
            'gameNumber' => isset($opts['gameNumber']) ? intval($opts['gameNumber']) : null,
            'seed' => strval(GetSWUVar('RNG_SEED', '')), 'finished' => false, 'undos' => 0, 'recorderErrors' => 0,
            'accounts' => $accounts, 'setup' => $setup, 'decks' => $decks,
        ]);
        SwuPgnEventsTruncate($g, 0);
        SwuPgnEventsAppend($g, [['seq' => 'R0.S.start', 't' => 'PHASE_START', 'phase' => 'setup']]);
        SwuPgnStateWrite($g, ['counters' => [], 'ledger' => $ledger, 'prev' => $snap, 'setup' => true, 'gaps' => 0,
            'pgn' => ['round' => 0, 'phase' => 'S']]);
        $GLOBALS['SWU_PGN_S0'] = $snap;
        $GLOBALS['SWU_PGN_FRAME_GAME'] = $g;
        $GLOBALS['SWU_PGN_RECORDING'] = true;
        SwuPgnTxBegin($g);   // the setup frame's writes wait for SWUSetupGame's commit
    } catch (Throwable $e) {
        _SwuPgnRecResetRequest();
        _SwuPgnRecError($g, $e, 'start');
    }
}

// The DECKS facts: each seat's leader, base and deck as the game starts (the whole deck, before any draw), and the
// sideboard when the game was set up with one (a Bo3 child game's resolved decks).
function _SwuPgnRecDecks(array $snap, array $opts): array {
    $decks = [];
    foreach ([1, 2] as $s) {
        $decks[$s] = ['leader' => $snap['seats'][$s]['leader']['cardID'] ?? null, 'base' => $snap['seats'][$s]['base']['cardID'] ?? null,
            'deck' => array_merge($snap['seats'][$s]['hand'], $snap['seats'][$s]['deck'])];
        $sb = $opts['resolvedDecks'][$s]['sideboard'] ?? null;
        if (is_array($sb) && $sb) $decks[$s]['sideboard'] = array_values(array_map('strval', $sb));
    }
    return $decks;
}

// Test-only: start recording the currently loaded fixture game, whatever its seats or format.
function SwuPgnRecForceForTest(string $g): void {
    $snap = SwuPgnSnapshot();
    $ledger = SwuPgnLedgerInit([1 => $snap['seats'][1]['deck'], 2 => $snap['seats'][2]['deck']]);
    $setup = ['p1DeckOrder' => $ledger['zones'][1]['deck'], 'p2DeckOrder' => $ledger['zones'][2]['deck']];
    SwuPgnLedgerAdopt($ledger, $snap);
    SwuPgnMetaWrite($g, ['recording' => true, 'finished' => false, 'recorderErrors' => 0, 'undos' => 0,
        'startedAt' => gmdate('Y-m-d\TH:i:s\Z'), 'format' => 'premier', 'cardPool' => SWUFormatLegalSets('premier'),
        'matchId' => null, 'gameNumber' => null, 'seed' => strval(GetSWUVar('RNG_SEED', '') ?: 'test-seed'),
        'accounts' => [1 => null, 2 => null], 'setup' => $setup, 'decks' => _SwuPgnRecDecks($snap, []), 'test' => true]);
    SwuPgnEventsTruncate($g, 0);
    SwuPgnStateWrite($g, ['counters' => [], 'ledger' => $ledger, 'prev' => $snap, 'gaps' => 0]);
}
