<?php
// ASH_248
// Cost 1 - Neel - The Cutest Boy - [Heroism] - Power 1 - HP 4
// Text: When Played/On Attack: The next unit you play this phase with 1 or less power enters play ready.

// ASH_248 Neel — When Played/On Attack: the next unit you play this phase with 1 or less power enters
// play ready. Arms the SWU_ASH248_READY flag (consumed in ActivateCard's entry-status).
$whenPlayedAbilities["ASH_248:0"] = $onAttackAbilities["ASH_248:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    if (GlobalEffectCount(intval($player), 'SWU_ASH248_READY') <= 0) AddGlobalEffects(intval($player), 'SWU_ASH248_READY');
};
