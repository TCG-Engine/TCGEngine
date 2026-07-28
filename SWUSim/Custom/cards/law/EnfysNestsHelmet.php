<?php
// LAW_186
// Cost 2 - Enfys Nest's Helmet - [Aggression,Heroism] - Upgrade Power 0 - Upgrade HP 2
// Text: Attach to a non-Vehicle unit. / Attached unit gains: "On Attack: You may give another unit +3/+0 for this phase."

// LAW_186 Enfys Nest's Helmet — granted "On Attack: You may give another unit +3/+0 for this phase."
// (OnAttackFromUpgrade seam; $mzID = the attacking host. "Another" excludes the host.)
$onAttackAbilities["LAW_186:0"] = function($player, $mzID) {
    SWUOfferUnitTarget(intval($player), $mzID, [
        'continuation' => 'APPLY_PHASE_BUFF|3|0|LAW_186',
        'side'         => 'any',
        'excludeSelf'  => true,
        'may'          => true,
        'question'     => "Give_another_unit_+3/+0_this_phase?",
        'prompt'       => "Choose_a_unit",
    ]);
};
