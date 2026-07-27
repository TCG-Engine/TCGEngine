<?php
// LOF_033
// Cost 3 - Nameless Terror - [Vigilance,Villainy] - Power 3 - HP 3
// Text: When Played: You may exhaust a Force unit. / On Attack: Each enemy unit loses the Force trait for this phase.

// LOF_033 Nameless Terror — When Played: You may exhaust a Force unit.
// On Attack: Each enemy unit loses the Force trait for this phase. Per-instance suppression via the
// NO_TRAIT_FORCE phase marker (read by _SWUUnitHasTrait at the arena-object trait sites). Snapshot of
// enemy units in play when it resolves — units entering later this phase are NOT affected.
$whenPlayedAbilities["LOF_033:0"] = function($player, $mzID) {
    SWUOfferUnitTarget($player, $mzID, [
        'continuation' => 'EXHAUST_UNIT', 'traits' => 'Force', 'may' => true,
        'extraFilter' => fn($o) => intval($o->Status ?? 0) === 1, // ready Force units
        'question' => "Exhaust_a_Force_unit?", 'prompt' => "Choose_a_Force_unit",
    ]);
};

$onAttackAbilities["LOF_033:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    foreach (SWUAllUnits('their') as $mz) {
        $o = GetZoneObject($mz);
        if (SWUObjGone($o)) continue;
        AddTurnEffect($mz, 'NO_TRAIT_FORCE'); // loses Force this phase
    }
};
