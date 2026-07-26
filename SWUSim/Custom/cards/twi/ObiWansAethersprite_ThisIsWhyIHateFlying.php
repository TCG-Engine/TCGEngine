<?php
// TWI_048
// Cost 5 - Obi-Wan's Aethersprite - This is Why I Hate Flying - [Vigilance,Heroism] - Power 4 - HP 6
// Text: When Played/On Attack: You may deal 1 damage to this unit and 2 damage to another space unit.

// TWI_048 Obi-Wan's Aethersprite — "When Played/On Attack: You may deal 1 damage to this unit and 2
// damage to another space unit." One "may" gating a bundled effect (1 self + 2 to another space unit).
$whenPlayedAbilities["TWI_048:0"] = $onAttackAbilities["TWI_048:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $self = GetZoneObject($mzID);
    if (SWUObjGone($self)) return;
    $selfUID = intval($self->UniqueID ?? 0);
    $targets = [];
    foreach (["mySpaceArena", "theirSpaceArena"] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && intval($o->UniqueID ?? 0) !== $selfUID) $targets[] = $mz;
        }
    }
    if (empty($targets)) return; // no "another space unit" → can't perform the combined effect
    SWUQueueMayChooseTarget(intval($player), $targets,
        "Deal_1_to_this_unit_and_2_to_another_space_unit?", "Choose_another_space_unit",
        "TWI_048#0|{$selfUID}");
};

$customDQHandlers["TWI_048#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID;
    $playerID = intval($player);
    $selfMz = SWUFindMzByUID(intval($parts[0] ?? 0));
    if ($selfMz !== null) SWUDealDamageToUnit($selfMz, 1, intval($player)); // 1 to this unit
    SWUDealDamageToUnit($lastDecision, 2, intval($player));                 // 2 to the chosen space unit
};
