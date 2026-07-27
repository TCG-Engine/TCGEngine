<?php
// JTL_066
// Cost 3 - Trace Martez - Trusting Sister - [Vigilance] - Power 2 - HP 5 - Upgrade Power 1 - Upgrade HP 2
// Text: / Piloting [1 resource Vigilance] (You may play this as an upgrade on a friendly Vehicle without a Pilot.) / Attached unit gains: "On Attack: You may heal 2 total damage from any number of units."

// JTL_066 Trace Martez (pilot) — granted "On Attack: You may heal 2 total damage from any number of
// units." (Implemented as heal up to 2 from one chosen unit — the common case.)
$onAttackAbilities["JTL_066:0"] = function($player, $mzID) {
    SWUOfferUnitTarget($player, $mzID, ['continuation'=>'HEAL_TARGET','amount'=>2,'may'=>true,
        'question'=>"Heal_2_from_a_unit",'prompt'=>"Choose_a_unit_to_heal"]);
};
