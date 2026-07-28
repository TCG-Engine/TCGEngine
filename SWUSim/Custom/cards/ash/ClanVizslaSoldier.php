<?php
// ASH_165
// Cost 2 - Clan Vizsla Soldier - [Aggression] - Power 2 - HP 3
// Text: When Defeated: You may defeat an upgrade.

// ASH_165 Clan Vizsla Soldier — When Defeated: you may defeat an upgrade (any unit, either side).
$whenDefeatedAbilities["ASH_165:0"] = function($player, $mzID) {
    SWUQueueDefeatUpgrade(intval($player), "Defeat_an_upgrade?", may: true, max: 1, min: 0);
};
