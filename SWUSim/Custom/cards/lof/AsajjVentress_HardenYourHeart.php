<?php
// LOF_165
// Cost 5 - Asajj Ventress - Harden Your Heart - [Aggression] - Power 5 - HP 6
// Text: When Played/On Attack: Give another friendly Force unit +2/+0 for this phase.

// LOF_165 Asajj Ventress — When Played/On Attack: give another friendly Force unit +2/+0 for this phase.
$whenPlayedAbilities["LOF_165:0"] =
$onAttackAbilities["LOF_165:0"]   = function($player, $mzID) {
    SWUOfferUnitTarget($player, $mzID, [
        'continuation' => 'APPLY_PHASE_BUFF|2|0|LOF_165',
        'side' => 'my', 'traits' => 'Force', 'excludeSelf' => true, 'may' => true,
        'question' => "Give_another_Force_unit_+2/+0?", 'prompt' => "Choose_a_Force_unit",
    ]);
};
