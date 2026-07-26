<?php
// ASH_214
// Cost 2 - Amnesty Officer - [Cunning] - Power 2 - HP 2
// Text: When Played: You may exhaust a unit with one or more keywords.

// ASH_214 Amnesty Officer — When Played: you may exhaust a unit with one or more keywords.
$whenPlayedAbilities["ASH_214:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $tg = [];
    foreach (SWUAllUnits() as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed) && _SWUUnitHasAnyKeyword($o)) $tg[] = $mz;
    }
    if (empty($tg)) return;
    SWUQueueMayChooseTarget(intval($player), $tg, "Exhaust_a_unit_with_a_keyword?", "Choose_a_unit_with_a_keyword", "EXHAUST_UNIT");
};
