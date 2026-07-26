<?php
// LOF_209
// Cost 3 - Tusken Tracker - [Cunning] - Power 2 - HP 4
// Text: Raid 2 (This unit gets +2/+0 while attacking.) / When Played: Each enemy unit loses Hidden for this phase.

// LOF_209 Tusken Tracker — Raid 2 + When Played: each enemy unit loses Hidden for this phase (keyword
// suppressor LOF_209 → HIDDEN).
$whenPlayedAbilities["LOF_209:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    foreach (SWUAllUnits('their') as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed)) AddTurnEffect($mz, 'LOF_209');
    }
};
