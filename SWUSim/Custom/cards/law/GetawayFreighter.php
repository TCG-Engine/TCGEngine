<?php
// LAW_155
// Cost 3 - Getaway Freighter - [Command] - Power 1 - HP 4
// Text: On Attack: If you control a ground unit, create a Credit token.

// LAW_155 Getaway Freighter — On Attack: if you control a ground unit, create a Credit token.
$onAttackAbilities["LAW_155:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    if (!empty(ZoneSearch("myGroundArena", AnyUnitFilter))) SWUCreateCreditToken(intval($player), 1);
};
