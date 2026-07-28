<?php
// TS26_14
// Cost 5 - Yoda - Begun, the Clone War Has - [Vigilance,Command,Heroism] - Power 4 - HP 4
// Text: If you control 7 or more resources, this unit costs 2 resources less to play. / When Played/When Defeated: Create a Clone Trooper token and give it Sentinel for this phase.

// TS26_14 Yoda — cost -2 if 7+ resources (cost modifier). When Played/When Defeated: create a Clone
// Trooper token and give it Sentinel for this phase.
$whenPlayedAbilities["TS26_14:0"] = $whenDefeatedAbilities["TS26_14:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $uid = SWUCreateUnitToken(intval($player), 'TS26_T02');
    $mz = SWUFindMzByUID($uid);
    if ($mz !== null) AddTurnEffect($mz, 'SENTINEL');   // Sentinel this phase (ready-made grant token)
};
