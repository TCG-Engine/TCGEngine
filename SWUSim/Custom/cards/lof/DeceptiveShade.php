<?php
// LOF_180
// Cost 2 - Deceptive Shade - [Cunning,Villainy] - Power 2 - HP 3
// Text: When Defeated: The next unit you play this phase gains Ambush for this phase.

// LOF_180 Deceptive Shade — When Defeated: the next unit you play this phase gains Ambush for this phase.
$whenDefeatedAbilities["LOF_180:0"] = function($player, $mzID) {
    AddGlobalEffects(intval($player), 'SWU_LOF180_NEXT_AMBUSH');
};
