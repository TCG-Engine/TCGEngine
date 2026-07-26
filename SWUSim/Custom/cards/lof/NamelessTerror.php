<?php
// LOF_033
// Cost 3 - Nameless Terror - [Vigilance,Villainy] - Power 3 - HP 3
// Text: When Played: You may exhaust a Force unit. / On Attack: Each enemy unit loses the Force trait for this phase.

// LOF_033 Nameless Terror — When Played: You may exhaust a Force unit.
// On Attack: Each enemy unit loses the Force trait for this phase. Per-instance suppression via the
// NO_TRAIT_FORCE phase marker (read by _SWUUnitHasTrait at the arena-object trait sites). Snapshot of
// enemy units in play when it resolves — units entering later this phase are NOT affected.
$whenPlayedAbilities["LOF_033:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $targets = [];
    foreach (SWUAllUnits() as $mz) {
        $o = GetZoneObject($mz);
        if (SWUObjGone($o)) continue;
        if (TraitContains($o, 'Force') && intval($o->Status ?? 0) === 1) $targets[] = $mz; // ready Force units
    }
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), array_values($targets), "Exhaust_a_Force_unit?", "Choose_a_Force_unit", "EXHAUST_UNIT");
};

$onAttackAbilities["LOF_033:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    foreach (SWUAllUnits('their') as $mz) {
        $o = GetZoneObject($mz);
        if (SWUObjGone($o)) continue;
        AddTurnEffect($mz, 'NO_TRAIT_FORCE'); // loses Force this phase
    }
};
