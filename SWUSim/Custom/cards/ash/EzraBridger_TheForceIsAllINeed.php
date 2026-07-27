<?php
// ASH_209
// Cost 6 - Ezra Bridger - The Force is All I Need - [Cunning,Heroism] - Power 6 - HP 6
// Text: Support (When you play this unit, you may attack with another unit. It gains this unit's other abilities for this attack.) / On Attack: If this unit is upgraded, you may give a unit -3/-0 for this phase.

// ASH_209 Ezra Bridger — On Attack: if this unit is upgraded, you may give a unit -3/-0 for this phase.
$onAttackAbilities["ASH_209:0"] = function($player, $mzID) {
    $self = GetZoneObject($mzID);
    if (SWUObjGone($self) || !_SWUIsUpgraded($self)) return;
    SWUOfferUnitTarget($player, $mzID, [
        'continuation' => 'APPLY_PHASE_DEBUFF|3|0|ASH_209', 'side' => 'any', 'may' => true,
        'question' => "Give_a_unit_-3/-0_this_phase?", 'prompt' => "Choose_a_unit",
    ]);
};
