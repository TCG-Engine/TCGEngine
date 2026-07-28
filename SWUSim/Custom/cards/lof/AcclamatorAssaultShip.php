<?php
// LOF_106
// Cost 7 - Acclamator Assault Ship - [Command,Command] - Power 5 - HP 8
// Text: On Attack: You may give another unit +5/+5 for this phase.

// LOF_106 Acclamator Assault Ship — On Attack: may give another unit +5/+5 for this phase.
$onAttackAbilities["LOF_106:0"] = function($player, $mzID) {
    SWUOfferUnitTarget($player, $mzID, [
        'continuation' => 'APPLY_PHASE_BUFF|5|5|LOF_106',
        'side' => 'any', 'excludeSelf' => true, 'may' => true,
        'question' => "Give_another_unit_+5/+5?", 'prompt' => "Choose_a_unit",
    ]);
};
