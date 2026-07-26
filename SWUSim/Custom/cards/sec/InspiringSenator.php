<?php
// SEC_261
// Cost 3 - Inspiring Senator - Power 3 - HP 3
// Text: When Defeated: The next Official unit you play this phase costs 1 resource less.

// SEC_261 Inspiring Senator — When Defeated: the next Official unit you play this phase costs 1 less.
$whenDefeatedAbilities["SEC_261:0"] = function($player, $mzID) {
    AddGlobalEffects(intval($player), 'SWU_SEC261_OFFICIAL_DISCOUNT');
};
