<?php
// TWI_035
// Cost 4 - Morgan Elsbeth - Keeper of Many Secrets - [Vigilance,Villainy] - Power 3 - HP 6
// Text: Restore 1 / On Attack: You may defeat another friendly unit. If you do, draw a card.

// TWI_035 Morgan Elsbeth — "On Attack: You may defeat another friendly unit. If you do, draw a card."
$onAttackAbilities["TWI_035:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $self = GetZoneObject($mzID);
    $selfUID = SWUObjUID($self, 0);
    $targets = [];
    foreach (["myGroundArena", "mySpaceArena"] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && intval($o->UniqueID ?? 0) !== $selfUID) $targets[] = $mz;
        }
    }
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets,
        "You_may_defeat_another_friendly_unit_to_draw", "Defeat_another_friendly_unit", "TWI_035#0");
};

$customDQHandlers["TWI_035#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID;
    $playerID = intval($player);
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    SWUDefeatUnit(intval($player), $lastDecision);
    DoDrawCard(intval($player), 1);
};
