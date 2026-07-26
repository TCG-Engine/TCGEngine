<?php
// SOR_162  |  Reprints: SHD_166
// Cost 3 - Disabling Fang Fighter - [Aggression] - Power 3 - HP 2
// Text: When Played: You may defeat an upgrade.

// SOR_162 / SHD_166 Disabling Fang Fighter — "When Played: You may defeat an upgrade."
// Routes through the generic SWUQueueDefeatUpgrade helper (host pick-or-pass).
$whenPlayedAbilities["SOR_162:0"] =
$whenPlayedAbilities["SHD_166:0"] = function($player, $mzID) {
    SWUQueueDefeatUpgrade(intval($player), "Choose_a_unit_to_defeat_its_upgrade", may: true, max: 1);
};
