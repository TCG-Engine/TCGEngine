<?php
// LOF_155
// Cost 2 - DRK-1 Probe Droid - [Aggression] - Power 2 - HP 3
// Text: When Played: You may defeat a non-<uq> (non-unique) upgrade.

// LOF_155 DRK-1 Probe Droid — When Played: may defeat a non-unique upgrade.
$whenPlayedAbilities["LOF_155:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    SWUQueueDefeatUpgrade(intval($player), "Defeat_a_non-unique_upgrade?", may: true, max: 1, filter: 'unique=0', min: 0);
};
