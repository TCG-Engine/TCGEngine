<?php
// TWI_110
// Cost 3 - Huyang - Enduring Instructor - [Command] - Power 2 - HP 4
// Text: When Played: Choose another friendly unit. While this unit is in play, the chosen unit gets +2/+2.

// TWI_110 Huyang — "When Played: Choose another friendly unit. While this unit is in play, the chosen
// unit gets +2/+2." (Link stored as SWU_TWI110_{srcUID}_{tgtUID}; read by _SWUTwi110HasBuff.)
$whenPlayedAbilities["TWI_110:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $self = GetZoneObject($mzID);
    $selfUID = SWUObjUID($self);
    $targets = [];
    foreach (array_merge(ZoneSearch('myGroundArena', AnyUnitFilter), ZoneSearch('mySpaceArena', AnyUnitFilter)) as $mz) {
        $o = GetZoneObject($mz);
        if (SWUObjGone($o) || intval($o->UniqueID ?? -1) === $selfUID) continue;
        $targets[] = $mz;
    }
    if (empty($targets)) return;
    SWUQueueChooseTarget(intval($player), $targets, "Choose_another_friendly_unit_(it_gets_+2/+2_while_Huyang_is_in_play)", "TWI_110#0|{$selfUID}");
};

$customDQHandlers["TWI_110#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID;
    $playerID = intval($player);
    $srcUID = intval($parts[0] ?? -1);
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    AddGlobalEffects(intval($player), 'SWU_TWI110_' . $srcUID . '_' . intval($o->UniqueID ?? -1));
};
