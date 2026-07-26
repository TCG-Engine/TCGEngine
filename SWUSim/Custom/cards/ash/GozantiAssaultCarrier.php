<?php
// ASH_099
// Cost 5 - Gozanti Assault Carrier - [Command,Villainy] - Power 4 - HP 6
// Text: Support (When you play this unit, you may attack with another unit. It gains this unit's other abilities for this attack.) / On Attack: This unit gains Sentinel for this phase.

// ASH_099 Gozanti Assault Carrier — On Attack: this unit gains Sentinel for this phase.
$onAttackAbilities["ASH_099:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    AddTurnEffect($mzID, SWUMakeTurnEffect('SENTINEL', [], SWU_DUR_PHASE, 'ASH_099'));
};
