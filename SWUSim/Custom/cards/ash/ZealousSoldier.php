<?php
// ASH_251
// Cost 2 - Zealous Soldier - [Heroism] - Power 2 - HP 3
// Text: When Played: Give an Advantage token to this unit.

// ASH_251 Zealous Soldier — When Played: give an Advantage token to this unit.
$whenPlayedAbilities["ASH_251:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    DoGiveAdvantageToken(intval($player), $mzID);
};
