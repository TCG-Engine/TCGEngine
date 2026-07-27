<?php
// JTL_057
// Cost 1 - Astromech Pilot - [Vigilance] - Power 1 - HP 3 - Upgrade Power 1 - Upgrade HP 3
// Text: / Piloting [2 resources Vigilance] (You may play this as an upgrade on a friendly Vehicle without a Pilot.) / When played as an upgrade: You may heal 2 damage from a unit.

// JTL_057 Astromech Pilot (pilot) — When played as an upgrade: You may heal 2 damage from a unit.
$whenPlayedAsUpgradeAbilities["JTL_057:0"] = function($player, $mzID) {
    SWUOfferUnitTarget($player, $mzID, ['continuation'=>'HEAL_TARGET','amount'=>2,'may'=>true,
        'question'=>"Heal_2_from_a_unit",'prompt'=>"Choose_a_unit_to_heal"]);
};
