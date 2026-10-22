<?php
// Game-log notes: the "why" beside each existing announcement (play, attack, damage, …). They live only in this
// request ($GLOBALS['SWU_PGN_NOTES']), never in the gamestate. An unrecorded game pays one boolean check.
// Shapes: see Matcher.php, which turns them (with the snapshot diff) into SWU-PGN events.

function SwuPgnNote(array $note): void {
    if (empty($GLOBALS['SWU_PGN_RECORDING'])) return;
    $GLOBALS['SWU_PGN_NOTES'][] = $note;
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
    $tgt = SwuPgnNoteRef($tgtObj);
    if ($tgt === null) return;
    $src = $combat ? _SwuPgnNoteCombatSource($tgtObj) : SwuPgnNoteSource();
    SwuPgnNote(['t' => 'DAMAGE', 'src' => $src ?? 'unknown', 'tgt' => $tgt, 'amt' => $amount, 'combat' => $combat]);
}

function SwuPgnNoteHeal($tgtObj, int $amount): void {
    if (!SwuPgnNoteOn() || $amount <= 0) return;
    $tgt = SwuPgnNoteRef($tgtObj);
    if ($tgt !== null) SwuPgnNote(['t' => 'HEAL', 'src' => SwuPgnNoteSource() ?? 'unknown', 'tgt' => $tgt, 'amt' => $amount]);
}

// A unit defeated: by combat ("attack", $byObj = the other combatant) or by an effect.
function SwuPgnNoteDefeat($obj, string $reason, $byObj = null): void {
    if (!SwuPgnNoteOn() || !is_object($obj)) return;
    $by = $byObj !== null ? SwuPgnNoteRef($byObj) : ($reason === 'attack' ? null : SwuPgnNoteSource());
    if ($reason !== 'attack') $reason = $by !== null ? 'ability' : $reason;
    SwuPgnNote(['t' => 'DEFEAT', 'uid' => intval($obj->UniqueID ?? 0) ?: null, 'cardID' => strval($obj->CardID ?? ''), 'reason' => $reason, 'by' => $by]);
}

function SwuPgnNoteToken($hostObj, string $tokenCardID, int $delta): void {
    if (!SwuPgnNoteOn() || !is_object($hostObj) || $delta === 0) return;
    $kind = _SwuPgnTokenKind($tokenCardID);
    if ($kind !== null) SwuPgnNote(['t' => 'TOKEN', 'uid' => intval($hostObj->UniqueID ?? 0), 'kind' => $kind, 'delta' => $delta]);
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
    $type = strval($head->Type ?? '');
    if (in_array($type, ['MZCHOOSE', 'MZMAYCHOOSE'], true)) {
        $mzs = array_values(array_filter(explode('&', strval($head->Param ?? '')), fn($m) => $m !== '' && $m !== '-'));
        $idx = array_search($answer, $mzs, true);
        if ($idx === false) return;
        $offered = []; $seats = []; $zones = [];
        foreach ($mzs as $mz) {
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
}
