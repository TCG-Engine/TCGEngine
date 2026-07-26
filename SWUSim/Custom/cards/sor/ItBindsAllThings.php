<?php
// SOR_075
// Cost 2 - It Binds All Things - [Vigilance]
// Text: Heal up to 3 damage from a unit. If you control a FORCE unit, you may deal that much damage to another unit.

// SOR_075 It Binds All Things (event) — target chosen; "Heal UP TO 3" lets the player pick how much
// (0..min(3, the unit's damage)) via NUMBERCHOOSE, so they can heal less than 3 (and thus deal less).
// An undamaged target has nothing to heal → skip.
$customDQHandlers["SOR_075#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID;
    $playerID = intval($player);
    $obj = GetZoneObject($lastDecision);
    if (SWUObjGone($obj)) return;
    $cap = min(3, intval($obj->Damage ?? 0));
    if ($cap <= 0) return;               // undamaged → "heal up to 3" heals 0, no deal
    DecisionQueueController::AddDecision(intval($player), "NUMBERCHOOSE", "0|" . $cap, 1, "Heal_how_much_(up_to_3)");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "SOR_075#1|" . $lastDecision, 1);
};

// Heal the chosen amount (clamped to the unit's damage, max 3), then — if a Force unit is controlled
// and >0 was healed — may deal THAT MUCH to ANOTHER unit. "Deal that much" = the actual amount healed.
$customDQHandlers["SOR_075#1"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $playerID = intval($player);
    $targetMz = $parts[0] ?? '';
    if ($targetMz === '') return;
    $obj = GetZoneObject($targetMz);
    if (SWUObjGone($obj)) return;
    // Validate the scripted answer against the real cap (the harness does not enforce NUMBERCHOOSE max).
    $healed = max(0, min(intval($lastDecision), min(3, intval($obj->Damage ?? 0))));
    if ($healed <= 0) return;            // chose 0 → no heal, no deal
    $healedUID = intval($obj->UniqueID ?? 0);
    OnHealUnit(intval($player), $targetMz, $healed);
    if (!_SWUControlsForceUnit(intval($player))) return;
    $targets = [];
    foreach (SWUAllUnits() as $mz) {
        $o = GetZoneObject($mz);
        if (SWUObjGone($o)) continue;
        if (intval($o->UniqueID ?? 0) === $healedUID) continue; // "another unit"
        $targets[] = $mz;
    }
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets,
        "You_may_deal_" . $healed . "_damage_to_another_unit",
        "Choose_another_unit_to_damage", "DEAL_UNIT_DAMAGE|" . $healed);
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SOR_075:0"] = function($player, $mzID = '') {
// It Binds All Things — "Heal up to 3 damage from a unit. If you control a
            // FORCE unit, you may deal that much damage to another unit." The actual healed amount is
            // captured in the follow-up and carried to the optional deal.
            global $playerID;
            $playerID = intval($player);
            $targets = array_merge(
                ZoneSearch("myGroundArena",    AnyUnitFilter),
                ZoneSearch("mySpaceArena",     AnyUnitFilter),
                ZoneSearch("theirGroundArena", AnyUnitFilter),
                ZoneSearch("theirSpaceArena",  AnyUnitFilter)
            );
            if (empty($targets)) return;
            SWUQueueChooseTarget($player, $targets, "Heal_up_to_3_damage_from_a_unit", "SOR_075#0");
            return;
};
