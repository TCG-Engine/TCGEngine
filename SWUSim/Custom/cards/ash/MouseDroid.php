<?php
// ASH_237
// Cost 1 - Mouse Droid - [Villainy] - Power 1 - HP 1
// Text: Raid 1 (This unit gets +1/+0 while attacking.) / When Played: The next Imperial unit you play this phase costs 1 resource less.

// ASH_237 Mouse Droid — Raid 1 (keyword) + When Played: the next Imperial unit you play this phase costs
// 1 resource less. Arms the SWU_ASH237_DISCOUNT_NEXT one-shot charge.
$whenPlayedAbilities["ASH_237:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    if (GlobalEffectCount(intval($player), 'SWU_ASH237_DISCOUNT_NEXT') <= 0) AddGlobalEffects(intval($player), 'SWU_ASH237_DISCOUNT_NEXT');
};
