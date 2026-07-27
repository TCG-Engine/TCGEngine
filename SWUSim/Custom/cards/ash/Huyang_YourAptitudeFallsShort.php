<?php
// ASH_056
// Cost 2 - Huyang - Your Aptitude Falls Short - [Vigilance,Heroism] - Power 2 - HP 4
// Text: On Attack: You may give an upgraded unit -4/-0 for this phase.

// ASH_056 Huyang — On Attack: you may give an upgraded unit -4/-0 for this phase.
$onAttackAbilities["ASH_056:0"] = function($player, $mzID) {
    SWUOfferUnitTarget($player, $mzID, [
        'continuation' => 'APPLY_PHASE_DEBUFF|4|0|ASH_056', 'side' => 'any', 'may' => true,
        'extraFilter' => fn($o) => _SWUIsUpgraded($o),
        'question' => "Give_an_upgraded_unit_-4/-0_this_phase?", 'prompt' => "Choose_an_upgraded_unit",
    ]);
};
