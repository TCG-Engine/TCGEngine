<?php
// SOR_094
// Cost 1 - Bail Organa - Rebel Councilor - [Command,Heroism] - Power 1 - HP 2
// Text: Action [Exhaust]: Give an Experience token to another friendly unit.

// SOR_094 Bail Organa — Action [Exhaust]: give an Experience token to another friendly unit.
$unitAbilities["SOR_094"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $targets = SWUOtherFriendlyUnits(intval($player), $mzID);
    if (empty($targets)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget(intval($player), $targets, "Give_an_Experience_token_to_another_friendly_unit", "GIVE_EXPERIENCE|1");
    SWUQueueAfterAction($player);
};
