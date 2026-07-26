<?php
// ASH_189
// Cost 1 - Emperor's Messenger - [Cunning,Villainy] - Power 0 - HP 3
// Text: Support (When you play this unit, you may attack with another unit. It gains this unit's other abilities for this attack.) / On Attack: Ready a resource.

// ASH_189 Emperor's Messenger — On Attack: ready a resource.
$onAttackAbilities["ASH_189:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    SWUReadyResources(intval($player), 1);
};
