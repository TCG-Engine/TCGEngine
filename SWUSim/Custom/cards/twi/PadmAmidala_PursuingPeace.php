<?php
// TWI_192
// Cost 2 - Padmé Amidala - Pursuing Peace - [Cunning,Heroism] - Power 1 - HP 4
// Text: Coordinate - On Attack: Give an enemy unit -3/-0 for this phase. (Gain this ability while you control 3 or more units.)

// TWI_192 Padmé Amidala — "Coordinate - On Attack: Give an enemy unit -3/-0 for this phase." (Using
// MZMAYCHOOSE per the OnAttack mandatory-MZCHOOSE limitation; declining a pure debuff is never rational.)
$onAttackAbilities["TWI_192:0"] = function($player, $mzID) {
    if (!IsCoordinateActive(intval($player))) return;
    SWUOfferUnitTarget(intval($player), $mzID, [
        'continuation' => 'APPLY_PHASE_DEBUFF|3|0|TWI_192', 'side' => 'their', 'may' => true,
        'question' => "Give_an_enemy_unit_-3/-0?", 'prompt' => "Choose_an_enemy_unit",
    ]);
};
