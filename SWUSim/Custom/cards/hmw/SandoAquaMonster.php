<?php
// HMW_094
// Cost 8 - Sando Aqua Monster - [Vigilance] - Unit (Ground) 5/9 - Trait: Creature
// Text: Grit
//       When Played: If you control a Naboo base, you may defeat any number of ground units with
//       combined power equal to or less than this unit's power. Deal damage to this unit equal to the
//       combined power of the defeated units.
//
// GRIT needs no code — HMW_094 is in $Grit_Cards (generator-derived).

// The weighted pool: every GROUND unit, both sides, weighted by its CURRENT power.
// ⚠ "ground units" carries no controller restriction AND no "other", so this deliberately includes the
// opponent's ground units AND Sando himself — he is a ground unit in play by the time his own When
// Played resolves. Space units are excluded by arena, whatever their power.
function _SWUHmw094GroundWeights(int $player): array {
    $weights = [];
    foreach (['myGroundArena', 'theirGroundArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if (SWUObjGone($o)) continue;
            $weights[$mz] = intval(ObjectCurrentPower($o));   // CURRENT, so buffs/Grit count
        }
    }
    return $weights;
}

$whenPlayedAbilities["HMW_094:0"] = function($player, $mzID = '') {
    global $playerID; $playerID = intval($player);
    if (!_SWUControlsBaseWithTrait(intval($player), 'Naboo')) return;   // the gate — no base, no prompt
    $self = GetZoneObject($mzID);
    if (SWUObjGone($self)) return;
    // "combined power equal to or less than THIS UNIT'S POWER", measured NOW: Sando enters undamaged so
    // the budget is his printed 5. Grit raises his power as the self-damage lands afterwards, but that
    // cannot retroactively widen a budget the player has already spent against.
    $budget  = intval(ObjectCurrentPower($self));
    $weights = _SWUHmw094GroundWeights(intval($player));
    if (empty($weights)) return;
    SWUQueueBudgetMultiChoose(intval($player), $weights, $budget, 'power',
        "Defeat_any_number_of_ground_units_(combined_power_up_to_{$budget})",
        "HMW_094#0|" . intval($self->UniqueID ?? 0));
};

$customDQHandlers["HMW_094#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $selfUID = intval($parts[0] ?? 0);

    // Re-measure everything against the CURRENT board: this runs in a later request, so nothing may be
    // carried over from when the offer was raised.
    $selfMz = SWUFindMzByUID($selfUID);
    $budget = 0;
    if ($selfMz !== null) {
        $o = GetZoneObject($selfMz);
        if (!SWUObjGone($o)) $budget = intval(ObjectCurrentPower($o));
    }
    $weights = _SWUHmw094GroundWeights(intval($player));

    // ⚠ THE CLIENT CAP IS UX ONLY — the harness (and a crafted request) hands the answer straight here
    // without consulting the offer. SWUFilterBudgetAnswer re-derives which picks are actually legal
    // against the freshly measured pool and trims the rest.
    $picks = SWUFilterBudgetAnswer($lastDecision, $weights, $budget);
    if (empty($picks)) return;   // "any number" includes NONE — a clean decline

    // Snapshot UID + power BEFORE defeating anything: each defeat compacts the arenas, so positional
    // mzIDs captured now would address the wrong units after the first one dies (the SOR_043 discipline).
    $snap = [];
    foreach ($picks as $mz) {
        $o = GetZoneObject($mz);
        if (SWUObjGone($o)) continue;
        $snap[] = ['uid' => intval($o->UniqueID ?? 0), 'power' => intval(ObjectCurrentPower($o))];
    }

    // "damage equal to the combined power of the DEFEATED units" — MEASURE the outcome. SWUDefeatUnit
    // returns false when the defeat is prevented (JTL_103 Chewbacca's "can't be defeated by enemy card
    // abilities"), and such a unit contributes nothing to the self-damage even though it was picked.
    $total = 0;
    foreach ($snap as $s) {
        $mz = SWUFindMzByUID($s['uid']);
        if ($mz === null) continue;
        if (SWUDefeatUnit(intval($player), $mz)) $total += $s['power'];
    }
    if ($total <= 0) return;

    // Re-resolve Sando by UID: the arena has reindexed, and he may have defeated HIMSELF (he is in his
    // own pool), in which case there is nothing left to damage.
    $selfMz = SWUFindMzByUID($selfUID);
    if ($selfMz !== null) SWUDealDamageToUnit($selfMz, $total, intval($player));
};
