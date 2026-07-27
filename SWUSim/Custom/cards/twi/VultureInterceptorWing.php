<?php
// TWI_063
// Cost 4 - Vulture Interceptor Wing - [Vigilance] - Power 3 - HP 3
// Text: On Attack: Give an enemy unit -1/-1 for this phase.

// TWI_063 Vulture Interceptor Wing — "On Attack: Give an enemy unit -1/-1 for this phase."
$onAttackAbilities["TWI_063:0"] = function($player, $mzID) {
    SWUOfferUnitTarget(intval($player), $mzID, [
        'continuation' => 'APPLY_PHASE_DEBUFF|1|1|TWI_063', 'side' => 'their', 'may' => true,
        'question' => "Give_an_enemy_unit_-1/-1?", 'prompt' => "Choose_an_enemy_unit",
    ]);
    // Combat owns the after-action.
};
