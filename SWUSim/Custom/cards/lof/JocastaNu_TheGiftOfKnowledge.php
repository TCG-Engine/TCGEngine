<?php
// LOF_248
// Cost 3 - Jocasta Nu - The Gift of Knowledge - [Heroism] - Power 3 - HP 4
// Text: When Played: You may attach a friendly upgrade on a friendly unit to a different eligible unit.

// LOF_248 Jocasta Nu — When Played: may move a friendly upgrade to a different eligible unit.
$whenPlayedAbilities["LOF_248:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    SWUQueueMoveUpgrade(intval($player), '', "Move_a_friendly_upgrade_to_a_different_unit", '', '', friendlyOnly: true);
};
