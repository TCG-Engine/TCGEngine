<?php
// JTL_075
// Repair
// Text: Heal 3 damage from a unit or base.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["JTL_075:0"] = function($player, $mzID = '') {
// Repair — heal 3 damage from a unit or base.
            SWUOfferUnitTarget($player, $mzID, ['continuation'=>'HEAL_TARGET','amount'=>3,'includeBases'=>true,'prompt'=>"Heal_3_from_a_unit_or_base"]);
};
