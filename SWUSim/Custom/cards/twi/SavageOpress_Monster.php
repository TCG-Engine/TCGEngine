<?php
// TWI_137
// Cost 7 - Savage Opress - Monster - [Aggression,Villainy] - Power 7 - HP 7
// Text: When Played: If you control fewer units (including this one) than an opponent, ready this unit.

// TWI_137 Savage Opress — "When Played: If you control fewer units (including this one) than an opponent, ready this unit."
$whenPlayedAbilities["TWI_137:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $myCount    = count(ZoneSearch("myGroundArena",    NonLeaderUnitFilter)) +
                  count(ZoneSearch("mySpaceArena",     NonLeaderUnitFilter));
    $theirCount = count(ZoneSearch("theirGroundArena", NonLeaderUnitFilter)) +
                  count(ZoneSearch("theirSpaceArena",  NonLeaderUnitFilter));
    if ($myCount < $theirCount) OnReadyCard($player, $mzID);
};
