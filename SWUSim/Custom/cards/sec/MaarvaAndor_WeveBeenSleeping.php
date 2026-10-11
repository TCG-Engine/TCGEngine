<?php
// SEC_252
// Cost 3 - Maarva Andor - We've Been Sleeping - [Heroism] - Power 3 - HP 4
// Text: When Defeated: Give an Experience token to each friendly Rebel unit.

// SEC_252 Maarva Andor — When Defeated: give an Experience token to each friendly Rebel unit.
$whenDefeatedAbilities["SEC_252:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    // ⚠ FRIENDLY spans the TEAM (user ruling 2026-08-25, IBH_095): in Team Suns a teammate's
    // unit is friendly, so the pool is SWUFriendlyUnits() ('team'), not 'my'. ⚠ NOT the same as "a unit you control",
    // which stays 'my' — control is per-player. 'team' degrades to 'my' outside a team game, so
    // Premier is byte-identical. See memory: unqualified pools miss teammates.
    foreach (SWUFriendlyUnits() as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed) && TraitContains($o, 'Rebel')) DoGiveExperienceToken(intval($player), $mz);
    }
};
