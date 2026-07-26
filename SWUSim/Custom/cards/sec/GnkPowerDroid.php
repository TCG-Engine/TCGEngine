<?php
// SEC_110
// Cost 2 - GNK Power Droid - [Command] - Power 1 - HP 3
// Text: On Attack: The next unit you play this phase costs 1 resource less.

// SEC_110 GNK Power Droid — On Attack: the next unit you play this phase costs 1 resource less.
$onAttackAbilities["SEC_110:0"] = function($player, $mzID) {
    AddGlobalEffects(intval($player), 'SWU_SEC110_DISCOUNT_NEXT');
};
