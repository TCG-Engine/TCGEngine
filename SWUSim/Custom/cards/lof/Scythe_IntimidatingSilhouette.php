<?php
// LOF_135
// Cost 4 - Scythe - Intimidating Silhouette - [Aggression,Villainy] - Power 3 - HP 5
// Text: On Attack: You may give another friendly Inquisitor unit +2/+0 for this phase.

// LOF_135 Scythe — On Attack: may give another friendly Inquisitor unit +2/+0 for this phase.
$onAttackAbilities["LOF_135:0"] = function($player, $mzID) {
    SWUOfferUnitTarget($player, $mzID, [
        'continuation' => 'APPLY_PHASE_BUFF|2|0|LOF_135',
        'side' => 'friendly', 'traits' => 'Inquisitor', 'excludeSelf' => true, 'may' => true,
        'question' => "Give_another_Inquisitor_unit_+2/+0?", 'prompt' => "Choose_an_Inquisitor",
    ]);
};
