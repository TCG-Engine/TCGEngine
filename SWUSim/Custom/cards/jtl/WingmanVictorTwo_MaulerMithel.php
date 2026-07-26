<?php
// JTL_084
// Cost 2 - Wingman Victor Two - Mauler Mithel - [Command,Villainy] - Power 3 - HP 2 - Upgrade Power 1 - Upgrade HP 1
// Text: / Piloting [1 resource Command Villainy] (You may play this as an upgrade on a friendly Vehicle without a Pilot.) / When played as an upgrade: Create a TIE Fighter token.

// JTL_084 Wingman Victor Two (pilot) — When played as an upgrade: Create a TIE Fighter token.
$whenPlayedAsUpgradeAbilities["JTL_084:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    SWUCreateUnitToken(intval($player), 'JTL_T01');
};
