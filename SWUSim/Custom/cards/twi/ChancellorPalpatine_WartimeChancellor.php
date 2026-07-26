<?php
// TWI_203
// Cost 4 - Chancellor Palpatine - Wartime Chancellor - [Cunning,Cunning] - Power 2 - HP 6
// Text: Each token unit you create enters play ready. / On Attack: If a unit left play this phase, create a Clone Trooper token.

// TWI_203 Chancellor Palpatine — "On Attack: If a unit left play this phase, create a Clone Trooper
// token." (The "tokens you create enter ready" passive is hooked in SWUCreateUnitToken.)
$onAttackAbilities["TWI_203:0"] = function($player, $mzID) {
    if (GlobalEffectCount(intval($player), 'SWU_FRIENDLY_LEFT_PLAY') > 0
        || GlobalEffectCount(intval($player), 'SWU_ENEMY_LEFT_PLAY') > 0) {
        SWUCreateUnitToken(intval($player), 'TWI_T02');
    }
    // Combat owns the after-action.
};
