<?php
// SEC_163
// Cost 2 - Outer Rim Constable - [Aggression] - Power 3 - HP 1
// Text: When Played: You may defeat an upgrade.

// SEC_163 Outer Rim Constable — When Played: you may defeat an upgrade.
$whenPlayedAbilities["SEC_163:0"] = function($player, $mzID) {
    SWUQueueDefeatUpgrade(intval($player), "Defeat_an_upgrade", may: true, max: 1, min: 0);
};
