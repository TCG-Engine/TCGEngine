<?php
// SEC_098
// Cost 4 - Captain Typho - All Necessary Precautions - [Command,Heroism] - Power 4 - HP 5
// Text: Sentinel / When this unit is attacked: You may disclose CommandHeroism. If you do, heal 1 damage from your base.

$onDefenseAbilities["SEC_098:0"] = function($player, $mzID) {
    SWUQueueDisclose(intval($player), ['Command', 'Heroism'], "SEC_098#0",
        "Disclose_CommandHeroism_to_heal_1_from_your_base");
};

$customDQHandlers["SEC_098#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    OnHealBase(intval($player), intval($player), 1);
};
