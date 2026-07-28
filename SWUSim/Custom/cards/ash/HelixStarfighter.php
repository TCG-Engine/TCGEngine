<?php
// ASH_221
// Cost 4 - Helix Starfighter - [Cunning] - Power 3 - HP 3
// Text: When Played: If an opponent controls a space unit, give a Shield token to this unit. Otherwise, give 2 Advantage tokens to this unit.

// ASH_221 Helix Starfighter — When Played: if an opponent controls a space unit, give a Shield token to
// this unit; otherwise give 2 Advantage tokens to this unit.
$whenPlayedAbilities["ASH_221:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $oppHasSpace = !empty(ZoneSearch("theirSpaceArena", AnyUnitFilter));
    if ($oppHasSpace) {
        DoGiveShieldToken(intval($player), $mzID);
    } else {
        DoGiveAdvantageToken(intval($player), $mzID);
        DoGiveAdvantageToken(intval($player), $mzID);
    }
};
