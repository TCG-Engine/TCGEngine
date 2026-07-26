<?php
// SHD_203
// Cost 5 - Zorii Bliss - Valiant Smuggler - [Cunning,Heroism] - Power 4 - HP 7
// Text: On Attack: Draw a card. At the start of the regroup phase, discard a card from your hand. / Smuggle [6 resources Cunning Heroism]

// ─── SHD_203 Zorii Bliss ──────────────────────────────────────────────────────
// On Attack: Draw a card. At the start of the regroup phase, discard a card from your hand.
// Each attack arms one discard (count-stacking flag, consumed in RegroupPhaseStart).
$onAttackAbilities["SHD_203:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    DoDrawCard(intval($player), 1);
    AddGlobalEffects(intval($player), 'SWU_SHD203_DISCARD');
};
