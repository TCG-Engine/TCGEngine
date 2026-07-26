<?php
// TWI_096
// Cost 5 - Aayla Secura - Master of the Blade - [Command,Heroism] - Power 6 - HP 5
// Text: Coordinate - On Attack: Prevent all combat damage that would be dealt to this unit for this attack.

// TWI_096 Aayla Secura — "Coordinate - On Attack: Prevent all combat damage that would be dealt to
// this unit for this attack." Mark the attacker (consumed by the combat prevent-attacker check).
$onAttackAbilities["TWI_096:0"] = function($player, $mzID) {
    if (!IsCoordinateActive(intval($player))) return;
    AddTurnEffect($mzID, SWUMakeTurnEffect('TWI_096', [], SWU_DUR_ATTACK));
    // Combat owns the after-action.
};
