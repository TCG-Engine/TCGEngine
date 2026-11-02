<?php
// The board as plain data. The recorder diffs two of these to find what changed in a request, so everything
// here is read-only over the current globals and JSON-plain (no zone objects, no references).

// Double-sided leaders (§10.1 LEADER_FLIP): they never deploy; SWUSim repurposes their Deployed bit as the face.
const SWUPGN_FLIP_LEADERS = ['TWI_017'];

function _SwuPgnLive(array $zone): array {
    $out = [];
    foreach ($zone as $o) if (is_object($o) && empty($o->removed)) $out[] = $o;
    return $out;
}

function _SwuPgnBool($v): bool {
    return $v === true || $v === 1 || $v === '1' || $v === 'true';
}

// Token-upgrade kind key ('shield', 'experience', 'advantage', 'weakness'), or null for any other subcard.
function _SwuPgnTokenKind(string $cardID): ?string {
    if (CardType($cardID) !== 'Token Upgrade') return null;
    return strtolower((string)CardTitle($cardID));
}

// Sorted lower-case keywords from the engine's HasKeyword_* predicates; numeric ones as "raid 2".
function _SwuPgnKeywords($obj): array {
    static $names = null;
    if ($names === null) {
        $names = [];
        foreach (get_defined_functions()['user'] as $fn) {
            if (str_starts_with($fn, 'haskeyword_')) $names[] = substr($fn, strlen('haskeyword_'));
        }
    }
    $out = [];
    foreach ($names as $kw) {
        $valueFn = 'GetKeyword_' . $kw . '_Value';
        if (function_exists($valueFn)) {
            $v = $valueFn($obj);
            if ($v !== null) $out[] = strtolower($kw) . ' ' . $v;
        } elseif (('HasKeyword_' . $kw)($obj)) {
            $out[] = strtolower($kw);
        }
    }
    sort($out);
    return $out;
}

// Cards a deck search took off the deck while the searcher picks: they ride in the pending TOPDECKSEARCH decision
// (its first segment) until the finalize draws the pick and puts the rest back.
function _SwuPgnPeeked(int $p): array {
    $out = [];
    foreach (_SwuPgnLive(GetDecisionQueue($p) ?? []) as $d) {
        if (($d->Type ?? '') !== 'TOPDECKSEARCH') continue;
        foreach (explode(',', explode('|', strval($d->Param ?? ''))[0]) as $id) if ($id !== '') $out[] = $id;
    }
    return $out;
}

// A unit's traits right now (§10.1 STATS [W-55]): printed or gained, less any lost — the engine's own object-aware trait test
// (TraitContains) over every trait any card prints. Lower case, sorted.
function _SwuPgnTraits($obj): array {
    if (!function_exists('SWUAllTraits') || !function_exists('TraitContains')) return [];
    $out = [];
    foreach (SWUAllTraits() as $t) if (TraitContains($obj, $t)) $out[] = strtolower($t);
    $out = array_values(array_unique($out));
    sort($out);
    return $out;
}

// §10.1 STATS `blank` (CR 8.14.2): the unit lost ALL its abilities. A loss that leaves one ability standing or puts another in
// its place is not blank (CR 8.14.4): SEC_054 Exiled from the Force keeps Grit, SEC_038 Condemn "loses all OTHER abilities".
function _SwuPgnBlank($obj): bool {
    if (!function_exists('_SWULostAbilitiesNow')) return false;
    $o = clone $obj;
    $o->TurnEffects = array_values(array_filter(is_array($obj->TurnEffects ?? null) ? $obj->TurnEffects : [], fn($e) => $e !== 'SEC_038'));
    return _SWULostAbilitiesNow($o, 'SEC_054');
}

// §10.1 STATS `leaderUnit` (CR 3.4.7): a unit an ability MADE a leader unit (a leader Pilot on it) — a deployed leader is one
// by being a leader, not by an ability.
function _SwuPgnMadeLeaderUnit($obj): bool {
    return function_exists('IsLeaderUnit') && IsLeaderUnit($obj) && !str_contains((string)(CardType((string)($obj->CardID ?? '')) ?? ''), 'Leader');
}

function _SwuPgnUnitSnap($obj, string $arena): array {
    $tokens = ['shield' => 0, 'experience' => 0, 'advantage' => 0, 'weakness' => 0];
    $upgrades = [];
    foreach (GetUpgradesOnUnit($obj) as $sub) {
        $id = (string)($sub->CardID ?? '');
        $kind = _SwuPgnTokenKind($id);
        if ($kind !== null) { $tokens[$kind] = ($tokens[$kind] ?? 0) + 1; continue; }
        $upgrades[] = ['cardID' => $id, 'uid' => isset($sub->UniqueID) && $sub->UniqueID !== '' ? intval($sub->UniqueID) : null,
            'isPilot' => _SwuPgnBool($sub->IsPilot ?? false), 'controller' => intval($sub->Controller ?? 0) ?: intval($obj->Controller ?? 0)];
    }
    $captives = [];
    foreach (is_array($obj->Subcards ?? null) ? $obj->Subcards : [] as $sub) {
        $sub = (object)$sub;
        if (!empty($sub->IsCaptive) && empty($sub->removed)) $captives[] = (string)$sub->CardID;
    }
    return [
        'cardID' => (string)$obj->CardID, 'arena' => $arena, 'damage' => intval($obj->Damage ?? 0),
        'ready' => intval($obj->Status ?? 0) === 1,
        'owner' => intval($obj->Owner ?? 0), 'controller' => intval($obj->Controller ?? 0),
        'power' => intval(ObjectCurrentPower($obj)), 'hp' => intval(ObjectCurrentHP($obj)),
        'keywords' => _SwuPgnKeywords($obj), 'traits' => _SwuPgnTraits($obj), 'blank' => _SwuPgnBlank($obj), 'leaderUnit' => _SwuPgnMadeLeaderUnit($obj),
        'upgrades' => $upgrades, 'tokens' => $tokens, 'captives' => $captives,
    ];
}

function _SwuPgnSeatSnap(int $p): array {
    $ids = fn(array $zone) => array_map(fn($o) => (string)$o->CardID, _SwuPgnLive($zone));
    $resources = [];
    foreach (_SwuPgnLive(GetResources($p) ?? []) as $r) {
        $resources[] = ['cardID' => (string)$r->CardID, 'ready' => intval($r->Status ?? 0) === 1,
            'credit' => SWUIsCreditToken((string)$r->CardID), 'owner' => intval($r->Owner ?? 0) ?: $p];
    }
    $base = _SwuPgnLive(GetBase($p) ?? [])[0] ?? null;
    $baseCaptives = [];
    foreach (_SwuPgnLive(GetGlobalEffects($p) ?? []) as $ge) {
        $flag = (string)($ge->CardID ?? '');
        if (str_starts_with($flag, 'SWU_BASECAPTIVE|')) $baseCaptives[] = explode('|', $flag)[1] ?? '';
    }
    $leader = _SwuPgnLive(GetLeader($p) ?? [])[0] ?? null;
    $flip = $leader !== null && in_array(strval($leader->CardID), SWUPGN_FLIP_LEADERS, true);
    $units = [];
    foreach (['ground' => GetGroundArena($p), 'space' => GetSpaceArena($p)] as $arena => $zone) {
        foreach (_SwuPgnLive($zone ?? []) as $u) $units[intval($u->UniqueID)] = _SwuPgnUnitSnap($u, $arena);
    }
    // The deployed leader's unit. DeployedUniqueID is 0 for a Pilot (a subcard, no unit of its own) — and stays 0 for a
    // pilot ejected to a unit (Eject), whose unit is then found by its card.
    $deployed = $leader !== null && !$flip && _SwuPgnBool($leader->Deployed ?? false);
    $leaderUid = $deployed && intval($leader->DeployedUniqueID ?? 0) > 0 ? intval($leader->DeployedUniqueID) : null;
    if ($deployed && $leaderUid === null) {
        foreach ($units as $uid => $u) if ($leaderUid === null && $u['cardID'] === (string)$leader->CardID && $u['owner'] === $p) $leaderUid = $uid;
    }
    return [
        'deck' => $ids(GetDeck($p) ?? []), 'hand' => $ids(GetHand($p) ?? []), 'discard' => $ids(GetDiscard($p) ?? []),
        'temp' => array_merge($ids(GetTempZone($p) ?? []), _SwuPgnPeeked($p)),   // staged for a pick — not a SWU-PGN zone
        'resources' => $resources, 'force' => PlayerHasTheForce($p),
        'base' => $base === null ? null : ['cardID' => (string)$base->CardID, 'damage' => intval($base->Damage ?? 0),
            'maxHp' => SWUBaseMaxHp($base->CardID), 'epicUsed' => _SwuPgnBool($base->EpicActionUsed ?? false),
            'captives' => $baseCaptives, 'upgrades' => array_map(fn($u) => (string)(is_array($u) ? ($u['CardID'] ?? '') : ($u->CardID ?? '')), GetUpgradesOnUnit($base))],
        'leader' => $leader === null ? null : ['cardID' => (string)$leader->CardID,
            'deployed' => $deployed, 'ready' => _SwuPgnBool($leader->Ready ?? false),
            'epicUsed' => _SwuPgnBool($leader->EpicActionUsed ?? false), 'deployedUid' => $leaderUid]
            + ($flip ? ['onStartingSide' => !_SwuPgnBool($leader->Deployed ?? false)] : []),
        'units' => $units,
    ];
}

function SwuPgnSnapshot(): array {
    $ic = (string)(GetInitiativeCounter() ?? '');
    return [
        'round' => intval(GetTurnNumber()), 'phase' => (string)GetCurrentPhase(),
        'initiative' => preg_match('/^P(\d+)_/', $ic, $m) ? intval($m[1]) : 0,
        'initiativeTaken' => str_ends_with($ic, '_CLAIMED'),
        'active' => intval(GetTurnPlayer()),
        'seats' => [1 => _SwuPgnSeatSnap(1), 2 => _SwuPgnSeatSnap(2)],
    ];
}
