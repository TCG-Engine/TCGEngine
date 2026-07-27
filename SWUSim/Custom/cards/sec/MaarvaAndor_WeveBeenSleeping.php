<?php
// SEC_252
// Cost 3 - Maarva Andor - We've Been Sleeping - [Heroism] - Power 3 - HP 4
// Text: When Defeated: Give an Experience token to each friendly Rebel unit.

// SEC_252 Maarva Andor — When Defeated: give an Experience token to each friendly Rebel unit.
$whenDefeatedAbilities["SEC_252:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    foreach (SWUAllUnits('my') as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed) && TraitContains($o, 'Rebel')) DoGiveExperienceToken(intval($player), $mz);
    }
};
