<?php
// TS26_16
// Cost 2 - King Katuunko - Great King of Toydaria - [Vigilance,Command] - Power 2 - HP 4
// Text: When Played: All units (including enemy units) gain Restore 1 for this phase.

// TS26_16 King Katuunko — When Played: all units (including enemy) gain Restore 1 for this phase.
$whenPlayedAbilities["TS26_16:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    foreach (SWUAllUnits() as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed)) AddTurnEffect($mz, SWUMakeTurnEffect('RESTORE', [1], SWU_DUR_PHASE));
    }
};
