<?php
// LOF_211
// Cost 4 - Dooku - It Is Too Late - [Cunning] - Power 5 - HP 4
// Text: Hidden (This unit can't be attacked if it was played this phase.) / When Played: Each friendly unit with Hidden can't be attacked for this phase.

// LOF_211 Dooku — Hidden + When Played: each friendly unit with Hidden can't be attacked for this phase.
$whenPlayedAbilities["LOF_211:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    // ⚠ FRIENDLY spans the TEAM (user ruling 2026-08-25, IBH_095): in Team Suns a teammate's
    // unit is friendly, so the pool is SWUFriendlyUnits() ('team'), not 'my'. ⚠ NOT the same as "a unit you control",
    // which stays 'my' — control is per-player. 'team' degrades to 'my' outside a team game, so
    // Premier is byte-identical. See memory: unqualified pools miss teammates.
    foreach (SWUFriendlyUnits() as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed) && HasKeyword_Hidden($o)) AddTurnEffect($mz, 'CANT_BE_ATTACKED');
    }
};
