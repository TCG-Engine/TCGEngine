<?php
// LOF_171
// Cost 4 - Heavy Blaster Cannon - [Aggression] - Upgrade Power 2 - Upgrade HP 2
// Text: Attach to a non-Vehicle unit. / When Played: You may deal 1 damage to a ground unit. Then, deal 1 damage to the same unit. Then, deal 1 damage to the same unit.

// LOF_171 Heavy Blaster Cannon — When Played: may deal 1 to a ground unit, then 1, then 1 (same unit).
// (Three separate 1-damage instances — matters for shields.)
$whenPlayedAbilities["LOF_171:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $targets = SWUAllUnits(null, GroundArena);
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets, "Deal_1+1+1_to_a_ground_unit?", "Choose_a_ground_unit", "LOF_171#0");
};

$customDQHandlers["LOF_171#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID; $playerID = intval($player);
    // "The same unit" each time: re-find it by UID before every ping. Once it is defeated the rest of the damage
    // has nowhere to go (its old mzID would name the next ground unit).
    $uid = intval(GetZoneObject($lastDecision)->UniqueID ?? 0);
    for ($i = 0; $i < 3; $i++) {
        $mz = $uid > 0 ? SWUFindMzByUID($uid) : null;
        if ($mz === null) break;
        SWUDealDamageToUnit($mz, 1, intval($player));
    }
};
