<?php
// Game-log notes: the "why" beside each existing announcement (play, attack, damage, …). They live only in this
// request ($GLOBALS['SWU_PGN_NOTES']), never in the gamestate. An unrecorded game pays one boolean check: every engine
// call site is guarded by SwuPgnNoteOn(), so it builds a note's arguments only for a recorded game. A note that fails is
// a recorder error, never the action's.
// Shapes: see Matcher.php, which turns them (with the snapshot diff) into SWU-PGN events.

function SwuPgnNote(array $note): void {
    if (empty($GLOBALS['SWU_PGN_RECORDING'])) return;
    try {
        _SwuPgnTestThrow('note');
        $GLOBALS['SWU_PGN_NOTES'][] = $note;
    } catch (Throwable $e) { _SwuPgnNoteFailed($e); }
}
function _SwuPgnNoteFailed(Throwable $e): void {
    global $gameName;
    _SwuPgnRecError(strval($GLOBALS['SWU_PGN_FRAME_GAME'] ?? $gameName ?? ''), $e, 'note');
}

function SwuPgnNoteOn(): bool {
    return !empty($GLOBALS['SWU_PGN_RECORDING']);
}

// What a note names for a zone object: a unit's UniqueID, base@N for a base, else its CardID.
function SwuPgnNoteRef($obj) {
    if (!is_object($obj)) return null;
    if ($obj instanceof Base) return 'base@' . (function_exists('SWUObjSeat') ? SWUObjSeat($obj) : intval($obj->PlayerID ?? 0));   // a base carries no Owner
    if (isset($obj->UniqueID) && intval($obj->UniqueID) > 0 && in_array($obj->Location ?? '', ['GroundArena', 'SpaceArena'], true)) {
        return intval($obj->UniqueID);
    }
    return isset($obj->CardID) ? strval($obj->CardID) : null;
}

// The card whose ability is resolving (the game log's source), base@N when it is that seat's base; a one-shot
// $GLOBALS['SWU_PGN_DAMAGE_SRC'] (a rule, e.g. "rule:deck-out") wins.
function SwuPgnNoteSource() {
    if (!empty($GLOBALS['SWU_PGN_DAMAGE_SRC'])) return strval($GLOBALS['SWU_PGN_DAMAGE_SRC']);
    [$p, $cardID] = function_exists('SWULogSource') ? SWULogSource() : [0, ''];
    if ($p <= 0 || $cardID === '') return null;
    foreach (GetBase($p) ?? [] as $b) if (empty($b->removed) && $b->CardID === $cardID) return "base@$p";
    return $cardID;
}

// Combat damage: the attacker dealt it, unless it landed on the attacker's side (the defender's counter).
function _SwuPgnNoteCombatSource($tgtObj) {
    $atk = intval(GetSWUVar('SWU_CURRENT_ATTACKER_UID', '0'));
    $def = intval(GetSWUVar('SWU_CURRENT_DEFENDER_UID', '0'));
    $atkSeat = intval(GetSWUVar('SWU_CURRENT_ATTACKER_SEAT', '0'));
    $tgtUid = is_object($tgtObj) ? intval($tgtObj->UniqueID ?? 0) : 0;
    $onAttackerSide = $tgtUid > 0 && ($tgtUid === $atk || intval($tgtObj->Controller ?? 0) === $atkSeat);
    if ($onAttackerSide) return $def > 0 ? $def : null;
    return $atk > 0 ? $atk : null;
}

function SwuPgnNoteDamage($tgtObj, int $amount, bool $combat): void {
    if (!SwuPgnNoteOn() || $amount <= 0) return;
    try {
        $tgt = SwuPgnNoteRef($tgtObj);
        if ($tgt === null) return;
        $src = $combat ? _SwuPgnNoteCombatSource($tgtObj) : SwuPgnNoteSource();
        SwuPgnNote(['t' => 'DAMAGE', 'src' => $src ?? 'unknown', 'tgt' => $tgt, 'amt' => $amount, 'combat' => $combat]);
    } catch (Throwable $e) { _SwuPgnNoteFailed($e); }
}

function SwuPgnNoteHeal($tgtObj, int $amount): void {
    if (!SwuPgnNoteOn() || $amount <= 0) return;
    try {
        $tgt = SwuPgnNoteRef($tgtObj);
        if ($tgt !== null) SwuPgnNote(['t' => 'HEAL', 'src' => SwuPgnNoteSource() ?? 'unknown', 'tgt' => $tgt, 'amt' => $amount]);
    } catch (Throwable $e) { _SwuPgnNoteFailed($e); }
}

// The rules' own reasons for a defeat (§6.4), set around the engine code that does it: the uniqueness rule (it wins over any
// card in the log's source), the rules engine — "frameworkEffect" — when no card is to blame ($fallback: a card in the
// source wins), or a keyword of the card being played (Exploit). $fn runs either way; the reason lasts only while it does.
function SwuPgnDefeatCause(string $reason, ?string $byCardID, callable $fn, bool $fallback = false) {
    $prev = $GLOBALS['SWU_PGN_DEFEAT_CAUSE'] ?? null;
    $GLOBALS['SWU_PGN_DEFEAT_CAUSE'] = ['reason' => $reason, 'by' => $byCardID, 'fallback' => $fallback];
    try { return $fn(); } finally { $GLOBALS['SWU_PGN_DEFEAT_CAUSE'] = $prev; }
}
// [reason, by] for an effect defeat: the cause set around it, else the card in the log's source ("ability"), else $default.
function _SwuPgnDefeatReason(string $default): array {
    $cause = $GLOBALS['SWU_PGN_DEFEAT_CAUSE'] ?? null;
    if ($cause !== null && empty($cause['fallback'])) return [$cause['reason'], $cause['by']];
    $by = SwuPgnNoteSource();
    if ($by !== null) return ['ability', $by];
    return $cause !== null ? [$cause['reason'], $cause['by']] : [$default, null];
}

// A unit defeated: by combat ("attack", $byObj = the other combatant) or by an effect.
function SwuPgnNoteDefeat($obj, string $reason, $byObj = null): void {
    if (!SwuPgnNoteOn() || !is_object($obj)) return;
    try {
        if ($reason === 'attack') $by = $byObj !== null ? SwuPgnNoteRef($byObj) : null;
        elseif ($byObj !== null) [$reason, $by] = ['ability', SwuPgnNoteRef($byObj)];
        else [$reason, $by] = _SwuPgnDefeatReason($reason);
        SwuPgnNote(['t' => 'DEFEAT', 'uid' => intval($obj->UniqueID ?? 0) ?: null, 'cardID' => strval($obj->CardID ?? ''), 'reason' => $reason, 'by' => $by]);
    } catch (Throwable $e) { _SwuPgnNoteFailed($e); }
}

function SwuPgnNoteToken($hostObj, string $tokenCardID, int $delta): void {
    if (!SwuPgnNoteOn() || !is_object($hostObj) || $delta === 0) return;
    try {
        $kind = _SwuPgnTokenKind($tokenCardID);
        if ($kind !== null) SwuPgnNote(['t' => 'TOKEN', 'uid' => intval($hostObj->UniqueID ?? 0), 'kind' => $kind, 'delta' => $delta]);
    } catch (Throwable $e) { _SwuPgnNoteFailed($e); }
}

// An mz's zone in the ledger's terms: myHand-2 → hand, theirDeck-0 → deck, myResources-1 → resource.
function _SwuPgnNoteMzZone(string $mz): ?string {
    $z = preg_replace('/^(my|their)|-.*$/', '', $mz);
    return ['Hand' => 'hand', 'Deck' => 'deck', 'Discard' => 'discard', 'Resources' => 'resource', 'TempZone' => 'temp',
        'Base' => 'base', 'Leader' => 'base', 'GroundArena' => 'ground', 'SpaceArena' => 'space'][$z] ?? null;
}

// The decision a player just answered, as a CHOICE: what was offered and which one they picked.
function SwuPgnNoteChoice(int $player, $head, string $answer): void {
    if (!SwuPgnNoteOn() || !is_object($head)) return;
    try {
        $type = strval($head->Type ?? '');
        if (in_array($type, ['MZCHOOSE', 'MZMAYCHOOSE'], true)) {
            $mzs = array_values(array_filter(explode('&', strval($head->Param ?? '')), fn($m) => $m !== '' && $m !== '-'));
            $idx = array_search($answer, $mzs, true);
            if ($idx === false) return;
            $offered = []; $seats = []; $zones = [];
            foreach ($mzs as $mz) {
                // An upgrade on a host ("<host mz>.u<Subcards key>"): its card, in the host's zone — a base's own, or the
                // unit's attachments.
                if (preg_match('/^(.+-\d+)\.u(\d+)$/', $mz, $m)) {
                    $host = _SWULogResolve($m[1], $player);
                    $sub = is_object($host) && is_array($host->Subcards ?? null) ? ($host->Subcards[intval($m[2])] ?? null) : null;
                    $cid = is_array($sub) ? ($sub['CardID'] ?? null) : (is_object($sub) ? ($sub->CardID ?? null) : null);
                    if (is_string($cid) && $cid !== '') {
                        $offered[] = $cid;
                        $seats[] = str_starts_with($mz, 'their') ? 3 - $player : $player;
                        $zones[] = $host instanceof Base ? 'base' : 'attached:' . intval($host->UniqueID ?? 0);
                        continue;
                    }
                }
                $o = _SWULogResolve($mz, $player);
                $offered[] = SwuPgnNoteRef($o) ?? $mz;
                // Whose zone it is, and which: a card in both decks is THIS seat's copy (the matcher resolves by these).
                $seats[] = str_starts_with($mz, 'their') ? 3 - $player : $player;
                $zones[] = _SwuPgnNoteMzZone($mz);
            }
        } elseif ($type === 'OPTIONCHOOSE' || $type === 'YESNO') {
            $offered = $type === 'YESNO' ? ['YES', 'NO'] : explode('&', strval($head->Param ?? ''));
            $idx = array_search($answer, $offered, true);
            if ($idx === false) return;
            $offered = array_map(fn($o) => str_replace('_', ' ', $o), $offered);
        } else {
            return;
        }
        $prompt = trim(str_replace('_', ' ', strval($head->Tooltip ?? '')));
        SwuPgnNote(['t' => 'CHOICE', 'p' => $player, 'offered' => $offered, 'chose' => intval($idx), 'prompt' => $prompt !== '' && $prompt !== '-' ? $prompt : null]
            + (isset($seats) ? ['offeredSeats' => $seats, 'offeredZones' => $zones] : []));
    } catch (Throwable $e) { _SwuPgnNoteFailed($e); }
}
