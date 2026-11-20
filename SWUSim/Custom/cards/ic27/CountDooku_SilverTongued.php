<?php
// IC27_148
// Cost 7 - Count Dooku - Silver-Tongued - [Cunning,Villainy] - Unit (Ground) 8/7 (unique)
//   Traits: Force, Separatist, Sith, Official
// Text: Each opponent can't ready more than 1 ground unit and 1 space unit during the regroup phase.
//       When Played: You may exhaust a unit.

// ── When Played: you may exhaust a unit ──────────────────────────────────────
// "a unit" is unqualified: either side, either arena, Dooku himself included (he is in play by now).
$whenPlayedAbilities["IC27_148:0"] = function($player, $mzID) {
    SWUOfferUnitTarget($player, $mzID, [
        'continuation' => 'EXHAUST_UNIT', 'may' => true,
        'question' => "Exhaust_a_unit?", 'prompt' => "Choose_a_unit_to_exhaust",
    ]);
};

// ── Each opponent can't ready more than 1 ground unit and 1 space unit during the regroup phase ─────────────
// A constant ability, read where units READY: the regroup ready step (GameLogic ReadyPhase) and every "ready a
// unit" effect (_SWUReadyBlocked / OnReadyCard / _SWUReadyInline). "during the regroup PHASE", not only its ready
// step: a regroup-start trigger that readies (a Sneak Attack defeat's SOR_226 Admiral Motti) spends the same slot.
// Only an EXHAUSTED unit that actually readies counts (CR 5.1.e); one that can't ready is no candidate and spends
// nothing. With more candidates than slots the LIMITED player chooses which readies. Two Dookus do not stack — the
// limit is 1 either way. "Each opponent" is OpponentsOf, so a Team Suns teammate is never limited. Owner ≠
// controller: the limit follows Dooku's CONTROLLER. The per-arena count is a global effect on the limited seat,
// cleared at RegroupPhaseStart (so it starts over each regroup).
function _SWUIc27148InRegroup(): bool {
    return in_array((string)GetCurrentPhase(), ['RGS', 'DRAW', 'RES', 'READY'], true);
}

// Does an opponent of $seat control Dooku right now?
function _SWUIc27148Limits(int $seat): bool {
    foreach (GetLiveSeatsArray() as $p) {
        if (in_array($seat, OpponentsOf(intval($p)), true) && _SWUControlsCardInPlay(intval($p), 'IC27_148')) return true;
    }
    return false;
}

// 'S' for a space unit, 'G' for ground. $mzHint is the unit's mzID where the caller has one.
function _SWUIc27148Arena($obj, string $mzHint): string {
    return (strpos($mzHint, 'SpaceArena') !== false || (string)($obj->Location ?? '') === 'SpaceArena') ? 'S' : 'G';
}

// How many more units of that arena $seat may ready this regroup phase (PHP_INT_MAX when no Dooku limits it).
function _SWUIc27148Slots(int $seat, string $arena): int {
    if (!_SWUIc27148Limits($seat)) return PHP_INT_MAX;
    return max(0, 1 - GlobalEffectCount($seat, 'SWU_IC27148_READIED_' . $arena));
}

// An exhausted unit just readied: during the regroup phase it spends one of its controller's slots.
function _SWUIc27148NoteReady($obj, string $mzHint): void {
    if (!_SWUIc27148InRegroup()) return;
    $ctrl = intval($obj->Controller ?? 0);
    if ($ctrl > 0) AddGlobalEffects($ctrl, 'SWU_IC27148_READIED_' . _SWUIc27148Arena($obj, $mzHint));
}

// The effect-ready gate (_SWUReadyBlocked): an exhausted unit whose controller has no slot left can't ready.
function _SWUIc27148BlocksReady($obj, string $mzHint): bool {
    if (!_SWUIc27148InRegroup() || intval($obj->Status ?? 1) === 1) return false;
    $ctrl = intval($obj->Controller ?? 0);
    return $ctrl > 0 && _SWUIc27148Slots($ctrl, _SWUIc27148Arena($obj, $mzHint)) <= 0;
}

// The ready step's choice (queued by ReadyPhase): ready the chosen unit and run its "when this unit readies" taxes
// exactly as the step does for the units it readied itself. Not counted, like the step's own readies: it is the
// regroup's last ready.
$customDQHandlers["IC27_148#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $o = GetZoneObject((string)$lastDecision);
    if (SWUObjGone($o) || intval($o->Status ?? 1) === 1) return;
    $o->Status = 1;
    _SWUQueueReadyTaxes($o);
};
