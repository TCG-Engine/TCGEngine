<?php
// TWI_053
// Cost 3 - Finn - On the Run - [Vigilance,Vigilance] - Power 3 - HP 4
// Text: When this unit completes an attack: Choose a unique unit. For this phase, if damage would be dealt to that unit, prevent 1 of that damage.

// TWI_053 Finn — "When this unit completes an attack: Choose a unique unit. For this phase, if damage
// would be dealt to that unit, prevent 1 of that damage." Tag the chosen unique unit with the phase
// marker read in _SWUApplyDamagePrevention. (MZMAYCHOOSE = the in-combat-safe choose, like LOF_038.)
$onAttackEndAbilities["TWI_053:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $uniques = [];
    foreach (['myGroundArena', 'mySpaceArena', 'theirGroundArena', 'theirSpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && CardUnique($o->CardID ?? '')) $uniques[] = $mz;
        }
    }
    if (empty($uniques)) return;
    SWUQueueMayChooseTarget($player, $uniques, "Choose_a_unique_unit_(prevent_1_of_each_damage)?",
        "Choose_a_unique_unit", "TWI_053#0");
};

$customDQHandlers["TWI_053#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID; $playerID = intval($player);
    $o = GetZoneObject($lastDecision);
    if ($o !== null && empty($o->removed)) AddTurnEffect($lastDecision, 'TWI_053');
};
